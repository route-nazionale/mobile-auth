<?php
/**
 * User: lancio
 * Date: 18/07/14
 * Time: 01:16
 */

namespace Rn2014\Auth;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Monolog\Logger;
use Rn2014\AESEncoder;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    protected $dbVarchi;
    protected $clientAuth;
    protected $aes;
    protected $apiUrl;

    public function __construct(Client $client, Connection $dbal, AESEncoder $aes, Logger $authLogger, $authApiUrl = "")
    {
        $this->dbVarchi = $dbal;
        $this->clientAuth = $client;
        $this->aes = $aes;
        $this->logger = $authLogger;
        $this->apiUrl = $authApiUrl;
    }

    public function attemptLogin(Request $request, $group)
    {
        $birthdate = $request->get('birthdate', null);
        $cu  = $request->get('cu', null);

        $context = [
            'cu' => $cu,
            'birthdate' => $birthdate,
            'group' => $group,
            'result' => null,
            'imei' => $request->get('imei'),
            'ip' => $request->getClientIps(),
            'user_agent' => $request->headers->get('User-Agent'),
        ];

        if (!$cu || $birthdate) {

            $this->logger->addInfo("KO no data", $context);

            return [
                "code" => 401,
                "result" => "missing data",
            ];
        }

        $cryptedBirthdate = $this->aes->encode($birthdate);

        $statusCode = $this->getAuthStatusCode($cu, $cryptedBirthdate, $group);

        switch ($statusCode) {
            case 500:
                $result = [
                    "code" => 500,
                    "result" => "retry later",
                ];
                $context['result'] = $result;

                $this->logger->addCritical("server response 500", $context);
                return $result;
            case 204:
                $result = [
                    "code" => 200,
                    "result" => "security",
                ];
                $context['result'] = $result;
                $this->logger->addInfo("OK security", $context);

                return $result;
            case 401:
                $result = [
                    "code" => 401,
                    "result" => "missing data",
                ];
                $context['result'] = $result;
                $this->logger->addInfo("KO no data", $context);
                return $result;;
            case 403:

                $ok = $this->existsPerson($cu, $birthdate) && $this->isCapoSpalla($cu);

                if ($ok) {

                    $result = [
                        "code" => 200,
                        "result" => "capospalla",
                    ];
                    $context['result'] = $result;
                    $this->logger->addInfo("OK capospalla", $context);
                    return $result;
                }
        }

        $result = [
            "code" => 403,
            "result" => "not authorized",
        ];
        $context['result'] = $result;
        $this->logger->addInfo("KO no auth", $context);
        return $result;
    }

    public function existsPerson($cu, $birthdate)
    {
        $sql = "SELECT * FROM date WHERE cu = :cu AND datanascita = :birthdate limit 1";
        $result = $this->dbVarchi->fetchAssoc($sql, [
            'cu' => $cu,
            'birthdate' => $birthdate
        ]);

        return ($result || 0);
    }

    public function isCapoSpalla($cu)
    {
        $sql = "SELECT * FROM assegnamenti WHERE cu = :username AND staffEvent = 1";
        $result = $this->dbVarchi->fetchAssoc($sql, [
            'cu' => $cu,
        ]);

        return ($result || 0);
    }

    public function getAuthStatusCode($cu, $cryptedBirthdate, $group)
    {
        $body = json_encode([
            'username' => $cu,
            'birthdate' => $cryptedBirthdate,
            'group' => $group,
        ]);

        try{
            $this->response = $this->clientAuth->post($this->apiUrl,[
                'headers' => ['Content-type' => 'application\json'],
                "body" => $body
            ]);

        } catch (RequestException $e) {
            return 500;
        }

        return $this->response->getStatusCode();
    }
} 