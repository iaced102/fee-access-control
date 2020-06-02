<?php
namespace Library;
class CacheService{
    private static function connect(){
        $cache=new Memcached();
        $cache->addServer('127.0.0.1',11211);
        return $cache;
    }
    public static function get($key){
        if((!USE_MEMCACHE)||(isset($GLOBALS['IsNoCache'])&&$GLOBALS['IsNoCache']===true)){
            return false;
        }
        $mycache=self::connect();
        $CacheResult=$mycache->get(md5($key));
     
        return $CacheResult;
    }
    public static function set($key,$value,$expired=5){
        if((!USE_MEMCACHE)||(isset($GLOBALS['IsNoCache'])&&$GLOBALS['IsNoCache']===true))
        {
            return;
        }
        $mycache=self::connect();
        $mycache->set(md5($key),$value,$expired);
    }

    public static function getMemoryCache($Key){
        if(isset($GLOBALS['MemoryCache_'.md5($Key)])){
            return $GLOBALS['MemoryCache_'.md5($Key)];
        }
        return false;
    }
    public static function setMemoryCache($Key,$Value){
        $GLOBALS['MemoryCache_'.md5($Key)]=$Value;
    }
}
