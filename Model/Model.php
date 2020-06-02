<?php
namespace Model;
use Model\Connection;
use Library\Auth;

class Model{
    public static function getTableName(){
        die(get_called_class()." not overriding getTableName");
    }
    public static function getColumnNameInDataBase($fieldName, $returnArray = false){
        die(get_called_class()." not overriding getColumnNameInDataBase");
    }
     /*  Dev create: Dinh
    *   CreateTime: 31/03/2020
    *   description: lấy condition query để lọc theo TanentId. khi đã call đến class model này nghĩa là class kế thừa không định nghĩa, không có tanentid trong db, không cần lọc 
    */
    public static function getFilterTenantQuery(){
        return '';
    }
    public static function getValueForSqlCommand($columnData,$value){
        $type = strtolower($columnData['type']);
        if($type != 'number' 
            || $type == 'integer' 
            || $type == 'double precision' 
            || $type == 'numeric' 
            || $type == 'smallint' 
            || $type == 'real'){
            $value = pg_escape_string($value);
            return  "'$value'";
        }
        else{
            return $value;
        }
    }
    /*  Dev create: Dinh
    *   CreateTime: 31/03/2020
    *   description: dinh update function get thành private, không được phép sử dụng hàm get trực tiếp từ bên ngoài,
    *   bắt buộc filter theo tanentid trước khi query.
    */
    private static function get($command,$returnObject=true){
        $className = get_called_class();
        $resultData  = Connection::getDataQuerySelect($command);
        if($returnObject){
            $arrayResult = [];
            if(!empty($resultData)){
                foreach($resultData as $row){
                    $newObj = new $className($row);
                    array_push($arrayResult,$newObj);
                }
            }
            return $arrayResult;
        }
        else{
            return $resultData;
        }
    }
    public static function getById($id){
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery(["id = $id",$filterTenantQuery]);
        $command            = "SELECT * FROM $tableName WHERE $where";
        $listObject         = self::get($command);
        if(isset($listObject[0])){
            return $listObject[0];
        }
        return false;
    }

    public static function getByAll(){
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $command            = "SELECT * FROM $tableName ";
        $command            .= $filterTenantQuery!=''?' WHERE '.$filterTenantQuery:'';
        return self::get($command);
    }
    public static function getByTop($top='',$where='',$order='',$fields=false,$otherTable=false,$hasDistinct=false){
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery([$where,$filterTenantQuery]);
        $tableName          = static::getTableName();
        $command            = "SELECT ";
        $command            .= $hasDistinct?"  DISTINCT ": ' ';
        $command            .= $fields==false ? "$tableName.*":$fields;
        $command            .= " FROM $tableName";
        $command            .= $otherTable!=false?', '.$otherTable:'';
        $command            .= $where!=''?' WHERE '.$where:'';
        $command            .= $order!=''?" ORDER BY ".$order:'';
        $command            .= $top!=''?' LIMIT '.$top:'';
        return self::get($command);
    }

    public static function getByPaging($currentPage, $pageSize,$order,$where,$fields=false,$otherTable=false,$hasDistinct=false){
        $top = $pageSize." OFFSET ".(($currentPage-1)*$pageSize);
        return self::getByTop($top,$where,$order,$fields,$otherTable,$hasDistinct);
    }

    public static function deleteMulti($where){
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery([$where,$filterTenantQuery]);
        $command            = "DELETE FROM $tableName WHERE $where";
        return connection::exeQuery($command);
    }
    public static function count($where){
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery([$where,$filterTenantQuery]);
        $tableName          = static::getTableName();
        $command            = "SELECT COUNT( DISTINCT $tableName.id) AS count FROM $tableName";
        $command            .= ($where!='')?' WHERE '.$where:'';
        return self::countByQuery($command);
    }
    public static function countByQuery($command){
        $result = 0;        
        $resultData =Connection::getDataQuerySelect($command);
        if(isset($resultData[0]['count'])){
            $result = $resultData[0]['count'];
        }
        return $result;
    }
    public function insert(){
        $listVar = get_object_vars($this);
        $tableName = static::getTableName();
        $columns = [];
        $values = [];
        $returnQuery = '';
        $returnColumn = '';
        foreach($listVar as $key => $value){
            $columnData = static::getColumnNameInDataBase($key,true);
            if(is_string($value) || is_numeric($value)){
                if(is_array($columnData) && $columnData != false && (!isset($columnData['auto_increment']) || $columnData['auto_increment']==false)){
                    $columns[] = $columnData['name'];
                    $values[]  = self::getValueForSqlCommand($columnData,$value);
                }   
            }
            if(isset($columnData['auto_increment']) && $columnData['auto_increment']==true){
                $returnQuery    = ' returning '.$columnData['name'];
                $returnColumn   = $columnData['name'];
            }
        }
        $keysCommand    = implode(",",$columns);
        $valuesCommand  = implode(",",$values);
        $command        = "INSERT INTO $tableName ($keysCommand) VALUES ($valuesCommand) $returnQuery";
        $result         = Connection::exeQuery($command);
        $this->setAutoIncrementValueAfterInsert($result,$returnQuery,$returnColumn);
        return $result;
    }
    private function setAutoIncrementValueAfterInsert($result,$returnQuery,$returnColumn){
        if($returnQuery != ''){
            $result = pg_fetch_all($result);
            if(isset($result[0][$returnColumn])){
                $result                 = $result[0][$returnColumn];
                $this->$returnColumn    = $result;
            }
        }
    }
    public function update(){
        $listVar = get_object_vars($this);
        $tableName = static::getTableName();
        $values = [];
        foreach($listVar as $key=>$value){
			if(is_string($value) || is_numeric($value)){
                $columnData = static::getColumnNameInDataBase($key,true);
                $columnName = $columnData['name'];
                $value      = self::getValueForSqlCommand($columnData,$value);
				$values[]   =  "$columnName = $value";
			}
        }
        $keysCommand = implode(",",$values);
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery(["id = ".$this->id,$filterTenantQuery]);
        $command            = "UPDATE ".$tableName." SET $keysCommand WHERE $where";
        return connection::exeQuery($command);
    }

    public function delete()
    {
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $where              = self::mergeConditionQuery(["id = ".$this->id,$filterTenantQuery]);
        $command            = "DELETE FROM $tableName WHERE $where";
        return connection::exeQuery($command);
    }
    private static function mergeConditionQuery($listQuery){
        $listQuery = array_filter($listQuery);
        return implode(' AND ',$listQuery);
    }
}