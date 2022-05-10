<?php
namespace Model;
use SqlObject;
class ServerKey extends SqlObject
{
    public const STATUS_ENABLE  = 1;
    public const STATUS_DISABLED  = 0;
    public $id,
        $name,
        $description,
        $serverKey,
        $status,
        $createAt,
        $updateAt,
        $userCreate,
        $userUpdate,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'            =>  [ 'name' => 'id',           'type' => 'string', 'primary'=>true],
        'name'          =>  [ 'name' => 'name',         'type' => 'string'],
        'description'   =>  [ 'name' => 'description',  'type' => 'string'],
        'serverKey'     =>  [ 'name' => 'server_key',   'type' => 'string'],
        'status'        =>  [ 'name' => 'status',       'type' => 'number'],
        'createAt'      =>  [ 'name' => 'create_at',    'type' => 'datetime'],
        'updateAt'      =>  [ 'name' => 'update_at',    'type' => 'datetime'],
        'userCreate'    =>  [ 'name' => 'user_create',  'type' => 'string'],
        'userUpdate'    =>  [ 'name' => 'user_update',   'type' => 'string'],
        'tenantId'      => [ 'name' => 'tenant_id_', 'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'server_key';
    }
    public static function getTopicName(){
        return 'server_key';
    }
}