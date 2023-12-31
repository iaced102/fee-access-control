<?php
namespace Model;
use SqlObject;
class PermissionPack extends SqlObject
{
    public const TYPE_SYSTEM    = 'system';
    public const TYPE_BA        = 'ba';
    public const TYPE_USER      = 'user';
    public const STATUS_ENABLE  = 1;
    public const STATUS_DISABLED  = 0;
    public $id,
        $name,
        $description,
        $type,
        $status,
        $userCreate,
        $userUpdate,
        $createAt,
        $updateAt,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'type'              =>  [ 'name' => 'type',                 'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'userCreate'        =>  [ 'name' => 'user_create',          'type' => 'string'],
        'userUpdate'        =>  [ 'name' => 'user_update',          'type' => 'string'],
        'createAt'          =>  [ 'name' => 'create_at',            'type' => 'datetime'],
        'updateAt'          =>  [ 'name' => 'update_at',            'type' => 'datetime'],
        'tenantId'          => [ 'name' => 'tenant_id_',            'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'permission_pack';
    }
    public static function getTopicName(){
       return 'permission_pack';
    }
    function saveActionPack($listActionPack){
        if($this->id!=''){
            $this->removeAllActionPack();
            foreach($listActionPack as $actionPackId){
                if(ActionPack::count("id=$1",[$actionPackId])>0){
                    $actionInPermissionPackObj =  new ActionInPermissionPack();
                    $actionInPermissionPackObj->permissionPackId = $this->id;
                    $actionInPermissionPackObj->actionPackId = $actionPackId;
                    $actionInPermissionPackObj->save();
                }
                
            }
        }
    }
    function removeAllActionPack(){
        $id = $this->id;
        ActionInPermissionPack::deleteMulti("permission_pack_id=$1",[$id]);
    }
}