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
use Library\ObjectRelation;
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
        if(!isset($this->parameters['pageSize'])){
            $this->parameters['pageSize'] = 500;
        }
        if(!isset($this->parameters['sort'])){
            $this->parameters['sort'] = [["column"=>"createAt","type"=>'DESC']];            
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
        Request::request(MESSAGE_BUS_SERVICE.'/publish', $messageBusData, 'POST');
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
                    self::saveObjectRleation($this->parameters['listOperations'],$obj->id,$obj->name);
                    $this->output['data'] = $obj;
                    $this->output['status'] = STATUS_OK;
                }
            }
        }
    
    }
    function getObjectRleationLinks(&$links,$objectIdentifier,$id){
        $listOperationId=[];
        foreach($objectIdentifier as $key=>$value){
            array_push($listOperationId,$key);
        }
        $listOperationId="'".implode("','",$listOperationId)."'";
        $operation = Operation::getByTop('',"id IN ($listOperationId)");
        foreach($operation as $key=>$val){
            if (strpos($val->objectIdentifier,':0')===false && strpos($val->objectIdentifier,'department')===false){
                if(strpos($val->objectIdentifier,'dataset')!==false || strpos($val->objectIdentifier,'dashboard')!==false){
                    array_push($links,['start' => "action_pack:$id",'end'=> $val->objectIdentifier,'type' => 'USE','host' =>"action_pack:$id"]);
                } else {
                    $idObj      = explode(':',$val->objectIdentifier)[1];
                    $obj        = explode(':',$val->objectIdentifier)[0];
                    $objName    = explode('_',$obj)[0];
                    array_push($links,['start' => "action_pack:$id",'end'=> $objName.':'.$idObj,'type' => 'USE','host' =>"action_pack:$id"]);
                }
            }
        }
    }
    function addObjectRleationNodes(&$nodes,$id,$objectIdentifier,$name){
        array_push($nodes,['name' => $name,'id' => "action_pack:$id",'title' => $name,'type' => 'action_pack','host' => "action_pack:$id"]);
        $listOperationId=[];
        foreach($objectIdentifier as $key=>$value){
            array_push($listOperationId,$key);
        }
        $listOperationId="'".implode("','",$listOperationId)."'";
        $operation = Operation::getByTop('',"id IN ($listOperationId)");
        foreach($operation as $key=>$val){
            if (strpos($val->objectIdentifier,':0')===false && strpos($val->objectIdentifier,'department')===false){
                if(strpos($val->objectIdentifier,'dataset')!==false || strpos($val->objectIdentifier,'dashboard')!==false){
                    $type = strpos($val->objectIdentifier,'dashboard')!==false ? 'dashboard' : 'dataset';
                    array_push($nodes,['name' => $val->objectIdentifier,'id' => $val->objectIdentifier,'title' => $val->objectIdentifier,'type' => $type,'host' =>"action_pack:$id"]);
                } else {
                    $idObj      = explode(':',$val->objectIdentifier)[1];
                    $obj        = explode(':',$val->objectIdentifier)[0];
                    $objName    = explode('_',$obj)[0];
                    array_push($nodes,['name' => $objName.':'.$idObj,'id' => $objName.':'.$idObj,'title' => $objName.':'.$idObj,'type' => $objName,'host' =>"action_pack:$id"]);
                }
            }
        }
    }
    function saveObjectRleation($list,$id,$name){
        $links=[];
        $nodes =[];
        self::getObjectRleationLinks($links,json_decode($list),$id);
        self::addObjectRleationNodes($nodes,$id,json_decode($list),$name);
        ObjectRelation::save($nodes,$links,'');
    }
    function update(){
        TimeLog::start('publish-data-to-kafka');
        $messageBusData = ['topic'=>ActionPack::getTopicName(), 'event' => 'update','resource' => json_encode($this->parameters),'env' => Environment::getEnvironment()];
        Request::request(MESSAGE_BUS_SERVICE.'publish', $messageBusData, 'POST');
        TimeLog::end('publish-data-to-kafka', MESSAGE_BUS_SERVICE.'publish');
        
        if($this->checkParameter(['id','name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj = ActionPack::getById($this->parameters['id']);
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
                                TimeLog::start('attachFilterToOperation');
                                $filterAttachToOperation = $obj->attachFilterToOperation($listFilter, $obj->id);
                                TimeLog::end('attachFilterToOperation');
                                
                                TimeLog::start('saveFilter');
                                $obj->saveFilter($listFilter);
                                TimeLog::end('saveFilter');
                            }

                            if(isset($this->parameters['listOperations'])){
                                $listOperations = Str::getArrayFromUnclearData($this->parameters['listOperations']);
                                TimeLog::start('saveOperation');                                
                                $obj->saveOperation($listOperations, $filterAttachToOperation);
                                TimeLog::end('saveOperation');

                            }
                            $this->output['status'] = STATUS_OK;
                        }
                        self::saveObjectRleation($this->parameters['listOperations'],$this->parameters['id'],trim($this->parameters['name']));
                        
                        $this->output['status'] = STATUS_OK;
                        $this->output['data'] = TimeLog::getAll();

                        TimeLog::start('RoleAction::refresh 2st');                                
                        RoleAction::closeConnectionAndRefresh($this);
                        TimeLog::end('RoleAction::refresh 2st');
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
            $obj = ActionPack::getById($this->parameters['id']);
            if($obj!=false){
                if($obj->delete()){
                    $this->output['status'] = STATUS_OK;
                    RoleAction::closeConnectionAndRefresh($this);
                    $hostsId=['action_pack:'.$this->parameters['id']];
                    ObjectRelation::deleteNodesAndLinks(implode(",",$hostsId));
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
            $obj = ActionPack::getById($this->parameters['id']);
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
            $obj = ActionPack::getById($this->parameters['id']);
            if($obj!=false){
                $listObj = Operation::getByTop('',"operation_in_action_pack.action_pack_id='".$obj->id."' and operation_in_action_pack.operation_id=operation.id","","operation.*","operation_in_action_pack");
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
            $obj = ActionPack::getById($this->parameters['id']);
            if($obj!=false){
                if(Operation::count("id='".$this->parameters['operationId']."'")>0){
                    if(OperationInActionPack::count("action_pack_id='".$this->parameters['id']."' and operation_id='".$this->parameters['operationId']."'")==0){
                        $operationInActionPackObj =  new OperationInActionPack();
                        $operationInActionPackObj->actionPackId = $obj->id;
                        $operationInActionPackObj->operationId = $this->parameters['operationId'];
                        $operationInActionPackObj->save();
                    }
                    $this->output['status'] = STATUS_OK;
                    RoleAction::closeConnectionAndRefresh($this);
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
            $obj = ActionPack::getById($this->parameters['id']);
            if($obj!=false){
                if(OperationInActionPack::count("action_pack_id='".$this->parameters['id']."' and operation_id='".$this->parameters['operationId']."'")>0){
                    OperationInActionPack::deleteMulti("action_pack_id='".$this->parameters['id']."' and operation_id='".$this->parameters['operationId']."'");
                    $this->output['status'] = STATUS_OK;
                    RoleAction::closeConnectionAndRefresh($this);
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