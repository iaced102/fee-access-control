<?php
namespace Model;
use SqlObject;
class OperationInActionPack extends SqlObject
{
    public $id,
        $operationId,
        $actionPackId,
        $filter;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',               'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'operationId'       =>  [ 'name' => 'operation_id',     'type' => 'number'],
        'actionPackId'      =>  [ 'name' => 'action_pack_id',   'type' => 'number'],
        'filter'            =>  [ 'name' => 'filter',           'type' => 'string']
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