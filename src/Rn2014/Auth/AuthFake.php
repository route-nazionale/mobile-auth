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

class AuthFake implements AuthInterface
{
    protected $users = false;
    protected $secondaryAuth;

    public function __construct(array $users = [], Logger $logger)
    {
        $this->users = $users;
        $this->logger = $logger;
    }

    public function setSecondaryAuth($auth)
    {
        $this->secondaryAuth = $auth;
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

        if (isset($this->users[$cu]) && ("security" == $this->users[$cu][1] || $this->secondaryAuth == $this->users[$cu][1])) {
            $result = [
                "code" => 200,
                "result" => $this->users[$cu][1],
            ];
            $context['result'] = $result;
            $this->logger->addInfo("FAKE OK {$this->users[$cu][1]}", $context);
            return $result;

        }

        $result = [
            "code" => 403,
            "result" => "not authorized",
        ];
        $context['result'] = $result;
        $this->logger->addInfo("KO no auth", $context);
        return $result;
    }
} 