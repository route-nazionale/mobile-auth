<?php
/**
 * User: lancio
 * Date: 15/07/14
 * Time: 01:32
 */

use Silex\Provider as Providers;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Rn2014\AESEncoder;
use Rn2014\Auth\Auth;
use Rn2014\Auth\AuthFake;
use Rn2014\Statistic;
use Rn2014\Varchi;

$app->register(new Providers\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
]);

$app->register(new Providers\MonologServiceProvider(),[
    'monolog.logfile' => __DIR__.'/../logs/development.log',
    'monolog.name' => 'auth',
    'monolog.level' => Logger::WARNING,
]);

$app->register(new Providers\SessionServiceProvider());

$app->register(new Providers\UrlGeneratorServiceProvider());

$app->register(new Providers\DoctrineServiceProvider(), [
    'dbs.options' => [
        'aes' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'   => MYSQL_DB_AES,
            'user'     => MYSQL_USER_AES,
            'password' => MYSQL_PASS_AES,
            'charset'  => 'utf8',
        ],
        'varchi' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'   => MYSQL_DB_VARCHI,
            'user'     => MYSQL_USER_VARCHI,
            'password' => MYSQL_PASS_VARCHI,
            'charset'  => 'utf8',
        ],
    ],
]);

/**
 * Loggers
 */
$app['monolog.login.logfile'] = __DIR__ . '/../logs/mobile-auth.log';
$app['monolog.login.level'] = Logger::INFO;
$app['monolog.login'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('login');
    $handler = new StreamHandler($app['monolog.login.logfile'], $app['monolog.login.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.login.meal'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('login.meal');
    $handler = new StreamHandler($app['monolog.login.logfile'], $app['monolog.login.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.stats.logfile'] = __DIR__ . '/../logs/mobile-stats.log';
$app['monolog.stats.level'] = Logger::INFO;
$app['monolog.stats'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('stats');
    $handler = new StreamHandler($app['monolog.stats.logfile'], $app['monolog.stats.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['aes.encoder'] = $app->share(function() use ($app) {
    $aesManager = new \Rn2014\AESManager($app['dbs']['aes']);

    return $aesManager->getEncoder();
});

$app['auth.client'] = $app->share(function() use ($app) {
    $client = new GuzzleHttp\Client();
    $client->setDefaultOption('verify', '/etc/ssl/rn2014/cacert.pem');
    $client->setDefaultOption('headers/Content-type', 'application/json');

    return $client;
});

$app['varchi'] = $app->share(function() use ($app){
    return new Varchi($app['dbs']['varchi']);
});

$app['auth'] = $app->share(function() use ($app) {

    if (!AUTH_FAKE) {
        $auth = new Auth($app['auth.client'], $app['varchi'], $app['aes.encoder'], $app['monolog.login'], API_AUTH);
    } else {

        $users = [
            "OT-1541-028230" => ["1990-10-10", LDAP_GATE_GROUP],
            "AG-0395-018827" => ["1996-05-08", "event"],
            "AG-0395-018826" => ["1996-05-08", LDAP_MEAL_GROUP],
        ];
        $auth = new AuthFake($users, $app['monolog.login']);
    }

    return $auth;
});

$app['statistics'] = $app->share(function() use ($app){
    return new Statistic($app['dbs']['varchi'], "statistiche");
});

$app['statistics.meal'] = $app->share(function() use ($app){
    return new Statistic($app['dbs']['varchi'], "statistiche_mensa");
});

// Http cache
$app['cache.path'] = __DIR__.'/../cache/';
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';

// Twig cache
$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => $app['cache.path'] . '/twig');


return $app;