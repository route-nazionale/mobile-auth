<?php
/**
 * User: lancio
 * Date: 15/07/14
 * Time: 01:38
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

//$checkJsonRequest = (function (Request $request) {
//    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
//        $data = json_decode($request->getContent(), true);
//        $request->request->replace(is_array($data) ? $data : array());
//    }else
//        return new JsonResponse(["content" => "JsonData Missing"], 406);
//});

$app->before(function() use ($app){

    if (HTTPS_REQUIRED) {
        $app->get('_controller')->requireHttps();
    }
});

/**
 * CONTROLLERS
 */
$gate = require __DIR__ . "/controllers/gate.php";
$meal = require __DIR__ . "/controllers/meal.php";

$app->mount('/', $gate);
$app->mount('/meal', $meal);


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

    } else {
        //if ($code == 500) {

        $data = array(
            'title' => "C'è stato un problema."
        );
        return new Response( $app['twig']->render('500.html.twig', $data), 500);
    }

//    return new Response('Spiacenti, c\'è stato un problema.', $code);
});

return $app;