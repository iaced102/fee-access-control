<?php
namespace Model;
use SqlObject;
class UserRole extends SqlObject
{
    public $id,
        $userId,
        $roleType,
        $roleIdentifier
        ;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',               'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'userId'            =>  [ 'name' => 'user_id',          'type' => 'number'],
        'roleType'          =>  [ 'name' => 'role_type',        'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',  'type' => 'string'],
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