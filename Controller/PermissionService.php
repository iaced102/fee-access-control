<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Str;
use Model\ActionInPermissionPack;
use Model\ActionPack;
use Model\RoleAction;
use Model\PermissionPack;
use Model\PermissionRole;
use Model\Users;

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
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $listObj = PermissionPack::getByPaging($page,$pageSize,'id ASC','');
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
                $obj =  new PermissionPack();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->type = isset($this->parameters['type'])?trim($this->parameters['type']):PermissionPack::TYPE_USER;
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):PermissionPack::STATUS_ENABLE;
                $obj->userCreate = Auth::getCurrentUserId();
                $obj->userUpdate = Auth::getCurrentUserId();
                $obj->createAt  =date(DATETIME_FORMAT);
                $obj->updateAt  =date(DATETIME_FORMAT);
                $obj->insert();
                if(isset($this->parameters['listActionPacks'])){
                    $listActionPacks = Str::getArrayFromUnclearData($this->parameters['listActionPacks']);
                    $obj->saveActionPack($listActionPacks);
                    RoleAction::refresh();
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
                $obj = PermissionPack::getById(intval($this->parameters['id']));
                if($obj!=false){
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->type = isset($this->parameters['type'])?trim($this->parameters['type']):PermissionPack::TYPE_USER;
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):PermissionPack::STATUS_ENABLE;
                    $obj->userUpdate = Auth::getCurrentUserId();
                    $obj->updateAt  =date(DATETIME_FORMAT);
                    if($obj->update()){
                        if(isset($this->parameters['listActionPacks'])){
                            $listActionPacks = Str::getArrayFromUnclearData($this->parameters['listActionPacks']);
                            $obj->saveActionPack($listActionPacks);
                            RoleAction::refresh();
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
            $obj = PermissionPack::getById(intval($this->parameters['id']));
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
                $this->output['message']    = 'operation not found';
            }
        }
    }
    function detail(){
        if($this->checkParameter(['id'])){
            $obj = PermissionPack::getById(intval($this->parameters['id']));
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
            $obj = PermissionPack::getById(intval($this->parameters['id']));
            $listObj = [];
            if($obj!=false){
                if(isset($this->parameters['detail'])&&intval($this->parameters['detail'])==1){
                    $listObj = ActionPack::getByTop('',"action_in_permission_pack.action_pack_id=action_pack.id AND action_in_permission_pack.permission_pack_id=".$obj->id,'',false,'action_in_permission_pack');
                }
                else{
                    $listObj = ActionInPermissionPack::getByTop('',"permission_pack_id=".$obj->id);
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
        if($this->checkParameter(['id','actionPackId'])){
            $obj = PermissionPack::getById(intval($this->parameters['id']));
            if($obj!=false){
                if(ActionPack::count("id=".intval($this->parameters['actionPackId']))>0){
                    if(ActionInPermissionPack::count("permission_pack_id=".intval($this->parameters['id'])." and action_pack_id=".intval($this->parameters['actionPackId']))==0){
                        $actionInPermissionPackObj =  new ActionInPermissionPack();
                        $actionInPermissionPackObj->permissionPackId = $obj->id;
                        $actionInPermissionPackObj->actionPackId = intval($this->parameters['actionPackId']);
                        $actionInPermissionPackObj->save();
                        $this->saveUserUpdate($this->parameters['id']);
                        RoleAction::refresh();
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
            $obj = ActionPack::getById(intval($this->parameters['actionPackId']));
            if($obj!=false){
                if(ActionInPermissionPack::count("permission_pack_id=".intval($this->parameters['id'])." and action_pack_id=".intval($this->parameters['actionPackId']))>0){
                    ActionInPermissionPack::deleteMulti("permission_pack_id=".intval($this->parameters['id'])." and action_pack_id=".intval($this->parameters['actionPackId']));
                    $this->saveUserUpdate($this->parameters['id']);
                    RoleAction::refresh();
                    $this->output['status'] = STATUS_OK;
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
        $id = intval($id);
        PermissionPack::updateMulti("user_update=".Auth::getCurrentUserId().",update_at='".date(DATETIME_FORMAT)."'","id=$id");
    }
    
}