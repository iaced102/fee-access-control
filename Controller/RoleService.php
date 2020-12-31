<?php
namespace Controller;

use Library\AccessControl;
use Library\Auth;
use Library\Message;
use Library\Str;
use Model\PermissionPack;
use Model\PermissionRole;
use Model\Role;
use Model\RoleAction;
use Model\Users;

class RoleService extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'listPermission';
        $this->requireLogin = true;
    }
   
    function setPermission(){
        // AccessControl::filterByPermission("role","setPermission");
        if($this->checkParameter(['permission_id','role_identifier'])){
            $permissionId = intval($this->parameters['permission_id']);
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $roleType = isset($this->parameters['role_type'])?trim($this->parameters['role_type']):Role::TYPE_ORGCHART;
            if($this->setPermissionItem($roleIdentifier,$permissionId,$roleType)){
                RoleAction::refresh();
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status'] = STATUS_SERVER_ERROR;
                $this->output['message'] = 'ERROR';
            }
        }
    }
    function setPermissionBatch(){
        // AccessControl::filterByPermission("role","setPermission");
        if($this->checkParameter(['permissions'])){
            $permissions = json_decode($this->parameters['permissions'],true);
            if(is_array($permissions)){
                if(isset($this->parameters['replace_all'])&&$this->parameters['replace_all']=='1'){
                    $this->clearPermissionForRole($permissions);
                }
                foreach($permissions as $item){
                    if(is_array($item)&&isset($item['role_identifier'])&&isset($item['permission_id'])){
                        $roleIdentifier = trim($item['role_identifier']);
                        $roleType = isset($item['role_type'])?trim($item['role_type']):Role::TYPE_ORGCHART;
                        if(is_array($item['permission_id'])){
                            foreach($item['permission_id'] as $permissionitem){
                                $permissionitem = intval($permissionitem);
                                $this->setPermissionItem($roleIdentifier,$permissionitem,$roleType);
                            }
                        }
                        else{
                            $permissionId = intval($item['permission_id']);
                            $this->setPermissionItem($roleIdentifier,$permissionId,$roleType);
                        }
                    }
                }
                RoleAction::refresh();
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status'] = STATUS_BAD_REQUEST;
            }
        }
    }
    private function clearPermissionForRole($permissions){
        $listRoleIdentifier = array_unique(array_map(function($item){
            if(is_array($item)&&isset($item['role_identifier'])){
                return $item['role_identifier'];
            } 
            return '';
        },$permissions));
        PermissionRole::deleteMulti("role_identifier in ('".implode("','",$listRoleIdentifier)."')");
    }
    private function setPermissionItem($roleIdentifier,$permissionId,$roleType= Role::TYPE_ORGCHART){
        if(PermissionPack::count("id=$permissionId")>0){
            if(PermissionRole::count("role_identifier='$roleIdentifier' and permission_pack_id=$permissionId")==0){
                $obj = new PermissionRole();
                $obj->permissionPackId = $permissionId;
                $obj->roleType = $roleType;
                $obj->roleIdentifier = $roleIdentifier;
                if($obj->insert()){
                    return true;
                }
                else{
                    return false;
                }
            }
            else{
                return true;
            }
        }
        return false;
    }
    function listPermission(){
        if($this->checkParameter(['role_identifier'])){
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $listPermission = [];
            if(isset($this->parameters['detail'])&&intval($this->parameters['detail'])==1){
                $listPermission = PermissionPack::getByTop('',"permission_role.permission_pack_id=permission_pack.id AND permission_role.role_identifier='$roleIdentifier'",'',false,'permission_role');
            }
            else{
                 $listPermission = PermissionRole::getByTop('',"role_identifier='$roleIdentifier'");
            }
           
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listPermission
            ];
        }
    }
    public function getAccessControl(){
        if($this->checkParameter(['role_identifier','object_identifier'])){
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $objectIdentifier = trim($this->parameters['object_identifier']);
            $listAccessControl = RoleAction::getByTop("","role_identifier='$roleIdentifier' AND object_identifier='$objectIdentifier'");
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   
    public function getAccessControlByRole(){
        if($this->checkParameter(['role_identifier'])){
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $listAccessControl = RoleAction::getByTop("","role_identifier='$roleIdentifier'");
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   
    public function getAccessControlByRoles(){
        if($this->checkParameter(['role_identifiers'])){
            $listAccessControl=[];
            $roleIdentifiers = Str::getArrayFromUnclearData($this->parameters['role_identifiers']);
            if(is_array($roleIdentifiers) && count($roleIdentifiers)>0){
                $roleIdentifiersStr = implode("','",$roleIdentifiers);
                $listAccessControl = RoleAction::getByTop("","role_identifier IN ('$roleIdentifiersStr')");
            }
           
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   
    public function getAccessControlMultiObject(){
        if($this->checkParameter(['role_identifier','object_identifiers'])){
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $objectIdentifiers = implode("','",Str::getArrayFromUnclearData($this->parameters['object_identifiers']));
            $listAccessControl = RoleAction::getByTop("","role_identifier='$roleIdentifier' AND object_identifier in ('$objectIdentifiers')","",false,false,true);
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   
}