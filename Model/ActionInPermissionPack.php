<?php
namespace Model;
use SqlObject;
class ActionInPermissionPack extends SqlObject
{
    public $id,
        $actionPackId,
        $permissionPackId;
    public static $mappingFromDatabase = [
        'id'                    =>  [ 'name' => 'id',                   'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'actionPackId'          =>  [ 'name' => 'action_pack_id',       'type' => 'number'],
        'permissionPackId'      =>  [ 'name' => 'permission_pack_id',   'type' => 'number']
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