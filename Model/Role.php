<?php
namespace Model;
use SqlObject;
class Role extends SqlObject{
    public const TYPE_SYSTEM    = 'system';
    public const TYPE_ORGCHART  = 'orgchart';
    public $id,
        $name,
        $type,
        $roleIdentifier,
        $status,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'type'              =>  [ 'name' => 'type',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'tenantId'          => [ 'name' => 'tenant_id_',            'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'role';
    }
    public static function getTopicName(){
       return 'role';
    }
}