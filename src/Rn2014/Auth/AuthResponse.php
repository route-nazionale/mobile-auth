<?php
/**
 * User: lancio
 * Date: 29/07/14
 * Time: 01:17
 */

namespace Rn2014\Auth;


class AuthResponse {

    private $code;
    private $result;

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'result' => $this->result,
        ];
    }
}