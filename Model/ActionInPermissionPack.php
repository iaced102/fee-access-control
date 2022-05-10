<?php
namespace Model;
use SqlObject;
class ActionInPermissionPack extends SqlObject
{
    public $id,
        $actionPackId,
        $permissionPackId,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                    =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'actionPackId'          =>  [ 'name' => 'action_pack_id',       'type' => 'string'],
        'permissionPackId'      =>  [ 'name' => 'permission_pack_id',   'type' => 'string'],
        'tenantId'              => [ 'name' => 'tenant_id_',            'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'action_in_permission_pack';
    }
    public static function getTopicName(){
       return 'action_in_permission_pack';
    }
}