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

$app['aes.encoder'] = $app->share(function() use ($app) {

    if (AES_IV && AES_KEY) {

        $iv = AES_IV;
        $key = AES_KEY;

    } else {

        $sql = "SELECT * FROM aes LIMIT 1";
        $cryptData = $app['dbs']['aes']->fetchAssoc($sql);

        if (!$cryptData) {
            throw new \Exception("key and iv not found");
        }

        $iv = base64_decode($cryptData['iv']);
        $key = base64_decode($cryptData['key']);
    }

    return new AESEncoder($key,$iv);
});

$app['auth.client'] = $app->share(function() use ($app) {
    $client = new GuzzleHttp\Client();

    return $client;

});
$app['auth'] = $app->share(function() use ($app) {

    $auth = new Auth($app['auth.client'], $app['dbs']['varchi'], $app['aes.encoder'], $app['monolog.login'], API_AUTH);

    $auth->setAuthFake(AUTH_FAKE);

    return $auth;
});

return $app;