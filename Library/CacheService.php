<?php
namespace Library;
class CacheService{
    private static $cacheInstance = null;
    private static function connect(){
        $cache = null;
        if(is_null(self::$cacheInstance)){
            if(CACHE_ENGINE == 'memcache'){
                $cache=new \Memcached();
                $cache->addServer('127.0.0.1',11211);
            }else {
                $cache=new \Redis();
                $cache->connect('127.0.0.1', 6379);
            }
            self::$cacheInstance = $cache;
        }else{
            $cache = self::$cacheInstance;
        }
        return $cache;
    }
    /*
    * thứ tự ưu tiên của các biến cấu hình cache( ưu tiên từ cao xuống thấp)
    * $force: nếu set force=true thì mặc định lấy cache,
    * USE_MEMCACHE: define = false thì return  luôn, nếu USE_MEMCACHE=true thì xét tiếp $GLOBALS['IsNoCache'] ==> hằng số này mang tính cấu hình toàn cục cho cả project
    *  $GLOBALS['IsNoCache']: nếu USE_MEMCACHE=true, và $GLOBALS['IsNoCache']=true, vì bỏ qua cache. $GLOBALS['IsNoCache'] mang tính cục bộ bỏ qua cache khi project có set cache
    */
    public static function get($key,$force=false){
        if(
            $force==false 
            && (
                (!USE_MEMCACHE)
                || (
                    isset($GLOBALS['IsNoCache'])
                    && $GLOBALS['IsNoCache']===true
                    )
                )
        ){
            return false;
        }
        $mycache=self::connect();
        $CacheResult=$mycache->get(md5($key));
     
        return $CacheResult;
    }
    public static function clear(){
        $mycache=self::connect();
        if(CACHE_ENGINE == 'memcache'){
            $mycache->flush();
        }else{
            $mycache->flushDB();
        }
    }
    /*
    * thứ tự ưu tiên của các biến cấu hình cache( ưu tiên từ cao xuống thấp)
    * $force: nếu set force=true thì mặc định lấy cache,
    * USE_MEMCACHE: define = false thì return  luôn, nếu USE_MEMCACHE=true thì xét tiếp $GLOBALS['IsNoCache'] ==> hằng số này mang tính cấu hình toàn cục cho cả project
    *  $GLOBALS['IsNoCache']: nếu USE_MEMCACHE=true, và $GLOBALS['IsNoCache']=true, vì bỏ qua cache. $GLOBALS['IsNoCache'] mang tính cục bộ bỏ qua cache khi project có set cache
    */
    public static function set($key,$value,$expired=0,$force=false){
        if(
            $force==false 
            && (
                (!USE_MEMCACHE)
                || (
                    isset($GLOBALS['IsNoCache'])
                    && $GLOBALS['IsNoCache']===true
                    )
                )
        ){
            return;
        }
        $mycache=self::connect();
        if(CACHE_ENGINE == 'memcache'){
            $mycache->set(md5($key),$value,$expired);
        }else{
            if($expired > 0){
                $mycache->setex(md5($key),$expired, $value);
            }else{
                $mycache->set(md5($key), $value);
            }
        }
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