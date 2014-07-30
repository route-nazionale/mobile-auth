<?php
/**
 * User: lancio
 * Date: 29/07/14
 * Time: 01:04
 */
namespace Rn2014\Auth;

use Symfony\Component\HttpFoundation\Request;

interface AuthInterface
{
    public function setSecondaryAuth($secondaryAuth);

    public function attemptLogin(Request $request, $group);
}