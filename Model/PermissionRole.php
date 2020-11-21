<?php
namespace Model;
use SqlObject;
class PermissionRole extends SqlObject
{
    public $id,
        $permissionPackId,
        $roleType,
        $roleIdentifier;
    public static $mappingFromDatabase = [
        'id'                    =>  [ 'name' => 'id',               'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'permissionPackId'      =>  [ 'name' => 'permission_pack_id', 'type' => 'number'],
        'roleType'              =>  [ 'name' => 'role_type',         'type' => 'string'],
        'roleIdentifier'        =>  [ 'name' => 'role_identifier',   'type' => 'string'],
    ];
    
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'permission_role';
    }
    public static function getTopicName(){
       return 'permission_role';
    }
       
}