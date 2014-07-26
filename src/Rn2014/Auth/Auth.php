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
use Rn2014\Varchi;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    protected $varchi;
    protected $clientAuth;
    protected $aes;
    protected $apiUrl;

    public function __construct(Client $client, Varchi $varchi, AESEncoder $aes, Logger $authLogger, $authApiUrl = "")
    {
        $this->varchi = $varchi;
        $this->clientAuth = $client;
        $this->aes = $aes;
        $this->logger = $authLogger;
        $this->apiUrl = $authApiUrl;
    }

    public function attemptLogin(Request $request, $group)
    {
        $birthdate = $request->request->get('date', null);
        $cu  = $request->request->get('cu', null);

        $context = [
            'cu' => $cu,
            'birthdate' => $birthdate,
            'group' => $group,
            'result' => null,
            'imei' => $request->request->get('imei'),
            'ip' => $request->getClientIps(),
            'user_agent' => $request->headers->get('User-Agent'),
        ];

        if (!$cu || !$birthdate) {

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

                $ok = $this->varchi->existsPerson($cu, $birthdate) && $this->varchi->isCapoSpalla($cu);

                if ($ok) {

                    $result = [
                        "code" => 200,
                        "result" => "event",
                    ];
                    $context['result'] = $result;
                    $this->logger->addInfo("OK event", $context);
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

            return [
                'code' => 500,
                'result' => $e->getMessage(),
            ];
        }

        return [
            'code' => $this->response->getStatusCode(),
            'result' => '-'
        ];
    }
} 