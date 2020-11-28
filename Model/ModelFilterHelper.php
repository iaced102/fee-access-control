<?php
/**
 * Class phục vụ cho việc tạo ra câu lệnh SQL cho việ filter các bản ghi
 */
class ModelFilterHelper{
    
    public static $notCheckType = [
        'begins_with'   => true,
        'ends_with'     => true,
        'contains'      => true,
        'not_contain'   => true,
    ];


    /**
     * Hàm lấy danh sách bản ghi dựa theo điều kiện filter (search, order, filter by value, filter by condition)
     *
     * @param string $table tên bảng cần filter hoặc một câu lệnh SQL trả về dữ liệu
     * @param array $filter cấu hình filter để lọc dữ liệu
     * @param array $filterableColumns Danh sách các cột có thể filter, dưới dạng : [[
     *      'name' => 'abgc',
     *      'type'  => 'number' // các kiểu dữ liệu trùng với các kiểu được khai báo trong model
     * ]]
     * @param array $selectableColumns Danh sách các cột có thể select để lấy ra, có thể dưới dạng 
     *      ['tên cột 1', 'tên cột 2',...] hoặc dưới dạng giống như biến $filterableColumns
     * @return void
     */
    public static function getSQLFromFilter($table, $filter, $filterableColumns, $selectableColumns)
    {
        $filter = self::standardlizeFilterData($filter);
        $columns = self::getColumnArrForSelect($filter, $selectableColumns);
        $where = self::getWhereCondition($filter, $filterableColumns, $columns);
        $table = self::getFrom($table);
        $limit = $filter['pageSize']." OFFSET ".(($filter['page']-1)*$filter['pageSize']);
        $sort = self::getSort($filter, $columns);

        $columns = implode("\" , \"", $columns);
        $columns = "\"$columns\"";

        $distnct = '';
        if(array_key_exists('distinct', $filter) && ($filter['distinct'] === true || $filter['distinct'] === 'true')){
            $distnct = 'DISTINCT';
        }

        return [
            'full'  => " SELECT $distnct $columns FROM $table $where $sort LIMIT $limit ",
            'count' => " SELECT COUNT(*) as count_items FROM $table $where ",
        ];
    }   

    private static function getSort($filter, $columns)
    {
        $sort = '';
        if(array_key_exists('sort', $filter)){
            $sort = [];
            foreach ($filter['sort'] as $item) {
                if(array_search($item['column'],$columns)){
                    $sort[] = '"'.$item['column'].'" '.$item['type'];
                }
            }

            if(count($sort) > 0){
                $sort = implode(' , ', $sort);
                $sort = " ORDER BY $sort";
            }else{
                $sort = '';
            }
        }

        return $sort;
    }

    private static function getFrom($table)
    {
        if(stripos($table, "select ") !== false){
            $table = "( $table ) as symper_tmp_table ";
        }
        return $table;
    }

    /**
     * Lấy danh sách các cột cần có trong mệnh đề select của câu SQL
     *
     * @param array $filter cấu hình filter truyền vào
     * @param array $selectableColumns danh sách các cột có thể đưa vào mệnh đề select do dev quy định khi tạo Model hoặc truyền vào
     * @return array
     */
    private static function getColumnArrForSelect($filter, $selectableColumns)
    {
        $columns = [];
        if(array_key_exists('columns', $filter) && count($filter['columns']) > 0){
            $columns = $filter['columns'];
        }else{
            foreach ($selectableColumns as $col) {
                if(is_array($col)){
                    $columns[] = $col['name'];
                }else if(is_string($col)){
                    $columns[] = $col;
                }
            }
        }
        return $columns;
    }

    private static function getWhereCondition($filter, $filterableColumns, $columns)
    {
        $whereItems = [];
       
        foreach ($filter['filter'] as $filterItem) {
            $str = self::convertConditionToWhereItem($filterItem, $filterableColumns);
            if($str != ''){
                $whereItems[] = $str;
            }
        }
        // get search query
        $searchKey = $filter['search'];
        if(trim($searchKey) != ''){
            $searchConditions = [];
            foreach ($columns as $colName) {
                $searchConditions[] = " CAST(\"$colName\" AS VARCHAR) ILIKE '%$searchKey%' ";
            }

            if(count($searchConditions) > 0){
                $whereItems[] = "(".implode(" OR ", $searchConditions).")";
            }
        }
        $whereItems = implode(" AND ", $whereItems);

        $where = '';
        if(trim($whereItems) != ''){
            $where = " WHERE $whereItems ";
        }

        return $where;
    }

