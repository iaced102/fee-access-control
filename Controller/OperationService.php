<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Str;
use Model\ObjectIdentifier;
use Model\Operation;
use Model\RoleAction;
use Model\OperationInActionPack;
use Model\PermissionRole;
use Model\Users;

class OperationService extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }
    function list(){
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $listObj = Operation::getByPaging($page,$pageSize,'id ASC');
        $this->output = [
            'status'=>STATUS_OK,
            'data' => $listObj
        ];   
    }
    function create(){
        if($this->checkParameter(['name','action','objectIdentifier'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['objectIdentifier'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","objectIdentifier" may not be blank';
            }
            else{
                $obj =  new Operation();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->action = trim($this->parameters['action']);
                $obj->objectName = isset($this->parameters['objectName'])?trim($this->parameters['objectName']):'';
                $obj->objectType = isset($this->parameters['objectType'])?trim($this->parameters['objectType']):'';
                $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Operation::STATUS_ENABLE;
                $obj->insert();
                $this->output['data'] = $obj;
                $this->output['status'] = STATUS_OK;
            }
        }
    
    }
    
    function saveBatch(){
        if($this->checkParameter(['operations'])){
            $listObj = [];
            $operations = Str::getArrayFromUnclearData($this->parameters['operations']);
            $mapOperations = [];
            $objIdens = [];
            $actions = [];

            foreach($operations as &$item){
                if(isset($item['name'])&&isset($item['action'])&&$item['objectIdentifier']){
                    $mapOperations[$item['objectIdentifier'].$item['action']] = $item;
                    $objIdens[$item['objectIdentifier']] = true;
                    $actions[$item['action']] = true;
                    // ----------------------------------------------------------------------- //
                    // $action = pg_escape_string($item['action']);
                    // $objectIdentifier = pg_escape_string($item['objectIdentifier']);
                    // $listNewObj = Operation::getByTop(1,"action='$action' AND object_identifier='$objectIdentifier'");
                    // $newObj =  new Operation();
                    // if(count($listNewObj)>0){
                    //     $newObj = $listNewObj[0];
                    // }
                    // $newObj->name = trim(pg_escape_string($item['name']));
                    // $newObj->description = isset($item['description'])?trim($item['description']):'';
                    // $newObj->action = trim($item['action']);
                    // $newObj->objectName = isset($item['objectName'])?trim($item['objectName']):'';
                    // $newObj->objectType = isset($item['objectType'])?trim($item['objectType']):'';
                    // $newObj->objectIdentifier = trim($item['objectIdentifier']);
                    // $newObj->status = Operation::STATUS_ENABLE;
                    // $newObj->save();
                    // $listObj[] = $newObj;
                
                }
            }

            $objIdens = array_keys($objIdens);
            $actions = array_keys($actions);

            if(count($actions) > 0 && count($objIdens) > 0){
                $objIdens = "{".implode(",", $objIdens)."}";
                $actions = "{".implode(",", $actions)."}";
                $where = ["conditions" => "object_identifier =ANY($1) AND action =ANY($2)", "dataBindings" => [$objIdens,$actions]];
                $savedOperations = Operation::getByStatements('', $where);
                if(is_array($savedOperations)){
                    foreach ($savedOperations as $opr) {
                        if(isset($mapOperations[$opr->objectIdentifier.$opr->action])){
                            unset($mapOperations[$opr->objectIdentifier.$opr->action]);
                            $listObj[] = $opr;
                        }
                    }
                }

                $insertOperations = array_values($mapOperations);
                if(count($insertOperations) > 0){
                    foreach ($insertOperations as $item) {
                        $start = microtime(true);
                        $newObj =  new Operation();
                        $newObj->name = trim(pg_escape_string($item['name']));
                        $newObj->description = isset($item['description'])?trim($item['description']):'';
                        $newObj->action = trim(pg_escape_string($item['action']));
                        $newObj->objectName = isset($item['objectName'])?trim($item['objectName']):'';
                        $newObj->objectType = isset($item['objectType'])?trim($item['objectType']):'';
                        $newObj->objectIdentifier = trim(pg_escape_string($item['objectIdentifier']));
                        $newObj->status = Operation::STATUS_ENABLE;
                        $newObj->insert();
                        $listObj[] = $newObj;
                    }
                }
            }
            $this->output['data']= $listObj;
            $this->output['status'] = STATUS_OK;
        }
    }
    function update(){
        if($this->checkParameter(['id','name','action','objectIdentifier'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['objectIdentifier'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","objectIdentifier" may not be blank';
            }
            else{
                $obj = Operation::getById($this->parameters['id']);
                if($obj!=false){
                    $obj =  new Operation();
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->action = trim($this->parameters['action']);
                    $obj->objectName = isset($this->parameters['objectName'])?trim($this->parameters['objectName']):'';
                    $obj->objectType = isset($this->parameters['objectType'])?trim($this->parameters['objectType']):'';
                    $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Operation::STATUS_ENABLE;
                    if($obj->update()){
                        $this->output['status'] = STATUS_OK;
                        RoleAction::closeConnectionAndRefresh($this);
                    }
                    else{
                        $this->output['status'] = STATUS_SERVER_ERROR;
                    }
                    
                }
                else{
                    $this->output['status'] = STATUS_NOT_FOUND;
                    $this->output['message'] = 'operation not found';
                }
            }
        }
    }
    function delete(){
        if($this->checkParameter(['id'])){
            $id = $this->parameters['id'];
            $obj = Operation::getById($id);
            if($obj!=false){
                if($obj->delete()){
                    OperationInActionPack::deleteMulti("operation_id =$1 ",[$id]);
                    $this->output['status'] = STATUS_OK;
                    RoleAction::closeConnectionAndRefresh($this);
                }
                else{
                    $this->output['status'] = STATUS_SERVER_ERROR;
                }
                
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'operation not found';
            }
        }
    }
    function deleteMany(){
        if($this->checkParameter(['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            if(count($ids)>0){
                $ids = array_map(function($item){
                    return $item;
                },$ids);
                $ids = "{".implode(",", $ids)."}";
                Operation::deleteMulti("id = ANY($1)",[$ids]);
                OperationInActionPack::deleteMulti("operation_id = ANY($1)",[$ids]);
                $this->output['status']  = STATUS_OK;
                RoleAction::closeConnectionAndRefresh($this);
            }
            else{
                $this->output['status'] = STATUS_BAD_REQUEST;
            }
            
        }
    }
    function detail(){
        if($this->checkParameter(['id'])){
            $obj = Operation::getById($this->parameters['id']);
            if($obj!=false){
                $this->output['data']   = $obj;
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'operation not found';
            }
        }
    }
    function getActionByObjectType(){
        if($this->checkParameter(['type'])){
            $this->output['data']= Operation::getListActionByType($this->parameters['type']);
            $this->output['status'] = count($this->output['data'])>0 ? STATUS_OK : STATUS_NOT_FOUND;
        }
    }
    function getListType(){        
        $this->output['data']= Operation::$listAction;
        $this->output['status'] =  STATUS_OK;
    }
    function getListObjIden(){
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $condition = [];
        $dataBindings=[];
        if(isset($this->parameters['type'])&&$this->parameters['type']!=''){
            $type = pg_escape_string($this->parameters['type']);
            $condition[]="type=$1";
            $dataBindings[]=$type;
        }
        if(isset($this->parameters['keyword'])&&$this->parameters['keyword']!=''){
            $keyword = pg_escape_string($this->parameters['keyword']);
            $condition[] =count($dataBindings) >0 ? "(name ilike $2 OR object_identifier ilike $2 OR title ilike $2)" 
                                                  : "(name ilike $1 OR object_identifier ilike $1 OR title ilike $1)" ;
            $dataBindings[]="%$keyword%";
        }
        if(isset($this->parameters['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            if(count($ids)>0){
                $strIds = '{'.implode(",",$ids).'}';
                $condition[] = count($dataBindings) ==0 ? "object_identifier = ANY($1)" 
                                :( count($dataBindings) ==1 ? "object_identifier = ANY($2)"
                                : "object_identifier = ANY($3)");
                $dataBindings[]=$strIds;
            }
        }
        $conditionStr = implode(' AND ',$condition);
        $where = ["conditions" => $conditionStr, "dataBindings" => $dataBindings];
        $data = ObjectIdentifier::getByPaging($page,$pageSize,'',$where,false, false, true);
        $this->output['data'] = $data;
        $this->output['status'] = STATUS_OK;
    }
    
    function getListObjectIdentifierMultiTenant(){
        Auth::ignoreTokenInfo();
        self::getListObjIden();
    }

    function getListObjectIdentifier(){
        self::getListObjIden();
    }


    function getOperationByObjectAndRole(){
        if($this->checkParameter(['objectType','role'])){
            $objectType = $this->parameters['objectType'];
            $role = $this->parameters['role'];
            $where = ["conditions" => "role_identifier = $1 AND object_type = $2", "dataBindings" => [$role,$objectType]];
            $operations = RoleAction::getByStatements('',$where);
            $this->output=[
                'status'=>STATUS_OK,
                'message'=>'OK',
                'data'=>$operations
            ];
        } else {
            $this->output=[
                'status'=>STATUS_BAD_REQUEST,
                'message'=>'missing objectType or role',
            ];
        }

    }
}