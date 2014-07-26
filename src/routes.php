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

$app->post("/auth.php", function() use ($app){

    // indice di ristampa badge
    $reprint = $app['request']->get('reprint');

    $group  = "security";

    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new Response($e->getMessage(), 500);
    }

    return new Response($result['result'], $result['code']);
});

$app->post("/post.php", function() use ($app){

    // indice di ristampa badge
    $reprint = $app['request']->request->get('reprint');

    $group  = "security";

    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new Response($e->getMessage(), 500);
    }

    // check auth $result
    if ($result['code'] != 200) {
        return new Response($result, $result['code']);
    }

    $data = $app['request']->get('json');
    $json = json_decode($data);

    if (!$json || !property_exists($json, 'update') || !isset($json->update)) {
        return new Response(null, 401);

    } else {

        foreach($json->update as $stat){

            $app['statistics']->insertStatistics($stat);
        }

        return new Response(null, 200);
    }
});

$app->get("/ident.php", function() use ($app){

    if (!isset($_POST['search']) || !isset($_POST['cu']) || !isset($_POST['date'])){

        return new Response(null, 400);
    }
    $search = $app['requeest']->request->get('search');

    $group = "security";
    /*
     * Autenticazione
     * Solo SECURITY!
     */
    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new Response($e->getMessage(), 500);
    }

    // check auth $result
    if ($result['code'] != 200) {
        return new Response($result, $result['code']);
    }

    // check auth $result
    if ($result['result'] != "security") {
        return new Response("No auth", 403);
    }

    $all = $app['varchi']->findByCU($search);

    $json = new \stdClass();
    $json->status = "notfound";

    if (count($all) == 1){
        $row = $all[0];

        $json->status = "found";
        $json->nome = $row['nome'];
        $json->cognome = $row['cognome'];
        $json->data = $row['datanascita'];

    }

    return new JsonResponse($json);
});

$app->get("/md5.php", function() use ($app){

    return md5_file(__DIR__ . " /../web/" . SQLITE_DB_FILENAME . ".gz");
});

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