<?php
namespace Model;
use SqlObject;
class Filter extends SqlObject
{
    public $id,
        $userId,
        $createTime,
        $name,
        $description,
        $formula,
        $status,
        $objectIdenfifier;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'userId'            =>  [ 'name' => 'user_id',              'type' => 'number'],
        'createTime'        =>  [ 'name' => 'create_time',          'type' => 'datetime'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'formula'           =>  [ 'name' => 'formula',              'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'objectIdenfifier'  =>  [ 'name' => 'object_idenfifier',    'type' => 'string'],
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