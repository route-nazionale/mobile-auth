<?php
/**
 * User: lancio
 * Date: 25/07/14
 * Time: 01:56
 */

define('APP_DEBUG', false);

define('AUTH_FAKE', true);
define('HTTPS_REQUIRED', false);

define('API_AUTH', "https://localhost/auth");

/*
 * se non sono presenti le cerca su db
 * iv e key di decrypt
 */
define('AES_IV', false);//base64_decode('aE5RaTRTTmJTNnFuSXFRQQ==')); //iv 16byte
define('AES_KEY', false);//base64_decode('TVkwNVVreVpJak5yTWtVZUYwcTRxeXE5RllCcXZuM0U=')); //key 32byte

/**
 * parametri accesso db
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');

/**
 * AES accesso al db per prelevare iv e key di decrypt
 */
define('MYSQL_DB_AES', 'db');
define('MYSQL_USER_AES', 'user');
define('MYSQL_PASS_AES', 'password');

/**
 * AES accesso al db per verificare il capospalla
 */
define('MYSQL_DB_VARCHI', 'db');
define('MYSQL_USER_VARCHI', 'user');
define('MYSQL_PASS_VARCHI', 'password');

/**
 * nome del db sqlite
 */
define('SQLITE_DB_FILENAME', 'database.db');