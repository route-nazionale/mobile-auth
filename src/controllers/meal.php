<?php
/**
 * User: lancio
 * Date: 30/07/14
 * Time: 23:04
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$meal = $app['controllers_factory'];

/**
 * MENSA
 */
$meal->post("/auth.php", function() use ($app){

    $app['monolog']->addNotice("request-meal-auth", $app['request']->request->all());
    // indice di ristampa badge
    $reprint = $app['request']->get('reprint');

    $app['auth']->setSecondaryAuth('meal');
    $group  = "security";

    try {

        $result = $app['auth']->attemptLogin($app['request'], $group);

    } catch (\Exception $e) {
        return new Response($e->getMessage(), 500);
    }

    return new Response($result['result'], $result['code']);
});

$meal->post("/post.php", function() use ($app){

    $app['monolog']->addNotice("request-meal-post", $app['request']->request->all());

//     indice di ristampa badge
//    $reprint = $app['request']->request->get('reprint');

    $app['auth']->setSecondaryAuth('meal');
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
            $arrayStat = $app['statistics.meal']->insertStatistics($stat);
            $app['monolog.stats']->addNotice($stat->type, $arrayStat);
        }

        return new Response(null, 200);
    }
});

$meal->post("/ident.php", function() use ($app){

    return new Response(null, 400);
});

$meal->get("/md5.php", function() use ($app){

    return md5_file(__DIR__ . " /../web/meal/" . SQLITE_DB_MEAL_FILENAME . ".gz");
});

return $meal;