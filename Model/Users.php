<?php
namespace Model;
use SqlObject;
class Users extends SqlObject
{
    public 
        $id,
        $firstName,
        $lastName,
        $userName,
        $displayName,
        $email,
        $phone,
        $status = 1,
        $avatar,
        $createAt,
        $updateAt;
    public static $mappingFromDatabase = [
        'id'            =>  [ 'name' => 'id',              'type' => 'number','primary'=>true, 'auto_increment' => true],
        'firstName'     =>  [ 'name' => 'first_name',      'type' => 'string'],
        'lastName'      =>  [ 'name' => 'last_name',       'type' => 'string'],
        'userName'      =>  [ 'name' => 'user_name',       'type' => 'string'],
        'displayName'   =>  [ 'name' => 'display_name',    'type' => 'string'],
        'email'         =>  [ 'name' => 'email',           'type' => 'string'], 
        'phone'         =>  [ 'name' => 'phone',           'type' => 'string'],
        'status'        =>  [ 'name' => 'status',          'type' => 'number'],
        'avatar'        =>  [ 'name' => 'avatar',          'type' => 'string'],
        'createAt'      =>  [ 'name' => 'create_at',       'type' => 'datetime'],
        'updateAt'      =>  [ 'name' => 'update_at',       'type' => 'datetime'],
    ];
    public function __construct($data=[]){
        parent::__construct($data,false);
    }
    public static function getTableName(){
        return 'users';
    }
   
    public function getProfile(){
        $profile = [
            'id'        =>  $this->id,
            'email'     =>  $this->email,
            'fullName'  => $this->displayName
        ];
        return $profile;
    }
}