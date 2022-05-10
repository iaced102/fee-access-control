<?php
namespace Model;
use SqlObject;
class UserRole extends SqlObject
{
    public $id,
        $userId,
        $roleType,
        $roleIdentifier,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',               'type' => 'string', 'primary'=>true],
        'userId'            =>  [ 'name' => 'user_id',          'type' => 'string'],
        'roleType'          =>  [ 'name' => 'role_type',        'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',  'type' => 'string'],
        'tenantId'          =>  [ 'name' => 'tenant_id_',       'type' => 'number']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'user_role';
    }
    public static function getTopicName(){
        return 'user_role';
    }
}