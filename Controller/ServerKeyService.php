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
use Model\ServerKey;
use Model\Users;
use SqlObject;

class ServerKeyService extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }
    function list(){
        if(Auth::isBa()){
        $page = isset($this->parameters['page']) ? intval($this->parameters['page']) : 1;
        $pageSize = isset($this->parameters['pageSize']) ? intval($this->parameters['pageSize']) : 50;
        $listObj = ServerKey::getByPaging($page,$pageSize,'id ASC','');
        $this->output = [
            'status'=>STATUS_OK,
            'data' => $listObj
        ];   
        }
        else{
            $this->output = [
                'status'=>STATUS_PERMISSION_DENIED
            ]; 
        }
    }
    function create(){
        if(Auth::isBa()){
            if($this->checkParameter(['name','description'])){
                if(trim($this->parameters['name'])==''){
                    $this->output['status'] = STATUS_BAD_REQUEST;
                    $this->output['message'] = '"name" may not be blank';
                }
                else{
                    $obj =  new ServerKey();
                    $obj->id = SqlObject::createUUID();
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->status = isset($this->parameters['status'])?intval($this->parameters['status']):ServerKey::STATUS_ENABLE;
                    $obj->userCreate = Auth::getCurrentSupporterId();
                    $obj->userUpdate = Auth::getCurrentSupporterId();
                    $obj->createAt  =date(DATETIME_FORMAT);
                    $obj->updateAt  =date(DATETIME_FORMAT);
                    $obj->serverKey = Auth::Hash(SqlObject::createUUID());
                    $obj->insert();
                    $this->output['status'] = STATUS_OK;
                }
            }
        }
        else{
            $this->output = [
                'status'=>STATUS_PERMISSION_DENIED
            ]; 
        }
    }
   
    function update(){
        if(Auth::isBa()){
            if($this->checkParameter(['id'])){
                
                    $obj = ServerKey::getById($this->parameters['id']);
                    if($obj!=false){
                        if(isset($this->parameters['name'])){
                            $obj->name = $this->parameters['name'];
                        }
                        if(isset($this->parameters['description'])){
                            $obj->description = $this->parameters['description'];
                        }
                        if(isset($this->parameters['status'])){
                            $obj->name = intval($this->parameters['status']);
                        }
                        $obj->userUpdate = Auth::getCurrentSupporterId();
                        $obj->updateAt  =date(DATETIME_FORMAT);
                        if($obj->update()){
                            $this->output['status'] = STATUS_OK;
                        }
                        else{
                            $this->output['status'] = STATUS_SERVER_ERROR;
                        }
                        
                    }
                    else{
                        $this->output['status'] = STATUS_NOT_FOUND;
                        $this->output['message'] = 'server key not found';
                    }
                
            }
        }
        else{
            $this->output = [
                'status'=>STATUS_PERMISSION_DENIED
            ]; 
        }
    }
    function delete(){
        if(Auth::isBa()){
            if($this->checkParameter(['id'])){
                $obj = ServerKey::getById($this->parameters['id']);
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
                    $this->output['message']    = 'server key not found';
                }
            }
        }
        else{
            $this->output = [
                'status'=>STATUS_PERMISSION_DENIED
            ]; 
        }
    }
    function detail(){
        if(Auth::isBa()){
            if($this->checkParameter(['id'])){
                $obj = ServerKey::getById($this->parameters['id']);
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
        else{
            $this->output = [
                'status'=>STATUS_PERMISSION_DENIED
            ]; 
        }
    }
    
    public function test()
    {
        var_dump('xxxxxxxxx');
    }
    
}