<?php

use Model\Model;
use Library\Auth;
class SqlObject extends Model{
    public $listForeignKey;

    public function __construct($data = []){
        $this->listForeignKey=[];
        $keys=get_object_vars($this);
        foreach ($keys as $key => $_) {
            if($key!='listForeignKey'){
                $columnNameInDatabase = static::getColumnNameInDataBase($key); 
                if(isset($data[$columnNameInDatabase])){
                    $this->$key = isset($data[$columnNameInDatabase]) ? $data[$columnNameInDatabase] : '';
                }
                else if(isset($data[$key])){
                    $this->$key = $data[$key];
                }
            }
        }
    }
    public function encode(){
        $arrayKey=get_object_vars($this);
        foreach($arrayKey as $key=>$value){
            if(is_string($value)){
                $this->$key=addslashes($value);
            }
        }
    }
    public function getForeignKey($SourceField){
        if(isset($this->listForeignKey)){
            foreach($this->listForeignKey as $fk){
                if($fk->SourceField==$SourceField) return $fk;
            }
        }
        return null;
    }
     /*  Dev create: Dinh
    *   CreateTime: 31/03/2020
    *   description: lấy condition query để lọc theo TanentId. Đầu vào là field chứa tenantid id, nếu không tìm thấy column in db thì đó là table dùng chung
    *   Nếu có tanent id trong db, thì kiểm tra JWT data, nếu tenantid = '' nghĩa là supporter tối cao, có thể query, nếu có tenantid , thì chỉ chỉ được phép lọc theo tenantid id đó 
    */
    public static function getFilterTenantQueryByFieldName($fieldNameTanentId){
        $tableName          = static::getTableName();
        $columnInDatabase   = static::getColumnNameInDataBase($fieldNameTanentId);
        if($columnInDatabase != false){
            $tenantId = Auth::getTenantId();
            if($tenantId != ''){
                return "$tableName.$columnInDatabase = '$tenantId'";
            }
        }    
        return '';
    }
    
}