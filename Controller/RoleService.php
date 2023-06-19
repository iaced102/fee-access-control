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
            $permissionId = $this->parameters['permission_id'];
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $roleType = isset($this->parameters['role_type'])?trim($this->parameters['role_type']):Role::TYPE_ORGCHART;
            if($this->setPermissionItem($roleIdentifier,$permissionId,$roleType)){
                $this->output['status'] = STATUS_OK;
                RoleAction::closeConnectionAndRefresh($this);
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
                $backupItems = $this->clearPermissionForRole($permissions);
                try {
                    foreach($permissions as $item){
                        if(is_array($item)&&isset($item['role_identifier'])&&isset($item['permission_id'])){
                            $roleIdentifier = trim($item['role_identifier']);
                            $roleType = isset($item['role_type'])?trim($item['role_type']):Role::TYPE_ORGCHART;
                            if(is_array($item['permission_id'])){
                                foreach($item['permission_id'] as $permissionitem){
                                    $permissionitem = $permissionitem;
                                    $this->setPermissionItem($roleIdentifier,$permissionitem,$roleType);
                                }
                            }
                            else{
                                $permissionId = ($item['permission_id']);
                                $this->setPermissionItem($roleIdentifier,$permissionId,$roleType);
                            }
                        }
                    }
                    $this->output['status'] = STATUS_OK;
                } catch (\Throwable $th) {
                    PermissionRole::insertBulk($backupItems);
                }
                RoleAction::closeConnectionAndRefresh($this);
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
        $listRoleIdentifier = '{'.implode(",",$listRoleIdentifier).'}';
        $where = ["conditions" => "role_identifier = ANY($1)", "dataBindings" => [$listRoleIdentifier]];
        $backupItem = PermissionRole::getByStatements('', $where);
        PermissionRole::deleteMulti($where['conditions'],$where['dataBindings']);
        return $backupItem;
    }
    private function setPermissionItem($roleIdentifier,$permissionId,$roleType= Role::TYPE_ORGCHART){
        if(PermissionPack::count("id=$1",[$permissionId])>0){
            if(PermissionRole::count("role_identifier=$1 and permission_pack_id=$2",[$roleIdentifier,$permissionId])==0){
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
                $where = ["conditions" => "permission_role.permission_pack_id=permission_pack.id AND permission_role.role_identifier=$1", "dataBindings" => [$roleIdentifier]];
                $listPermission = PermissionPack::getByStatements('',$where,'',false,'permission_role');
            }
            else{
                $where = ["conditions" => "role_identifier = $1", "dataBindings" => [$roleIdentifier]];
                $listPermission = PermissionRole::getByStatements('',$where);
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
            $listAccessControl = [];
            if($roleIdentifier == 'auto'){
                $user = Auth::getDataToken();
                $allRoles = $user['allRoles'];
                if(count($allRoles) > 0){
                    $allRoles = "{".implode(",", $allRoles)."}";
                    $where = ["conditions" => "role_identifier = ANY($1) AND object_identifier=$2", "dataBindings" => [$allRoles,$objectIdentifier]];
                    $listAccessControl = RoleAction::getByStatements("",$where);
                    self::standardRoleActionFilterValue($listAccessControl);
                    foreach ($listAccessControl as &$item) {
                        $item->originRoleIdentifier = $item->roleIdentifier;
                        $item->roleIdentifier = 'auto';
                    }
                }
            }else{
                $where = ["conditions" => "role_identifier=$1 AND object_identifier=$2", "dataBindings" => [$roleIdentifier,$objectIdentifier]];
                $listAccessControl = RoleAction::getByStatements("",$where);
                self::standardRoleActionFilterValue($listAccessControl);
            }
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   
    public function getAccessControlByRole(){
        if($this->checkParameter(['role_identifier'])){
            $roleIdentifier = trim($this->parameters['role_identifier']);
            $listAccessControl = [];
            if($roleIdentifier == 'auto'){
                $user = Auth::getDataToken();
                $allRoles = isset($user['allRoles']) ? $user['allRoles'] : [];
                if(count($allRoles) > 0){
                    $allRoles = "{".implode(",", $allRoles)."}";
                    $where = ["conditions" => "role_identifier = ANY($1)", "dataBindings" => [$allRoles]];
                    $listAccessControl = RoleAction::getByStatements("",$where);
                    self::standardRoleActionFilterValue($listAccessControl);
                    foreach ($listAccessControl as &$item) {
                        $item->originRoleIdentifier = $item->roleIdentifier;
                        $item->roleIdentifier = 'auto';
                    }
                }
            }else{
                $where = ["conditions" => "role_identifier = $1", "dataBindings" => [$roleIdentifier]];
                $listAccessControl = RoleAction::getByStatements("",$where);
                self::standardRoleActionFilterValue($listAccessControl);
            }
            $this->output = [
                'status'=> STATUS_OK,
                'data'  => $listAccessControl
            ];
        }
    }
    public function getAccessControlByRoles(){
        if($this->checkParameter(['role_identifiers'])){
            $listAccessControl=[];
            $roleIdentifiers = Str::getArrayFromUnclearData($this->parameters['role_identifiers']);
            if(is_array($roleIdentifiers) && count($roleIdentifiers)>0){
                $roleIdentifiersStr = '{'.implode(",",$roleIdentifiers).'}';
                $where = ["conditions" => "role_identifier = ANY($1)", "dataBindings" => [$roleIdentifiersStr]];
                $listAccessControl = RoleAction::getByStatements("",$where);
                self::standardRoleActionFilterValue($listAccessControl);
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
            $objectIdentifiers = '{'.implode(",",Str::getArrayFromUnclearData($this->parameters['object_identifiers'])).'}';
            $where = ["conditions" => "role_identifier=$1 AND object_identifier = ANY($2)", "dataBindings" => [$roleIdentifier,$objectIdentifiers]];
            $listAccessControl = RoleAction::getByStatements("",$where,"",false,false,true);
            self::standardRoleActionFilterValue($listAccessControl);
            $this->output = [
                'status'=>STATUS_OK,
                'data' =>$listAccessControl
            ];
        }
    }   

    public static function standardRoleActionFilterValue(&$roleActionArr)
    {
        foreach ($roleActionArr as &$ra) {
            $ra->filter = ($ra->filterNew != '' && !is_null($ra->filterNew )) ? $ra->filterNew :$ra->filter;
        }
        if (isset($authData['filter'])) {
            $authData = Auth::getDataToken();
            $f = $authData['filter'];
            $roleActionArr[] = new RoleAction([
                'objectIdentifier'  =>  $f['object'],
                'action'            =>  $f['action'],
                'objectType'        =>  $f['objectType'],
                'name'              =>  'symper_filter_on_token',
                'roleIdentifier'    =>  $authData['role'],
                'filter'            =>  $f['plainFilter'],
                'filterNew'         =>  $f['plainFilter'],
                'status'            =>  1,
                'actionPackId'      =>  'symper_filter_on_token',
                'filterCombination' =>  $f['plainFilter'],
                'tenantId'          =>  $authData['tenantId']
            ]);
        }
    }
}