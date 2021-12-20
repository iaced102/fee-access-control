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
        $filter,
        $actionPackId;
    public static $mappingFromDatabase = [
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string'],
        'filter'            =>  [ 'name' => 'filter_formula',               'type' => 'string'],
        'status'            =>  [ 'name' => 'filter_status',        'type' => 'string'],
        'actionPackId'      =>  [ 'name' => 'action_pack_id',        'type' => 'string'],
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
        SELECT o.object_identifier,                                                          
        o.action,                                                                         
        o.object_type,                                                                    
        o.name,                                                                           
        o.status,                                                                         
        pr.role_identifier,                                                               
        filter.formula AS filter_formula,                                                 
        filter.status AS filter_status,
        fia.filter_values as filter_values
        FROM operation o 
         JOIN operation_in_action_pack op ON o.id = op.operation_id and o.tenant_id = op.tenant_id               
         JOIN action_in_permission_pack app ON op.action_pack_id = app.action_pack_id and op.tenant_id = app.tenant_id      
         JOIN permission_role pr ON app.permission_pack_id = pr.permission_pack_id and pr.tenant_id = app.tenant_id   
         LEFT JOIN filter ON (op.filter)::text = (filter.id)::text and op.tenant_id = filter.tenant_id
         LEFT JOIN filter_in_action_pack fia ON op.action_pack_id  = fia.action_pack_id and fia.tenant_id = filter.tenant_id;
        ";
    }
   
}