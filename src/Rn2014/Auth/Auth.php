<?php
/**
 * User: lancio
 * Date: 18/07/14
 * Time: 01:16
 */

namespace Rn2014\Auth;

use GuzzleHttp\Client;
use Monolog\Logger;
use Rn2014\AESEncoder;
use Rn2014\Varchi;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\RequestException;

class Auth implements AuthInterface
{
    protected $varchi;
    protected $clientAuth;
    protected $aes;
    protected $apiUrl;
    protected $context;
    protected $secondaryAuth;

    public function __construct(Client $client, Varchi $varchi, AESEncoder $aes, Logger $authLogger, $authApiUrl = "")
    {
        $this->varchi = $varchi;
        $this->clientAuth = $client;
        $this->aes = $aes;
        $this->logger = $authLogger;
        $this->apiUrl = $authApiUrl;
    }

    public function setSecondaryAuth($secondaryAuth)
    {
        $this->secondaryAuth = $secondaryAuth;
    }

    public function attemptLogin(Request $request, $group)
    {
        $response = new AuthResponse();
        $birthdate = $request->request->get('date', null);
        $cu  = $request->request->get('cu', null);

        $this->context = [
            'cu' => $cu,
            'birthdate' => $birthdate,
            'group' => $group,
            'result' => null,
            'imei' => $request->request->get('imei'),
            'ip' => $request->getClientIps(),
            'user_agent' => $request->headers->get('User-Agent'),
        ];

        if (!$cu || !$birthdate) {

            $this->logger->addInfo("KO no data", $this->context);

            return $response->setCode(401)
                            ->setResult("missing data")
                            ->toArray();
        }

        $cryptedBirthdate = $this->aes->encode($birthdate);

        $result = $this->getAuthStatusCode($cu, $cryptedBirthdate, $group);

        switch ($result['code']) {
            case 500:
                $result = $response->setCode(500)
                                    ->setResult("retry later")
                                    ->toArray();

                $this->context['result'] = $result;

                $this->logger->addCritical("server response 500", $this->context);
                return $result;
            case 204:
                $result = $response->setCode(200)
                                    ->setResult("security")
                                    ->toArray();

                $this->context['result'] = $result;
                $this->logger->addInfo("OK $group", $this->context);

                return $result;
            case 401:
                $result = $response->setCode(401)
                                    ->setResult("missing data")
                                    ->toArray();

                $this->context['result'] = $result;
                $this->logger->addInfo("KO no data", $this->context);

                return $result;
            case 403:
                if ("event" == $this->secondaryAuth) {
                    $result = $this->attemptLoginAsCapoSpalla($cu, $birthdate);
                }
                break;
            default:

                $result = $response->setCode(403)
                                    ->setResult("not authorized")
                                    ->toArray();
                $this->context['result'] = $result;
                $this->logger->addInfo("KO no auth", $this->context);
        }

        return $result;
    }

    public function getAuthStatusCode($cu, $cryptedBirthdate, $group)
    {
        $response = new AuthResponse();

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

            return $response->setCode(500)
                ->setResult($e->getMessage())
                ->toArray();

        }

        return $response->setCode($this->response->getStatusCode())
            ->setResult("-")
            ->toArray();
    }

    public function attemptLoginAsCapoSpalla($cu, $birthdate)
    {
        $response = new AuthResponse();

        $ok = $this->varchi->existsPerson($cu, $birthdate) && $this->varchi->isCapoSpalla($cu);
        if ($ok) {

            $result = $response->setCode(200)
                ->setResult("event")
                ->toArray();

            $this->context['result'] = $result;
            $this->logger->addInfo("OK event", $this->context);

            return $result;
        }

        $result = $response->setCode(403)
                            ->setResult("not authorized")
                            ->toArray();

        $this->context['result'] = $result;
        $this->logger->addInfo("KO no auth", $this->context);

        return $result;
    }
} 