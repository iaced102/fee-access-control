<?php
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Message;
use Library\Request;
use Library\Str;
use Model\ActionInPermissionPack;
use Library\ObjectRelation;
use Model\ActionPack;
use Model\RoleAction;
use Model\PermissionPack;
use Model\PermissionRole;
use Model\Users;
use Library\MessageBus;

class PermissionService extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }
    function list(){
        if(!isset($this->parameters['pageSize'])){
            $this->parameters['pageSize'] = 500;
        }
        if(!isset($this->parameters['sort'])){
            $this->parameters['sort'] = [["column"=>"createAt","type"=>'DESC']];            
        }
        $filter = [
            [
                'column'    => 'status',
                'conditions'=> [
                    [
                        'name'  =>'greater_than',
                        'value' => 0
                    ]
                ]
            ]
        ];
        $listObj = PermissionPack::getByFilter($this->parameters,$filter);
        $data = [
            'listObject'  => $listObj['list'],
            'columns'     => $this->getListColumns(),
            'total'       => $listObj['total'],
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
            ["name"=>"type","title"=>"type","type"=>"text"],
            ["name"=>"status","title"=>"status","type"=>"text"],
            ["name"=>"description","title"=>"description","type"=>"text"],
            ["name"=>"userCreate","title"=>"userCreate","type"=>"text"],
            ["name"=>"userUpdate","title"=>"userUpdate","type"=>"text"],
            ["name"=>"createAt","title"=>"createAt","type"=>"text"],
            ["name"=>"updateAt","title"=>"updateAt","type"=>"text"],
        ];
    }
    function getObjectRleationLinks(&$links,$objectIdentifier,$id){
        foreach($objectIdentifier as $key=>$value){
            array_push($links,[
                'start' => "permission_pack:$id",
                'end'=> "action_pack:$value",
                'type' => 'USE',
                'host' =>"permission_pack:$id"
            ]);
        }
    }
    function addObjectRleationNodes(&$nodes,$id,$objectIdentifier,$name){
        array_push($nodes,  [
            'name' => $name,
            'id' => "permission_pack:$id",
            'title' => $name,
            'type' => 'permission_pack',
            'host' => "permission_pack:$id"
        ]);
        foreach($objectIdentifier as $key=>$value){
            array_push($nodes,  [ 
                'name' => "action_pack:$value",
                'id' => "action_pack:$value",
                'title' => "action_pack:$value",
                'type' => 'action_pack',
                'host' => "action_pack:$value"
            ]);
        }
    }
    function saveObjectRleation($list,$id,$name){
        $links=[];
        $nodes =[];
        self::getObjectRleationLinks($links,json_decode($list),$id);
        self::addObjectRleationNodes($nodes,$id,json_decode($list),$name);
        ObjectRelation::save($nodes,$links,"permission_pack:$id");
    }
    function create(){
        MessageBus::publish(PermissionPack::getTopicName(),"create",json_encode($this->parameters));
        if($this->checkParameter(['name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj =  new PermissionPack();
                $obj->id= PermissionPack::createUUID();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->type = isset($this->parameters['type'])?trim($this->parameters['type']):PermissionPack::TYPE_USER;
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):PermissionPack::STATUS_ENABLE;
                $obj->userCreate = Auth::getCurrentBaEmail();
                $obj->userUpdate = Auth::getCurrentBaEmail();
                $obj->createAt  =date(DATETIME_FORMAT);
                $obj->updateAt  =date(DATETIME_FORMAT);
                $obj->insert();
                if(isset($this->parameters['listActionPacks'])){
                    $listActionPacks = Str::getArrayFromUnclearData($this->parameters['listActionPacks']);
                    $obj->saveActionPack($listActionPacks);
                }
                $this->output['data'] = $obj;
                $this->output['status'] = STATUS_OK;
                if(isset($this->parameters['listActionPacks'])){
                    self::saveObjectRleation($this->parameters['listActionPacks'],$obj->id,$obj->name);
                    RoleAction::closeConnectionAndRefresh($this);
                }
            }
        }
    
    }
   
    function update(){
        MessageBus::publish(PermissionPack::getTopicName(),"update",json_encode($this->parameters));
        if($this->checkParameter(['id','name'])){
            if(trim($this->parameters['name'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name" may not be blank';
            }
            else{
                $obj = PermissionPack::getById($this->parameters['id']);
                if($obj!=false){
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->type = isset($this->parameters['type'])?trim($this->parameters['type']):PermissionPack::TYPE_USER;
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):PermissionPack::STATUS_ENABLE;
                    $obj->userUpdate = Auth::getCurrentBaEmail();
                    $obj->updateAt  =date(DATETIME_FORMAT);
                    if($obj->update()){
                        if(isset($this->parameters['listActionPacks'])){
                            $listActionPacks = Str::getArrayFromUnclearData($this->parameters['listActionPacks']);
                            $obj->saveActionPack($listActionPacks);
                            self::saveObjectRleation($this->parameters['listActionPacks'],$this->parameters['id'], trim($this->parameters['name']));
                            $this->output['status'] = STATUS_OK;
                            RoleAction::closeConnectionAndRefresh($this);
                        }
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
            $ids = $this->parameters['id'];
            $idsArr = explode(',',$ids);
            $ids ='{'.implode(",",$idsArr).'}';
            
            PermissionPack::updateMulti(" status = $1"," id = ANY($2)",['0',$ids]);
            $this->output['status'] = STATUS_OK;
            $hostsId=[];
            foreach($idsArr as $k => $v){
                array_push($hostsId,'permission_pack:'.$v);
            }
            ObjectRelation::deleteNodesAndLinks(implode(",",$hostsId));
            RoleAction::closeConnectionAndRefresh($this);
        }
    }
    function detail(){
        if($this->checkParameter(['id'])){
            $obj = PermissionPack::getById($this->parameters['id']);
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
    function listActionPack(){
        if($this->checkParameter(['id'])){
            $obj = PermissionPack::getById($this->parameters['id']);
            $listObj = [];
            if($obj!=false){
                if(isset($this->parameters['detail'])&&intval($this->parameters['detail'])==1){
                    $where = ["conditions" => "action_in_permission_pack.action_pack_id=action_pack.id AND action_in_permission_pack.permission_pack_id=$1", "dataBindings" => [$obj->id]];
                    $listObj = ActionPack::getByStatements('',$where,'',false,'action_in_permission_pack');
                }
                else{
                    $where = ["conditions" => "permission_pack_id=$1", "dataBindings" => [$obj->id]];
                    $listObj = ActionInPermissionPack::getByStatements('',$where);
                }
                $this->output['data']   = $listObj;
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'Permission pack not found';
            }
        }
    }
    function addActionPack(){
        MessageBus::publish(ActionInPermissionPack::getTopicName(),"create",json_encode($this->parameters));
        if($this->checkParameter(['id','actionPackId'])){
            $obj = PermissionPack::getById($this->parameters['id']);
            if($obj!=false){
                if(ActionPack::count("id=$1",[$this->parameters['actionPackId']])>0){
                    if(ActionInPermissionPack::count("permission_pack_id=$1 and action_pack_id=$2",[$this->parameters['id'],$this->parameters['actionPackId']])==0){
                        $actionInPermissionPackObj =  new ActionInPermissionPack();
                        $actionInPermissionPackObj->permissionPackId = $obj->id;
                        $actionInPermissionPackObj->actionPackId = $this->parameters['actionPackId'];
                        $actionInPermissionPackObj->save();
                        $this->saveUserUpdate($this->parameters['id']);
                        $this->output['status'] = STATUS_OK;
                        RoleAction::closeConnectionAndRefresh($this);
                    }
                    $this->output['status'] = STATUS_OK;
                }
                else{
                    $this->output['status']     = STATUS_NOT_FOUND;
                    $this->output['message']    = 'Action pack not found';
                }
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'permission not found';
            }
        }
    }
    function removeActionPack(){
        if($this->checkParameter(['id','actionPackId'])){
            $obj = ActionPack::getById($this->parameters['actionPackId']);
            if($obj!=false){
                if(ActionInPermissionPack::count("permission_pack_id=$1 and action_pack_id=$2",[$this->parameters['id'],$this->parameters['actionPackId']])>0){
                    ActionInPermissionPack::deleteMulti("permission_pack_id=$1 and action_pack_id=$2",[$this->parameters['id'],$this->parameters['actionPackId']]);
                    $this->saveUserUpdate($this->parameters['id']);
                    $this->output['status'] = STATUS_OK;
                    RoleAction::closeConnectionAndRefresh($this);
                }
                else{
                    $this->output['status']     = STATUS_NOT_FOUND;
                 $this->output['message']    = 'Action pack not found';
                }
                
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'Permission not found';
            }
        }
    }
    function saveUserUpdate($id){
        $id = $id;
        PermissionPack::updateMulti("user_update=$1,update_at=$2","id=$3",[Auth::getCurrentUserId(),date(DATETIME_FORMAT),$id]);
    }
    
}