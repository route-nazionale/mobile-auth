<?php
/**
 * User: lancio
 * Date: 30/07/14
 * Time: 23:09
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$gate = $app['controllers_factory'];

$gate->post("/auth.php", function() use ($app){

    $app['monolog']->addNotice("request-auth", $app['request']->request->all());
    // indice di ristampa badge
    $reprint = $app['request']->get('reprint');

    $app['auth']->setSecondaryAuth('event');
    $group  = "security";

    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new Response($e->getMessage(), 500);
    }

    return new Response($result['result'], $result['code']);
});

$gate->post("/post.php", function() use ($app){

    $app['monolog']->addNotice("request-post", $app['request']->request->all());
    // indice di ristampa badge
    $reprint = $app['request']->request->get('reprint');

    $app['auth']->setSecondaryAuth('event');
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
            $arrayStat = $app['statistics']->insertStatistics($stat);
            $app['monolog.stats']->addNotice($stat->type, $arrayStat);
        }

        return new Response(null, 200);
    }
});

$gate->post("/ident.php", function() use ($app){

    $app['monolog']->addNotice("request-ident", $app['request']->request->all());

    $cu = $app['request']->request->get('cu');
    $date = $app['request']->request->get('date');
    $search = $app['request']->request->get('search');
    $imei = $app['request']->request->get('imei');

    if (!$search || !$cu || !$date|| !$imei){

        return new Response(null, 400);
    }

    $app['auth']->setSecondaryAuth(null);
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

    $person = $app['varchi']->findByCU($search);

    $json = new \stdClass();
    $json->status = "notfound";

    if ($person){
        $json->status = "found";
        $json->nome = $person['nome'];
        $json->cognome = $person['cognome'];
        $json->data = $person['datanascita'];
    }

    return new JsonResponse($json);
});

$gate->get("/md5.php", function() use ($app){

    return md5_file(__DIR__ . " /../web/" . SQLITE_DB_FILENAME . ".gz");
});

return $gate;