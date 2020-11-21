<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Str;
use Model\ActionPack;
use Model\Operation;
use Model\OperationInActionPack;
use Model\PermissionRole;
use Model\RoleAction;
use Model\Users;
class ActionPackService extends Controller
{
    
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }
   /**
    * @operation("pack","list")
    * @operation("pack","list2")
    */
    public function  list(){
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $listObj = ActionPack::getByPaging($page,$pageSize,'id ASC','');
        $this->output = [
            'status'=>STATUS_OK,
            'data' => $listObj
        ];   
    }
    function create(){
        if($this->checkParameter(['name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj =  new ActionPack();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):ActionPack::STATUS_ENABLE;
                $obj->userCreate = Auth::getCurrentUserId();
                $obj->userUpdate = Auth::getCurrentUserId();
                $obj->createAt = date(DATETIME_FORMAT);
                $obj->updateAt = date(DATETIME_FORMAT);
                $obj->insert();
                if(isset($this->parameters['listOperations'])){
                    $listOperations = Str::getArrayFromUnclearData($this->parameters['listOperations']);
                    $obj->saveOperation($listOperations);
                }
                
                $this->output['status'] = STATUS_OK;
            }
        }
    
    }
    function update(){
        if($this->checkParameter(['id','name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj = ActionPack::getById(intval($this->parameters['id']));
                if($obj!=false){
                   
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):ActionPack::STATUS_ENABLE;
                    $obj->userUpdate = Auth::getCurrentUserId();
                    $obj->updateAt = date(DATETIME_FORMAT);
                    if($obj->update()){
                        if(isset($this->parameters['listOperations'])){
                            $listOperations = Str::getArrayFromUnclearData($this->parameters['listOperations']);
                            $obj->saveOperation($listOperations);
                        }
                        RoleAction::refresh();
                        $this->output['status'] = STATUS_OK;
                    }
                    else{
                        $this->output['status'] = STATUS_SERVER_ERROR;
                    }
                    
                }
                else{
                    $this->output['status'] = STATUS_NOT_FOUND;
                    $this->output['message'] = 'ActionPack not found';
                }
            }
        }
    }
    function delete(){
        if($this->checkParameter(['id'])){
            $obj = ActionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                if($obj->delete()){
                    RoleAction::refresh();
                    $this->output['status'] = STATUS_OK;
                }
                else{
                    $this->output['status'] = STATUS_SERVER_ERROR;
                }
                
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'ActionPack not found';
            }
        }
    }
    function detail(){
        if($this->checkParameter(['id'])){
            $obj = ActionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                $this->output['data']   = $obj;
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'ActionPack not found';
            }
        }
    }
    function listOperation(){
        if($this->checkParameter(['id'])){
            $obj = ActionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                $listObj = Operation::getByTop('',"operation_in_action_pack.action_pack_id=".$obj->id." and operation_in_action_pack.operation_id=operation.id","","operation.*","operation_in_action_pack");
                $this->output['data']   = $listObj;
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'ActionPack not found';
            }
        }
    }
    function addOperation(){
        if($this->checkParameter(['id','operationId'])){
            $obj = ActionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                if(Operation::count("id=".intval($this->parameters['operationId']))>0){
                    if(OperationInActionPack::count("action_pack_id=".intval($this->parameters['id'])." and operation_id=".intval($this->parameters['operationId']))==0){
                        $operationInActionPackObj =  new OperationInActionPack();
                        $operationInActionPackObj->actionPackId = $obj->id;
                        $operationInActionPackObj->operationId = intval($this->parameters['operationId']);
                        $operationInActionPackObj->save();
                    }
                    RoleAction::refresh();
                    $this->output['status'] = STATUS_OK;
                }
                else{
                    $this->output['status']     = STATUS_NOT_FOUND;
                    $this->output['message']    = 'Operation not found';
                }
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'ActionPack not found';
            }
        }
    }
    function removeOperation(){
        if($this->checkParameter(['id','operationId'])){
            $obj = ActionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                if(OperationInActionPack::count("action_pack_id=".intval($this->parameters['id'])." and operation_id=".intval($this->parameters['operationId']))>0){
                    OperationInActionPack::deleteMulti("action_pack_id=".intval($this->parameters['id'])." and operation_id=".intval($this->parameters['operationId']));
                    RoleAction::refresh();
                    $this->output['status'] = STATUS_OK;
                }
                else{
                    $this->output['status']     = STATUS_NOT_FOUND;
                 $this->output['message']    = 'Operation not found';
                }
                
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'ActionPack not found';
            }
        }
    }
    
}