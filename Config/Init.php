<?php
/**
 * Created by PhpStorm.
 * User: Nguyen Viet Dinh
 * Date: 9/3/2015
 * Time: 3:42 PM
 */
use Library\Load;
date_default_timezone_set('Asia/Ho_Chi_Minh');
ignore_user_abort(true);
set_time_limit(0);
ob_end_clean();
ob_start();
header("Connection: close");
header('Content-Encoding: none');
define("SITE_NAME", "https://".$_SERVER['SERVER_NAME']);
define('PRIVATE_KEY','EGRRH^&%&&%6584');
define('SERVICE_DEFINITION','nana-framework.symper.vn');

define('USE_MEMCACHE',false);
define('CACHE_ENGINE', 'memcache');
define('DATETIME_FORMAT',"Y-m-d H:i:s");


error_reporting(E_ALL);    
ini_set('display_errors', 1);
require_once DIR.'/Library/Load.php';
Load::autoLoad();