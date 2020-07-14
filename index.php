<?php
/**
 * Created by PhpStorm.
 * User: Nguyen Viet Dinh
 * Date: 9/3/2015
 * Time: 3:42 PM
 */

use Library\Route;
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: *");
header ("Access-Control-Max-Age: 86400");
define("DIR", dirname(__FILE__));
require 'Config/Init.php';
$uri=isset($_GET['uri'])?$_GET['uri']:'';
if($uri!='404.html'){
    Route::performRequest($uri);
}