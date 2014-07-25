<?php
/**
 * User: lancio
 * Date: 15/07/14
 * Time: 01:38
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$checkJsonRequest = (function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }else
        return new JsonResponse(["content" => "JsonData Missing"], 406);
});

$app->before(function() use ($app){

    if (HTTPS_REQUIRED) {
        $app->get('_controller')->requireHttps();
    }
});

$app->post("/auth", function() use ($app){

    $group  = "security";

    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new JsonResponse(["error" => $e->getMessage()], 500);
    }

    return new JsonResponse($result['result'], $result['code']);
})
    ->before($checkJsonRequest);

if ($app['debug']) {

    $app->get("/encode/{password}", function($password) use ($app){

        return $app['aes.encoder']->encode($password);
    });

    $app->get("/decode", function() use ($app){

        $password = $app['request']->query->get('password', '');

        return $app['aes.encoder']->decode($password);
    });
}

$app->error(function (\Exception $e, $code) use ($app) {

    // commented for testing purposes
    if ($app['debug']) {
        return;
    }

    if ($code == 404) {

        $data = array(
            'title' => "Ti sei perso? usa la bussola!"
        );

        return new Response( $app['twig']->render('404.html.twig', $data), 404);

    } elseif ($code == 500) {

        $data = array(
            'title' => "C'è stato un problema."
        );
        return new Response( $app['twig']->render('500.html.twig', $data), 500);
    }

    return new Response('Spiacenti, c\'è stato un problema.', $code);
});

return $app;