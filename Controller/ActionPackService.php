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
use Library\MessageBus;
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
        MessageBus::publish(ActionPack::getTopicName(),"create",json_encode($this->parameters));
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
                    self::saveObjectRleation(json_decode($this->parameters['listOperations']),json_decode($this->parameters['listFilter']),$obj->id,$obj->name);
                    $this->output['data'] = $obj;
                    $this->output['status'] = STATUS_OK;
                }
            }
        }
    
    }
    function getObjectRleationLinks(&$links,$listObject,$id){
        self::getObject($listObject,'links',$id,$links);

    }
    function addObjectRleationNodes(&$nodes,$id,$listObject,$name){
        array_push($nodes,['name' => $name,'id' => "action_pack:$id",'title' => $name,'type' => 'action_pack','host' => "action_pack:$id"]);
        
        self::getObject($listObject,'nodes',$id,$nodes);
    }
    function getObject($listObject,$type,$id,&$arr){
        foreach($listObject as $key=>$val){
            if (strpos($val['name'],':0')===false && strpos($val['name'],'department')===false){
                    $name = $val['name'];
                    $typeObj = $val['type'];
                    if($type == 'nodes'){
                        $data = ['name' => $name,'id' => $name,'title' => $name,'type' => $typeObj,'host' =>$name];
                    } else {
                        $data = ['start' => "action_pack:$id",'end'=> $name,'type' => 'USE','host' =>"action_pack:$id"];
                    }
                    if(!in_array($data,$arr)){
                        array_push($arr,$data);
                    }
            }
        }
    }
    function saveObjectRleation($listOperation,$listFilter,$id,$name){
        $links=[];
        $nodes =[];
        $listOperationId=[];
        foreach($listOperation as $key=>$value){
            array_push($listOperationId,$key);
        }
        $listOperationId="'".implode("','",$listOperationId)."'";
        $operation = Operation::getByTop('',"id IN ($listOperationId)");
        $listObject=[];
        foreach($operation as $key => $value) {
            if($value->objectType=='stateflow_flow'){
                array_push($listObject,['name'=>'kanban:'.$value->objectName,'type'=>'kanban']);
            } else array_push($listObject,['name'=>$value->objectIdentifier,'type'=>$value->objectType]);
        }
        foreach($listFilter as $k=>$v){
            array_push($listObject,['name' => 'filter:'.$v->id,'type'=>'filter']);
        }
        self::getObjectRleationLinks($links,$listObject,$id);
        self::addObjectRleationNodes($nodes,$id,$listObject,$name);
        ObjectRelation::save($nodes,$links,"action_pack:$id");
    }
    function update(){
        MessageBus::publish(ActionPack::getTopicName(),"update",json_encode($this->parameters));
        
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
                        self::saveObjectRleation(json_decode($this->parameters['listOperations']),json_decode($this->parameters['listFilter']),$this->parameters['id'],trim($this->parameters['name']));
                        
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