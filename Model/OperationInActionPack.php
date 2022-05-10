<?php
namespace Model;
use SqlObject;
class OperationInActionPack extends SqlObject
{
    public $id,
        $operationId,
        $actionPackId,
        $formulaStruct,
        $formulaValue,
        $filter,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',               'type' => 'string', 'primary'=>true],
        'operationId'       =>  [ 'name' => 'operation_id',     'type' => 'string'],
        'actionPackId'      =>  [ 'name' => 'action_pack_id',   'type' => 'string'],
        'formulaStruct'     =>  [ 'name' => 'formula_struct',   'type' => 'string'],
        'formulaValue'      =>  [ 'name' => 'formula_value',   'type' => 'string'],
        'filter'            =>  [ 'name' => 'filter',           'type' => 'string'],
        'tenantId'          => [ 'name' => 'tenant_id_',        'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'operation_in_action_pack';
    }
    public static function getTopicName(){
       return 'operation_in_action_pack';
    }
}