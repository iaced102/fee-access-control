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
        $listObj = Operation::getByPaging($page,$pageSize,'id ASC','');
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
                $objIdens = "'".implode("','", $objIdens)."'";
                $actions = "'".implode("','", $actions)."'";
                $savedOperations = Operation::getByTop('', " object_identifier IN ($objIdens) AND action IN ($actions)");
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
                $obj = Operation::getById(intval($this->parameters['id']));
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
                        RoleAction::refresh();
                        $this->output['status'] = STATUS_OK;
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
            $id = intval($this->parameters['id']);
            $obj = Operation::getById($id);
            if($obj!=false){
                if($obj->delete()){
                    OperationInActionPack::deleteMulti("operation_id =$id ");
                    RoleAction::refresh();
                    $this->output['status'] = STATUS_OK;
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
                    return intval($item);
                },$ids);
                Operation::deleteMulti("id in (".implode(',',$ids).')');
                OperationInActionPack::deleteMulti("operation_id in (".implode(',',$ids).')');
                RoleAction::refresh();
                $this->output['status']  = STATUS_OK;
            }
            else{
                $this->output['status'] = STATUS_BAD_REQUEST;
            }
            
        }
    }
    function detail(){
        if($this->checkParameter(['id'])){
            $obj = Operation::getById(intval($this->parameters['id']));
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
    function getListObjectIdentifier(){
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $condition = [];
        if(isset($this->parameters['type'])&&$this->parameters['type']!=''){
            $type = pg_escape_string($this->parameters['type']);
            $condition[]="type='$type'";
        }
        if(isset($this->parameters['keyword'])&&$this->parameters['keyword']!=''){
            $keyword = pg_escape_string($this->parameters['keyword']);
            $condition[] = "(name ilike '%$keyword%' OR object_identifier ilike '%$keyword%' OR title ilike '%$keyword%')";
        }
        if(isset($this->parameters['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            if(count($ids)>0){
                $condition[] = "object_identifier in ('". implode("','",$ids)."')";
            }
        }
        $conditionStr = implode(' AND ',$condition);
        $this->output['data'] = ObjectIdentifier::getByPaging($page,$pageSize,'',$conditionStr);
        $this->output['status'] = STATUS_OK;
    }
}