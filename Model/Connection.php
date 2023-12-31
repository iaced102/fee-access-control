<?php

namespace Model;

use library\CacheService;
use Library\Str;
use Library\Test;

class Connection
{
    private static function _connectPostgreSQL($server, $userName, $password, $database)
    {
        $connection = pg_connect("host=$server port=5432 dbname=$database user=$userName password=$password");
        return $connection;
    }

    public static function connectSql($server = false, $database = false, $userName = false, $password = false)
    {
        if ($server != false) {
            $server = $server;
        } else {
            $server = $GLOBALS['env']['db']['postgresql']['host'];
            $server = explode(",", $server);
            $server = $server[array_rand($server)];
        }
        $database = $database == false ? $GLOBALS['env']['db']['postgresql']['dbname'] : $database;
        $userName = $userName == false ? $GLOBALS['env']['db']['postgresql']['username'] : $userName;
        $password = $password == false ? $GLOBALS['env']['db']['postgresql']['password'] : $password;
        $connection = CacheService::getMemoryCache("Connection" . $server . $database);
        if ($connection == false) {
            $connection = self::_connectPostgreSQL($server, $userName, $password, $database);
            if ($connection) {
                CacheService::setMemoryCache("Connection" . $server . $database, $connection);
            }
        }
        return $connection;
    }
    public static  function exeQuery($command, $server = false, $userName = false, $password = false, $database = false)
    {
        $resultTest = Test::callFunction(Test::FUNC_EXE_QUERY_DB, $command);
        if ($resultTest !== Test::FUNC_NO_AVAILABLE) {
            return $resultTest;
        }
        $command = trim($command);
        $command = self::checkJoinTableForTenant($command);
        $connection = self::connectSql($server, $database, $userName, $password);
        $result = pg_query($connection, $command);
        return $result;
    }
    public static  function prepareExeQuery($command, $dataBindings, $server = false, $userName = false, $password = false, $database = false)
    {
        $command = trim($command);
        $command = self::checkJoinTableForTenant($command);
        $connection = self::connectSql($server, $database, $userName, $password);
        $randomStr = Str::generateRandomString(5);
        $result = pg_prepare($connection, $randomStr, $command);
        $result = pg_execute($connection, $randomStr, $dataBindings);
        return $result;
    }
    public static function exeQueryAndFetchData($command)
    {
        $resultTest = Test::callFunction(Test::FUNC_QUERY_DB, $command);
        if ($resultTest !== Test::FUNC_NO_AVAILABLE) {
            return $resultTest;
        }
        $arrayResult    = [];
        $result         = pg_query(self::connectSql(), $command);
        if ($result != false) {
            $arrayResult = pg_fetch_all($result);
        }
        return $arrayResult;
    }
    private static function prepareAndExecuteQueryAndFetchData($command, $dataBindings)
    {
        $arrayResult    = [];
        $connection = self::connectSql();
        $randomStr = Str::generateRandomString(5);
        $result = pg_prepare($connection, $randomStr, $command);
        $result = pg_execute($connection, $randomStr, $dataBindings);
        if ($result != false) {
            $arrayResult = pg_fetch_all($result);
        }
        return $arrayResult;
    }

    public static function getDataQuerySelect($command, $dataBindings = [])
    {
        $command = self::checkJoinTableForTenant($command);
        if (count($dataBindings) > 0) {
            $resultData = self::prepareAndExecuteQueryAndFetchData($command, $dataBindings);
        } else {
            $resultData = self::exeQueryAndFetchData($command);
        }
        return $resultData;
    }

    public static function getLastError()
    {
        $lastError = pg_last_error(self::connectSql());
        if ($lastError !== false) {
            return $lastError;
        } else {
            return '';
        }
    }

    // nếu có join trong câu lệnh thì bổ sung điều kiện trên tenant
    private static function checkJoinTableForTenant($sql)
    {
        $sqlCheck = $sql;
        $sqlCheck = trim($sqlCheck);
        preg_match('/^insert|^update/i', $sqlCheck, $o);
        if (count($o) > 0) {
            return $sql;
        }
        $newSql = $sql;
        preg_match_all('/[a-zA-Z0-9_"]+\.[a-zA-Z0-9_:"]+\s*=\s*[a-zA-Z0-9_"]+\.[a-zA-Z0-9_:"]+/', $sql, $output_array);
        if (count($output_array) > 0) {
            $allMatch = $output_array[0];
            $c = count($allMatch);
            if ($c > 0) {
                for ($i = 0; $i < $c; $i++) {
                    $onClause = $allMatch[$i];
                    preg_match_all('/\w*\./i', $onClause, $listTable);
                    $tenantClause = $listTable[0][0] . "tenant_id_ = " . $listTable[0][1] . "tenant_id_";
                    $clauseReplace = $onClause . " AND " . $tenantClause;
                    $newSql = str_replace($onClause, $clauseReplace, $newSql);
                }
            }
        }
        return $newSql;
    }
}
