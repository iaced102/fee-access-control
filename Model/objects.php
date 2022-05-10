<?php
namespace Model;
use SqlObject;
class ObjectIdentifier extends SqlObject
{
    public
        $objectIdentifier,
        $name,
        $type,
        $tenantId;
        
    public static $mappingFromDatabase = [
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string', 'primary'=>true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'type'              =>  [ 'name' => 'type',                 'type' => 'string'],
        'tenantId'          =>  [ 'name' => 'tenant_id_',           'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'object_identifier';
    }
    public static function getTopicName(){
       return 'object_identifier';
    }
}