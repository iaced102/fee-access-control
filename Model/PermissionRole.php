<?php
namespace Model;
use SqlObject;
class PermissionRole extends SqlObject
{
    public $id,
        $permissionPackId,
        $roleType,
        $roleIdentifier,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                    =>  [ 'name' => 'id',               'type' => 'string', 'primary'=>true],
        'permissionPackId'      =>  [ 'name' => 'permission_pack_id', 'type' => 'string'],
        'roleType'              =>  [ 'name' => 'role_type',         'type' => 'string'],
        'roleIdentifier'        =>  [ 'name' => 'role_identifier',   'type' => 'string'],
        'tenantId'              => [ 'name' => 'tenant_id_', 'type' => 'number'],
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