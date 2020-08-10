<?php
namespace Library;

use Controller\Controller;
use Model\Model;
use Model\RoleAction;

class AccessControl{ 
    public static function checkPermission($objectIdentifier,$action){
        if(CacheService::getMemoryCache("permission:$objectIdentifier$action")==false){
            $currentRole = Auth::getCurrentRole();
            if($currentRole!=false){
                $roleAction = RoleAction::getByTop(1,"role_identifier='$currentRole' AND object_identifier='$objectIdentifier' AND action='$action'");
                if(count($roleAction)>0){
                    if($roleAction[0]->status==1){
                        CacheService::setMemoryCache("permission:$objectIdentifier$action",true);
                        return true;
                    }
                }
            }
            CacheService::setMemoryCache("permission:$objectIdentifier$action",false);
            return false;
        }
        else{
            return CacheService::getMemoryCache("permission:$objectIdentifier$action");
        }
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