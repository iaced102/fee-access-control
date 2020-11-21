<?php
namespace Model;
use SqlObject;
class Users extends SqlObject
{
    public $id,
        $fullName,
        $email,
        $password;
    public static $mappingFromDatabase = [
        'id'        =>  [ 'name' => 'id',           'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'fullName'  =>  [ 'name' => 'full_name',    'type' => 'string'],
        'email'     =>  [ 'name' => 'email',        'type' => 'string'],
        'password'  =>  [ 'name' => 'password',     'type' => 'string']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'users';
    }
    
    public function getProfile(){
        $profile = [
            'id'        =>  $this->id,
            'email'     =>  $this->email,
            'fullName'  =>  $this->fullName
        ];
        return $profile;
    }
}