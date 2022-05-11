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
        $filterCombination,
        $filterNew,
        $actionPackId,
        $tenantId;
    public static $mappingFromDatabase = [
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string'],
        'filter'            =>  [ 'name' => 'filter_formula',               'type' => 'string'],
        'filterNew'         =>  [ 'name' => 'filter_formula_new',               'type' => 'string'],
        'status'            =>  [ 'name' => 'filter_status',        'type' => 'string'],
        'actionPackId'      =>  [ 'name' => 'action_pack_id',        'type' => 'string'],
        'filterCombination' =>  [ 'name' => 'filter_combination',        'type' => 'string'],
        'tenantId'          =>  [ 'name' => 'tenant_id_', 'type' => 'number'],
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
        CREATE MATERIALIZED VIEW role_action AS SELECT o.object_identifier,

        o.action,
    
        o.object_type,
    
        o.name,
    
        o.status,
    
        pr.role_identifier,
    
        filter.formula AS filter_formula,
    
        filter.status AS filter_status,
    
        filter.id AS filter_id,
    
        op.formula_value AS filter_formula_new,
    
        op.formula_struct AS filter_combination,
    
        app.action_pack_id,
        0 AS tenant_id_
    
       FROM ((((operation o
    
         JOIN operation_in_action_pack op ON (((o.id = op.operation_id) AND (o.tenant_id_ = op.tenant_id_))))
    
         JOIN action_in_permission_pack app ON (((op.action_pack_id = app.action_pack_id) AND (op.tenant_id_ = app.tenant_id_))))
    
         JOIN permission_role pr ON (((app.permission_pack_id = pr.permission_pack_id) AND (pr.tenant_id_ = app.tenant_id_))))
    
         LEFT JOIN filter ON ((((op.filter)::text = (filter.id)::text) AND (op.tenant_id_ = filter.tenant_id_))))"; 
    }
}