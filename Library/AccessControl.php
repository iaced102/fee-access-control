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
    /*
    * Lấy check flag trong memcache, nếu chưa có thì get về memcache, rồi get memcache trả về.
    */
    public static function checkRoleActionRemote($roleIdentifier,$objectIdentifier,$action){
        $key = json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'action'=>$action]);
        if(self::checkMemcache($roleIdentifier,$objectIdentifier)!==false){
            self::getRoleActionRemote($roleIdentifier,$objectIdentifier);    
        }
        return CacheService::get($key);
        
    }
    /*
    * Lấy quyền về memcache, và set flag là đã lấy bất kể là có quyền hoặc không.
    */
    public static function getRoleActionRemote($roleIdentifier,$objectIdentifier){
        $listAction = []; 
        $dataResponse = Request::request(Request::API_ACCESS_CONTROL."/roles/$roleIdentifier/accesscontrol/$objectIdentifier");
        if(is_array($dataResponse)&&isset($dataResponse['status']) && $dataResponse['status']==STATUS_OK && isset($dataResponse['data'])){
            if(is_array($dataResponse['data']) && count($dataResponse['data'])>0){
                foreach($dataResponse['data'] as $accessControl){
                    $keyAccessControl = json_encode(['role'=>$accessControl['role_identifier'],'object'=>$accessControl['object_identifier'],'action'=>$accessControl['action']]);
                    CacheService::set($keyAccessControl,$accessControl['status']);
                    $listAction[]=$accessControl['action'];
                }
            }
        }
        CacheService::set(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]),$listAction);
    }
     /*
    * Lấy quyền về memcache all 
    */
    public static function getRoleActionRemoteAllObject($roleIdentifier){
        $listAction = []; 
        $dataResponse = Request::request(Request::API_ACCESS_CONTROL."/roles/$roleIdentifier/accesscontrol");
        if(is_array($dataResponse)&&isset($dataResponse['status']) && $dataResponse['status']==STATUS_OK && isset($dataResponse['data'])){
            if(is_array($dataResponse['data']) && count($dataResponse['data'])>0){
                foreach($dataResponse['data'] as $accessControl){
                    $keyAccessControl = json_encode(['role'=>$accessControl['role_identifier'],'object'=>$accessControl['object_identifier'],'action'=>$accessControl['action']]);
                    CacheService::set($keyAccessControl,$accessControl['status']);
                    if(!isset($listAction[$accessControl['object_identifier']])){
                        $listAction[$accessControl['object_identifier']]=[];
                    }
                    $listAction[$accessControl['object_identifier']][]=$accessControl['action'];
                }
            }
        }
        foreach($listAction as $object=>$actionsByObject){
            CacheService::set(json_encode(['role'=>$roleIdentifier,'object'=>$object]),$actionsByObject);
        }
        
    }
    /*
    *check xem đã được sync về chưa. Khác với đã lấy về nhưng không có quyền
    */
    public static function checkMemcache($roleIdentifier,$objectIdentifier){
        return CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));
    }
    /*
    * get List Action by Object Identifier
    */
    public static function getListAction($objectIdentifier){
        $roleIdentifier = Auth::getCurrentRole();
        $listActionInMemcache = CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));
        if($listActionInMemcache===false){
            self::getRoleActionRemote($roleIdentifier,$objectIdentifier);    
            return CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));//self::getListAction($roleIdentifier,$objectIdentifier);
        }
        return $listActionInMemcache;
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