    /**
     * Chuyển đổi các condition từ filter thành các item trong điều kiện where của truy vấn
     *
     * @param array $conditionItem 
     * @return string
     */
    public static function convertConditionToWhereItem($conditionItem, $filterableColumns)
    {
        $colName = $conditionItem['column'];
        $mapColumns = [];
        foreach ($filterableColumns as $col) {
            $mapColumns[$col['name']] = $col;
        }
        $conds = [];

        if( array_key_exists('conditions', $conditionItem) && count($conditionItem['conditions']) > 0){
            $cond = [];
            foreach ($conditionItem['conditions'] as $item) {
                $value = '';
                if(array_key_exists('value', $item)){
                    $value = $item['value'];
                }

                if(!($item['name'] == 'contains' && $value == '')){
                    $cond[] = self::bindValueToWhereItem($item['name'], $colName, $value, $mapColumns);
                }
            }

            $conjunction = array_key_exists('operation', $conditionItem) ? $conditionItem['operation'] : ' AND';
            $conds[] = implode(" ".$conjunction." ", $cond);
        }

        if(array_key_exists('valueFilter', $conditionItem)){
            $colType = $mapColumns[$colName];
            $values = '';
            if($colType == 'number'){
                $values = implode(' , ', $conditionItem['valueFilter']['values']);
                $values = "($values)";
            }else{
                $values = implode("' , '", $conditionItem['valueFilter']['values']);
                $values = "('$values')";
            }
            $op = $conditionItem['valueFilter']['operation'];
            $conds[] = "\"$colName\" $op $values ";
        }
        return implode(' AND ', $conds);
    }

    public static function bindValueToWhereItem( $op ,$colName, $value, $mapColumns)
    {
        
        $COLUMN = 'SYMPER_COLUMN_PLACE_HOLDER';
        $VALUE = 'SYMPER_VALUE_PLACE_HOLDER';

        if(array_key_exists($colName, $mapColumns)){
            $colDef = $mapColumns[$colName];
            if($op == 'in' || $op == 'notIn'|| $op == 'not_in'){
                $colType = $colDef['type'];
                if($colType == 'number'){
                    $value = implode(' , ', $value);
                    $value = "($value)";
                }else{
                    $value = implode("' , '", $value);
                    $value = "('$value')";
                }
            }
        }
        
        $mapOpertationToSQL = [
            'empty'                 => "($COLUMN IS NULL OR $COLUMN = '' ) ",
            'not_empty'             => "($COLUMN IS NOT NULL AND $COLUMN != '' ) ",
            'equal'                 => "$COLUMN = $VALUE",
            'not_equal'             => "$COLUMN != $VALUE",
            'greater_than'          => "$COLUMN > $VALUE",
            'greater_than_or_equal' => "$COLUMN >= $VALUE",
            'less_than'             => "$COLUMN < $VALUE",
            'less_than_or_equal'    => "$COLUMN <= $VALUE",
            'begins_with'           => "$COLUMN ILIKE '$VALUE%'",
            'ends_with'             => "$COLUMN ILIKE '%$VALUE'",
            'contains'              => "$COLUMN ILIKE '%$VALUE%'",
            'not_contain'           => "$COLUMN NOT ILIKE '%$VALUE%'",
            'in'                    => "$COLUMN IN $value",
            'not_in'                => "$COLUMN NOT IN $value",
        ];

        $str = $mapOpertationToSQL[$op];
        if(array_key_exists($colName, $mapColumns)){
            $colDef = $mapColumns[$colName];
            if(!array_key_exists($op, self::$notCheckType)){
                if($colDef['type'] != 'number'){
                    $value = "'$value'";
                }
                $colName = "\"$colName\"";
            }else if($colDef['type'] != 'string'){
                $colName = "CAST(\"$colName\" AS VARCHAR)";
            }
        }

        $str = str_replace($COLUMN, $colName, $str);
        $str = str_replace($VALUE, $value, $str);
        return $str;
    }

    private static function standardlizeFilterData($filter)
    {
        $result = $filter;
        if(!array_key_exists('page', $filter)){
            $result['page'] = 1;
        }

        if(!array_key_exists('pageSize', $filter)){
            $result['pageSize'] = 50;
        }

        if(!array_key_exists('filter', $filter)){
            $result['filter'] = [];
        }

        if(!array_key_exists('search', $filter)){
            $result['search'] = '';
        }

        return $result;
    }
}