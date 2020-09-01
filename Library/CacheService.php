<?php
namespace Library;
class CacheService{
    private static function connect(){
        $cache=new \Memcached();
        $cache->addServer('127.0.0.1',11211);
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
        $mycache->flush();
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
