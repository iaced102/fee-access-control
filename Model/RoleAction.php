<?php
namespace Model;

use Library\MessageBus;
use SqlObject;
class RoleAction extends SqlObject{
    
   
    public 
        $objectIdentifier,
        $action,
        $objectType,
        $name,
        $roleIdentifier,
        $status,
        $filter;
    public static $mappingFromDatabase = [
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string'],
        'filter'            =>  [ 'name' => 'filter_formula',               'type' => 'string'],
        'status'            =>  [ 'name' => 'filter_status',        'type' => 'string'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'role_action';
    }
    public static function getTopicName(){
       return 'role_action';
    }
    public static function refresh(){
        Connection::exeQuery("REFRESH MATERIALIZED VIEW ".self::getTableName());
        MessageBus::publish("role_action","update",["has update"]);
    }
    public static function createView(){
        $createViewQuery = "
        SELECT operation.object_identifier,
        operation.action,
        operation.object_type,
        operation.name,
        operation.status,
        permission_role.role_identifier,
        filter.formula AS filter_formula,
        filter.status AS filter_status
    FROM operation,
        operation_in_action_pack,
        action_in_permission_pack,
        permission_role,
        filter
        WHERE operation.id = operation_in_action_pack.operation_id AND 
        operation_in_action_pack.action_pack_id = action_in_permission_pack.action_pack_id AND 
        action_in_permission_pack.permission_pack_id = permission_role.permission_pack_id AND 
        filter.id::text = operation_in_action_pack.filter::text;
        ";
    }
   
}