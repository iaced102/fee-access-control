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
        'id'        =>  [ 'name' => 'id', 'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'fullName'  =>  [ 'name' => 'full_name', 'type' => 'string'],
        'email'     =>  [ 'name' => 'email', 'type' => 'string'],
        'password'  =>  [ 'name' => 'password', 'type' => 'string']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'users';
    }
    public static function getColumnNameInDataBase($fieldName, $returnArray = false){
        if(isset(self::$mappingFromDatabase[$fieldName]['name'])){
            if($returnArray){
                return self::$mappingFromDatabase[$fieldName];
            }
            else{
                return self::$mappingFromDatabase[$fieldName]['name'];
            }
        }
    }
    public function getProfile(){
        $profile = [
            'id'        =>  $this->id,
            'email'     =>  $this->email,
            'fullName'  =>  $this->fullName
        ];
        return $profile;
    }
    /*  Dev create: Dinh
    *   CreateTime: 24/06/2020
    *   description: lấy primary column name, nếu không tìm thấy thì mặc định trả về id
    */
    public static function getPrimaryKey(){
        foreach(self::$mappingFromDatabase as $fieldName=> $ColumnData){
            if(isset($ColumnData['primary']) && $ColumnData['primary']==true && isset($ColumnData['name'])){
                return $fieldName;
            }
        }
        return 'id';
    }
    
}