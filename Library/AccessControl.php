<?php
namespace Library;

use Controller\Controller;
use Model\Model;
use Model\RoleAction;

class AccessControl{ 
    public static $variables = [];
    public static function checkPermission($objectIdentifier,$action,$variables=[]){
        $variables = array_merge(self::$variables,$variables);
        $objectIdentifier = Str::bindDataToString($objectIdentifier,$variables); 
        $currentRole = Auth::getCurrentRole();
        //
        return self::getRoleActionLocal($currentRole,$objectIdentifier,$action);
    }
    public static function getRoleActionLocal($roleIdentifier,$objectIdentifier,$action){
        $key = json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'action'=>$action]);
        if(CacheService::getMemoryCache($key)==false){
            $roleAction = RoleAction::getByTop(1,"role_identifier='$roleIdentifier' AND object_identifier='$objectIdentifier' AND action='$action'");
            if(count($roleAction)>0){
                if($roleAction[0]->status==1){
                    CacheService::setMemoryCache($key,true);
                    return true;
                }
            }
            CacheService::setMemoryCache($key,false);
            return false;
        }
        else{
            return CacheService::getMemoryCache($key);
        }
        
        
    }
    public static function checkActionWithCurrentRole($objectIdentifier,$action){
        if(Auth::isBa()){
            return true;    
        }
        else{
            $roleIdentifier = Auth::getCurrentRole();
            return self::checkRoleActionRemote($roleIdentifier,$objectIdentifier,$action);
        }
        
    }
    /*
    * Lấy check flag trong memcache, nếu chưa có thì get về memcache, rồi get memcache trả về.
    */
    public static function checkRoleActionRemote($roleIdentifier,$objectIdentifier,$action){
        $key = json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'action'=>$action]);
        if(!self::checkMemcache($roleIdentifier,$objectIdentifier)){
            self::getRoleActionRemote($roleIdentifier,$objectIdentifier);    
        }
        return CacheService::get($key);
        
    }
    /*
    * Lấy quyền về memcache, và set flag là đã lấy bất kể là có quyền hoặc không.
    */
    public static function getRoleActionRemote($roleIdentifier,$objectIdentifier){
        $dataResponse = Request::request(Request::API_ACCESS_CONTROL."/roles/$roleIdentifier/accesscontrol/$objectIdentifier");
        if(is_array($dataResponse)&&isset($dataResponse['status']) && $dataResponse['status']==STATUS_OK && isset($dataResponse['data'])){
            if(is_array($dataResponse['data']) && count($dataResponse['data'])>0){
                foreach($dataResponse['data'] as $accessControl){
                    $keyAccessControl = json_encode(['role'=>$accessControl['roleIdentifier'],'object'=>$accessControl['objectIdentifier'],'action'=>$accessControl['action']]);
                    CacheService::set($keyAccessControl,$accessControl['status']);
                }
            }
        }
        
        CacheService::set(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'flag'=>true]),true);
    }
    /*
    *check xem đã được sync về chưa. Khác với đã lấy về nhưng không có quyền
    */
    public static function checkMemcache($roleIdentifier,$objectIdentifier){
        return CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'flag'=>true]));
    }
    
    public static function filterByPermission($objectIdentifier,$action){
        if(self::checkPermission($objectIdentifier,$action)){
            return true;
        }
        else{
            header('Content-Type: application/json');
            $output = [
                'status'=>STATUS_PERMISSION_DENIED,
                'message'=> Message::getStatusResponse(STATUS_PERMISSION_DENIED)
            ];
            print json_encode($output);
            exit;
        }
    }
}