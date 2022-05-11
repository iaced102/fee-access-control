<?php
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Message;
use Library\Request;
use Library\Str;
use Model\ObjectIdentifier;
use Model\Filter;
use Model\RoleAction;
use Model\FilterInActionPack;
use Model\PermissionRole;
use Model\Users;
use Model\SqlObject;

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
        if(!isset($this->parameters['pageSize'])){
            $this->parameters['pageSize'] = 500;
        }
        if(!isset($this->parameters['sort'])){
            $this->parameters['sort'] = [["column"=>"id","type"=>'DESC']];            
        }
        $listObj = Filter::getByFilter($this->parameters);
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
            ["name"=>"userId","title"=>"userId","type"=>"text"],
            ["name"=>"status","title"=>"status","type"=>"text"],
            ["name"=>"description","title"=>"description","type"=>"text"],
            ["name"=>"name","title"=>"name","type"=>"text"],
            ["name"=>"createTime","title"=>"createTime","type"=>"text"],
            ["name"=>"formula","title"=>"formula","type"=>"text"],
            ["name"=>"objectIdentifier","title"=>"objectIdentifier","type"=>"text"],
        ];
    }
    
    function create(){
        $messageBusData = ['topic'=>Filter::getTopicName(), 'event' => 'update','resource' => json_encode($this->parameters),'env' => Environment::getEnvironment()];
        Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        if($this->checkParameter(['name','formula'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['formula'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","formula" may not be blank';
            }
            else{
                $obj =  new Filter();
                $obj->id = Filter::createUUID();
                $obj->userId = Auth::getCurrentBaEmail();
                $obj->createTime = Str::currentTimeString();
                $obj->name = trim($this->parameters['name']);
                $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                $obj->formula = trim($this->parameters['formula']);
                $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Filter::STATUS_ENABLE;
                $obj->formulaStruct = isset($this->parameters['formulaStruct']) ? ($this->parameters['formulaStruct']):"";
                $obj->insert();
                $this->output['data'] = $obj;
                $this->output['status'] = STATUS_OK;
            }
        }
    
    }
    
    function update(){
        $messageBusData = ['topic'=>Filter::getTopicName(), 'event' => 'update','resource' => json_encode($this->parameters),'env' => Environment::getEnvironment()];
        Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        if($this->checkParameter(['id','name','formula'])){
            if(trim($this->parameters['name'])==''||trim($this->parameters['formula'])==''){
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = '"name","formula" may not be blank';
            }
            else{
                $obj = Filter::getById($this->parameters['id']);
                if($obj!=false){
                    $obj->userId = Auth::getCurrentBaEmail();
                    $obj->name = trim($this->parameters['name']);
                    $obj->description = isset($this->parameters['description'])?trim($this->parameters['description']):'';
                    $obj->formula = trim($this->parameters['formula']);
                    $obj->formulaStruct = isset($this->parameters['formulaStruct']) ? ($this->parameters['formulaStruct']) : "";
                    $obj->objectIdentifier = trim($this->parameters['objectIdentifier']);
                    $obj->status = isset($this->parameters['status'])?trim($this->parameters['status']):Filter::STATUS_ENABLE;
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
                $ids = "'".implode("','", $ids)."'";
                Filter::deleteMulti("id in ($ids)"); 
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

    function getFilterInActionPack(){
        if($this->checkParameter(['actionPackId'])){
            $actionPackId = $this->parameters['actionPackId'];
            $this->output['data']   = FilterInActionPack::getFilterInActionPack($actionPackId);
            $this->output['status'] = STATUS_OK;
        }
    }
  
}