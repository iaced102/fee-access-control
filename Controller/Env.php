<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Str;
use Model\ActionPack;
use Model\PermissionPack;
use Model\UserPermissionPackage as ModelUserPermissionPackage;
use Model\UserPermissionPositionOrgchart;
use Model\Users;
use UserPermissionPackage;


class Env extends Controller{
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'listObjectType';
        $this->requireLogin = false;
    }

    public function listObjectType(){
        
        $this->output=[
            'status'=>STATUS_OK,
            'data'=>['action_pack','permission_pack',]
        ];
    }
    public function listActionPack(){
        $condition=[];
        $data = ActionPack::getByFilter($this->parameters,$condition);
        $this->output = [
            'status'    => STATUS_OK,
            'message'   => 'OK',
            'data'      => [
                'listObject' => $data['list'],
                'columns'    => [
                    ['name'=>'id',                  'title'=>'id','type'=>'numeric'],
                    ['name'=>'name',                'title'=>'name','type'=>'string'],
                    ['name'=>'description',         'title'=>'description','type'=>'string'],
                    ['name'=>'status',               'title'=>'status','type'=>'numeric'],
                    ['name'=>'createAt',            'title'=>'createAt','type'=>'datetime'],
                    ['name'=>'updateAt',            'title'=>'updateAt','type'=>'datetime'],
                    ['name'=>'userUpdate',          'title'=>'userUpdate','type'=>'string'],
                    ['name'=>'userCreate',          'title'=>'userCreate','type'=>'string']
                ],
                'total'      => $data['total']
            ]
        ];
    }
    public function listPermission(){
        $condition=[];
        $data = PermissionPack::getByFilter($this->parameters,$condition);
        $this->output = [
            'status'    => STATUS_OK,
            'message'   => 'OK',
            'data'      => [
                'listObject' => $data['list'],
                'columns'    => [
                    ['name'=>'id',                  'title'=>'id','type'=>'numeric'],
                    ['name'=>'name',                'title'=>'name','type'=>'string'],
                    ['name'=>'description',         'title'=>'description','type'=>'string'],
                    ['name'=>'type',                'title'=>'type','type'=>'string'],
                    ['name'=>'status',              'title'=>'status','type'=>'numeric'],
                    ['name'=>'createAt',            'title'=>'createAt','type'=>'datetime'],
                    ['name'=>'updateAt',            'title'=>'updateAt','type'=>'datetime'],
                    ['name'=>'userUpdate',          'title'=>'userUpdate','type'=>'string'],
                    ['name'=>'userCreate',          'title'=>'userCreate','type'=>'string']
                ],
                'total'      => $data['total'],
                // 'sql'=>$data['sql']
            ]
        ];
    }
    public function getActionPackByIds(){
        if($this->checkParameter(['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            $idsStr = implode(',',$ids);
            $this->output=[
                'status'=>STATUS_OK,
                'data'=>ActionPack::getByTop('',"id IN ($idsStr)")
            ];  
        }
    }
    
    public function getPermissionByIds(){
        if($this->checkParameter(['ids'])){
            $ids = Str::getArrayFromUnclearData($this->parameters['ids']);
            $idsStr = implode(',',$ids);
            $this->output=[
                'status'=>STATUS_OK,
                'data'=>PermissionPack::getByTop('',"id IN ($idsStr)")
            ];  
        }
    }
    
    public function saveActionPackByIds(){
        if($this->checkParameter(['data'])){
            $data = is_array($this->parameters['data'])?$this->parameters['data']:json_decode($this->parameters['data'],true);
            if(is_array($data)&&count($data)>0){
                foreach($data as $item){
                    $userObject = new ActionPack($item);
                    $userObject->save();
                }
            }
        }
    }

    public function savePermissionByIds(){
        if($this->checkParameter(['data'])){
            $data = is_array($this->parameters['data'])?$this->parameters['data']:json_decode($this->parameters['data'],true);
            if(is_array($data)&&count($data)>0){
                foreach($data as $item){
                    $userObject = new PermissionPack($item);
                    $userObject->save();
                }
            }
        }
    }
}