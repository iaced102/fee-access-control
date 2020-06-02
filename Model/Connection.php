<?php
namespace Model;

use library\CacheService;

class Connection{
    private static function _connectPostgreSQL($server, $userName, $password, $database){
        $connection = pg_connect("host=$server port=5432 dbname=$database user=$userName password=$password");
        return $connection;
    }
    
    public static function connectSql($server = false, $database = false, $userName = false, $password = false){
        $server = $server == false ? SERVER: $server;
        $database = $database == false ? DB_NAME : $database;
        $userName = $userName == false ? DB_USERNAME : $userName;
        $password = $password == false ? DB_PASSWORD : $password;
        $connection = CacheService::getMemoryCache("Connection".$server.$database);
        if($connection == false){
            $connection = self::_connectPostgreSQL($server, $userName, $password, $database);
            if ($connection) {
                CacheService::setMemoryCache("Connection".$server.$database, $connection);
            }
        }
        return $connection;
    }
    public static  function exeQuery($command, $server = false, $userName = false, $password = false, $database = false){
        $command = trim($command);
        $connection = self::connectSql($server, $database, $userName, $password);
        $result = pg_query($connection, $command);
        return $result;
    }
    private static function exeQueryAndFetchData($command){
        $arrayResult    = [];
        $result         = pg_query(self::connectSql(),$command);
        if($result!=false){
            $arrayResult = pg_fetch_all($result);
        }
        return $arrayResult;
    }
    public static function getDataQuerySelect($command){
        $cacheCommandResult = CacheService::get($command);
        if($cacheCommandResult){
            return $cacheCommandResult;
        }
        $resultData  = self::exeQueryAndFetchData($command);
        CacheService::set($command,$resultData);
        return $resultData;
    }
    
}