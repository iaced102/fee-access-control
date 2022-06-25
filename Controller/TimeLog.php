<?php
namespace Controller;

class TimeLog extends Controller
{
    static $logs = [];

    public static function start($key, $data = [])
    {
        self::$logs[$key] = [
            'time'  => microtime(true),
            'data'  => $data
        ];
    }

    public static function end($key, $data = [])
    {
        $info = &self::$logs[$key];
        $info['time'] = microtime(true) - self::$logs[$key]['time'];
        if(is_array($data)){
            $info['data'] = array_merge($info['data'], $data);
        }
        
    }

    public static function get($key)
    {
        return self::$logs[$key];
    }

    public static function getAll()
    {
        $rsl = self::$logs;
        $sumTime = 0;
        foreach ($rsl as $key => $item) {
            $sumTime += $item['time'];
        }
        $rsl['__sumarize__'] = [
            'time'  => $sumTime
        ];
        return $rsl;
    }

    public static function clear()
    {
        self::$logs = [];
    }
}