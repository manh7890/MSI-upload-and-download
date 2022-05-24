<?php
/**
 * Created by PhpStorm.
 * User: Nguyen Viet Dinh
 * Date: 9/3/2015
 * Time: 3:42 PM
 */
use Library\Load;

ignore_user_abort(true);
set_time_limit(0);
ob_end_clean();
ob_start();
header("Connection: close");
header('Content-Encoding: none');
define("SITE_NAME", "https://".$_SERVER['SERVER_NAME']);
define('PRIVATE_KEY','EGRRH^&%&&%6584');

define('USE_MEMCACHE',false);
define('CACHE_ENGINE', 'memcache');
define('DATETIME_FORMAT',"Y-m-d H:i:s");


// <<<<<<< HEAD
error_reporting(E_ALL);   
    
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); 
// =======
// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
// >>>>>>> 334bb8f87996d36fa374fab66bcc404849f25705
ini_set('display_errors', 1);
require_once DIR.'/Library/Load.php';
Load::autoLoad();