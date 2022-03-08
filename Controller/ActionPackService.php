<?php
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Message;
use Library\Request;
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
        $this->requireLogin = false;
    }
   /**
    * @operation("pack","list")
    * @operation("pack","list2")
    */
    public function  list(){
        if(!isset($this->parameters['pageSize'])){
            $this->parameters['pageSize'] = 500;
        }
        if(!isset($this->parameters['sort'])){
            $this->parameters['sort'] = [["column"=>"id","type"=>'DESC']];            
        }
        $listObj = ActionPack::getByFilter($this->parameters);
        $data = [
            'listObject'  => $listObj['list'],
            'columns'     => $this->getListColumns(),
            'total'       => $listObj['total'],
            'sql'       => $listObj['sql'],
        ];
        $this->output = [
            'status'=>STATUS_OK,
            'data' => $data
        ];   
    }
    function getListColumns(){
        return [
            ["name"=>"id","title"=>"id","type"=>"numeric"],
            ["name"=>"name","title"=>"name","type"=>"text"],
            ["name"=>"status","title"=>"status","type"=>"text"],
            ["name"=>"description","title"=>"description","type"=>"text"],
            ["name"=>"userCreate","title"=>"userCreate","type"=>"text"],
            ["name"=>"userUpdate","title"=>"userUpdate","type"=>"text"],
            ["name"=>"createAt","title"=>"createAt","type"=>"text"],
            ["name"=>"updateAt","title"=>"updateAt","type"=>"text"],
        ];
    }
    function create(){
        $messageBusData = ['topic'=>ActionPack::getTopicName(), 'event' => 'create','resource' => json_encode($this->parameters),'env' => Environment::getEnvironment()];
        Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        if($this->checkParameter(['name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj =  new ActionPack();
                $obj->name = trim($this->parameters['name']);
                if(ActionPack::checkNameExist($obj->name)){
                    $this->output['status'] = STATUS_SERVER_ERROR;
                    $this->output['message'] = "Action pack already exists";
                }else{
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):ActionPack::STATUS_ENABLE;
                    $obj->userCreate = Auth::getCurrentBaEmail();
                    $obj->userUpdate = Auth::getCurrentBaEmail();
                    $obj->createAt = date(DATETIME_FORMAT);
                    $obj->updateAt = date(DATETIME_FORMAT);
                    $obj->insert();
                    if(isset($this->parameters['listOperations'])){
                        $listOperations = Str::getArrayFromUnclearData($this->parameters['listOperations']);
                        $obj->saveOperation($listOperations);
                    }
                    if(isset($this->parameters['listFilter'])){
                        $listFilter = Str::getArrayFromUnclearData($this->parameters['listFilter']);
                        $obj->saveFilter($listFilter, $obj->id);
                    }
                    $this->output['data'] = $obj;
                    $this->output['status'] = STATUS_OK;
                }
            }
        }
    
    }
    function update(){
        $messageBusData = ['topic'=>ActionPack::getTopicName(), 'event' => 'update','resource' => json_encode($this->parameters),'env' => Environment::getEnvironment()];
        Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        if($this->checkParameter(['id','name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj = ActionPack::getById(intval($this->parameters['id']));
                if($obj!=false){
                    $obj->name = trim($this->parameters['name']);
                    if(ActionPack::checkNameExist($obj->name, $obj->id)){
                        $this->output['status'] = STATUS_SERVER_ERROR;
                        $this->output['message'] = "Action pack already exists";
                    }else{
                        $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                        $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):ActionPack::STATUS_ENABLE;
                        $obj->userUpdate = Auth::getCurrentBaEmail();
                        $obj->updateAt = date(DATETIME_FORMAT);
                        if($obj->update()){
                            $filterAttachToOperation = [];
                            if(isset($this->parameters['listFilter'])){
                                $listFilter = Str::getArrayFromUnclearData($this->parameters['listFilter']);
                                $filterAttachToOperation = $obj->attachFilterToOperation($listFilter, $obj->id);
                                $obj->saveFilter($listFilter);
                            }

                            if(isset($this->parameters['listOperations'])){
                                $listOperations = Str::getArrayFromUnclearData($this->parameters['listOperations']);
                                $obj->saveOperation($listOperations, $filterAttachToOperation);
                            }
                            RoleAction::refresh();
                            $this->output['status'] = STATUS_OK;
                        }
                        else{
                            $this->output['status'] = STATUS_SERVER_ERROR;
                        }
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