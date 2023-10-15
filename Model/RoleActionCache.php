<?php

namespace Model;

use Library\Auth;
use Library\CacheService;
use Library\Message;
use Model\Users;

class RoleActionCache
{

    public static function getKey()
    {
        return "access_control_".Auth::getTenantId()."_role_action_";
    }

    private static function getMapRows($rows)
    {
        $map = [];
        foreach ($rows as $r) {
            $roleId = $r->roleIdentifier;
            $objId = $r->objectIdentifier;

            if(!isset($map[$roleId])){
                $map[$roleId] = [];
            }

            if(!isset($map[$roleId][$objId])){
                $map[$roleId][$objId] = [];
            }

            $map[$roleId][$objId][] = $r;
        }

        $rsl = [];
        foreach ($map as $roleId => $data) {
            $rsl[$roleId] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return $rsl;
    }

    /**
     * Set dữ liệu của các row của Role action vào cache
     */
    public static function set(array $rows)
    {
        $map = self::getMapRows($rows);
        $cache = CacheService::connect($map);
        foreach ($map as $role => $data) {
            $cache->hSet(self::getKey(), $role, $data);
        }
    }


    /**
     * Lấy danh sách các dòng RoleAction trong cache theo 
     * @param roleId string : mảng các role Ids
     * @param objIds []sting : mảng các obj id
     * @param actions []sting : mảng các action cần lấy
     */
    public static function get($roleId ,array $objIds = [],array $action = [])
    {

        $cache = CacheService::connect();
        $allOps = $cache->hGet(self::getKey(), $roleId);
        if($allOps === false){
            return [];
        }

        $allOps = json_decode($allOps, true);
        $rsl = [];
        if(count($objIds) == 0){
            foreach ($allOps as $objId => $ops) {
                $rsl = array_merge($rsl, $ops);
            }
            return $rsl;
        }

        foreach ($objIds as $objId) {
            if(isset($allOps[$objId])){
                $ops = $allOps[$objId];
                if(count($action) == 0){
                    $rsl = array_merge($rsl, $ops);
                }else{
                    foreach ($ops as $op) {
                        if(array_search($op['action'], $action)){
                            $rsl[] = $op;
                        }
                    }
                }
            }
        }
        return $rsl;
    }

    /**
     * Clear toàn bộ cache của role action
     */
    public static function clearAll()
    {
        $cache = CacheService::connect();
        $cache->del(self::getKey());
    }
}