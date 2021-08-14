<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Str;
use Model\ObjectIdentifier;
use Model\Filter;
use Model\RoleAction;
use Model\FilterInActionPack;
use Model\PermissionRole;
use Model\Users;

class FilterService extends Controller
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
        $listObj = Filter::getByPaging($page,$pageSize,'id ASC','');
        $this->output = [
            'status'=>STATUS_OK,
            'data' => $listObj
        ];   
    }
    function create(){
        if($this->checkParameter(['name','formula'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['formula'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","formula" may not be blank';
            }
            else{
                $obj =  new Filter();
                $obj->id = SqlObject::createUUID();
                $obj->userId = Auth::getCurrentUserId();
                $obj->createTime = Str::currentTimeString();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->formula = trim($this->parameters['formula']);
                $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Filter::STATUS_ENABLE;
                $obj->insert();
                $this->output['data'] = $obj;
                $this->output['status'] = STATUS_OK;
            }
        }
    
    }
    
    function update(){
        if($this->checkParameter(['id','name','formula'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['objectIdentifier'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","formula" may not be blank';
            }
            else{
                $obj = Filter::getById($this->parameters['id']);
                if($obj!=false){
                    $obj =  new Filter();
                    $obj->userId = Auth::getCurrentUserId();
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->formula = trim($this->parameters['formula']);
                    $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Filter::STATUS_ENABLE;
                    if($obj->update()){
                        $this->output['status'] = STATUS_OK;
                    }
                    else{
                        $this->output['status'] = STATUS_SERVER_ERROR;
                    }
                    
                }
                else{
                    $this->output['status'] = STATUS_NOT_FOUND;
                    $this->output['message'] = 'Filter not found';
                }
            }
        }
    }
    function delete(){
        if($this->checkParameter(['id'])){
            $id = $this->parameters['id'];
            $obj = Filter::getById($id);
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
                $this->output['message']    = 'Filter not found';
            }
        }
    }
    function deleteMany(){
        if($this->checkParameter(['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            if(count($ids)>0){
                Filter::deleteMulti("id in (".implode(',',"'$ids'").')');
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
            $obj = Filter::getById($this->parameters['id']);
            if($obj!=false){
                $this->output['data']   = $obj;
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status']     = STATUS_NOT_FOUND;
                $this->output['message']    = 'Filter not found';
            }
        }
    }
  
}