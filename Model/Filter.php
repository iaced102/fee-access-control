<?php
namespace Model;
use SqlObject;
class Filter extends SqlObject
{
    public const STATUS_ENABLE  = 1;
    public $id,
        $userId,
        $createTime,
        $name,
        $description,
        $formula,
        $formulaStruct,
        $status,
        $objectIdentifier;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'userId'            =>  [ 'name' => 'user_id',              'type' => 'string'],
        'createTime'        =>  [ 'name' => 'create_time',          'type' => 'datetime'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'formula'           =>  [ 'name' => 'formula',              'type' => 'string'],
        'formulaStruct'    =>  [ 'name' => 'formula_struct',       'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'filter';
    }
    public static function getTopicName(){
       return 'filter';
    }
}