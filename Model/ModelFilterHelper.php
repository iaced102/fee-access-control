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


    public static function getJoinedSQL($table, $columns, $filter, $relatedColumns)
    {
        $joinInfo = $filter['joinInfo'];
        $tb2Name = $joinInfo['table'];

        $condition = [];
        foreach ($joinInfo['condition'] as $c) {
            $col1 = $c['column1'];
            $col2 = $c['column2'];
            $operator = $c['operator'];
            $condition[] = " tb1.$col1 $operator tb2.$col2";
        }

        $condition = implode(' AND ', $condition);
        $items = self::getMaskedItems($joinInfo, $columns, $relatedColumns);
        $items = array_values($items);
        $items = implode(" , ", $items);
        $sql = "SELECT $items FROM $table AS tb1 LEFT JOIN $tb2Name AS tb2 ON $condition";
        return "($sql) tb_temp ";
    }

    public static function getMaskedItems($joinInfo, $columns, $relatedColumns)
    {
        $items = [];
        foreach ($relatedColumns as $colName => $flag) {
            $items[$colName] = "tb1.$colName";
        }

        $map = [];
        foreach ($joinInfo['masks'] as $item) {
            $map[$item['column1']] = $item['column2'];
        }

        foreach ($columns as $col) {
            if (is_array($col)) {
                $name = $col['name'];
                if (array_key_exists($name, $map)) {
                    $items[$col['name']] = "tb2." . $map[$name] . " AS $name";
                } else {
                    $items[$col['name']] = "tb1." . $name;
                }
            } else {
                if (array_key_exists($col, $map)) {
                    $items[$col] = "tb2." . $map[$col] . " AS $col";
                } else {
                    $items[$col] = "tb1." . $col;
                }
            }
        }
        return $items;
    }

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
        $relatedColumns = [];
        $filter = self::standardlizeFilterData($filter, $filterableColumns, $selectableColumns);
        $columns = self::getColumnArrForSelect($filter, $selectableColumns, $relatedColumns);
        $where = self::getWhereCondition($filter, $filterableColumns, $columns, $relatedColumns);
        $table = self::getFrom($table);
        $limit = $filter['pageSize'] . " OFFSET " . (($filter['page'] - 1) * $filter['pageSize']);
        $sort = self::getSort($filter, $columns, $relatedColumns);

        $groupBy = self::getGroupBy($filter, $relatedColumns);
        $distnct = '';
        if (array_key_exists('distinct', $filter) && ($filter['distinct'] === true || $filter['distinct'] === 'true')) {
            $distnct = 'DISTINCT';
        }

        if (count($filter['joinInfo']) > 0) {
            $table = self::getJoinedSQL($table, $columns, $filter, $relatedColumns);
        }
        $columns = implode("\" , \"", $columns);
        $columns = "\"$columns\"";
        return [
            'full'  => " SELECT $distnct $columns FROM $table $where $groupBy $sort LIMIT $limit ",
            'count' => " SELECT COUNT(*) as count_items FROM (SELECT $distnct $columns FROM $table $where $groupBy) tmp_table",
        ];
    }

    private static function getGroupBy($filter, &$relatedColumns)
    {
        $groupBy = "";
        if (array_key_exists('groupBy', $filter) && count($filter['groupBy']) > 0) {
            $groupByColumns = implode("\" , \"", $filter['groupBy']);
            $groupByColumns = "\"$groupByColumns\"";
            $groupBy = "GROUP BY " . $groupByColumns;
            foreach ($filter['groupBy'] as $colName) {
                $relatedColumns[$colName] = true;
            }
        }
        return $groupBy;
    }

    private static function getSort($filter, $columns, &$relatedColumns)
    {
        $sort = '';
        if (array_key_exists('sort', $filter)) {
            $sort = [];
            foreach ($filter['sort'] as $item) {
                if (array_search($item['column'], $columns) !== false) {
                    $sort[] = '"' . $item['column'] . '" ' . $item['type'];
                    $relatedColumns[$item['column']] = true;
                }
            }

            if (count($sort) > 0) {
                $sort = implode(' , ', $sort);
                $sort = " ORDER BY $sort";
            } else {
                $sort = '';
            }
        }

        return $sort;
    }

    private static function getFrom($table)
    {
        if (stripos($table, "select ") !== false) {
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
    private static function getColumnArrForSelect($filter, $selectableColumns, &$relatedColumns)
    {
        $columns = [];
        if (array_key_exists('columns', $filter) && count($filter['columns']) > 0) {
            $columns = $filter['columns'];
        } else {
            foreach ($selectableColumns as $col) {
                if (is_array($col)) {
                    $columns[] = $col['name'];
                } else if (is_string($col)) {
                    $columns[] = $col;
                }
                $relatedColumns[$columns[count($columns) - 1]] = true;
            }
        }
        return $columns;
    }

    private static function getWhereCondition($filter, $filterableColumns, $columns, &$relatedColumns)
    {
        $whereItems = [];
        foreach ($filter['filter'] as $filterItem) {
            $str = self::convertConditionToWhereItem($filterItem, $filterableColumns, $relatedColumns);
            if ($str != '') {
                $whereItems[] = $str;
            }
        }

        // get search query
        $searchKey = $filter['search'];
        if (trim($searchKey) != '') {
            $searchConditions = [];
            foreach ($columns as $colName) {
                $searchConditions[] = " CAST(\"$colName\" AS VARCHAR) ILIKE '%$searchKey%' ";
            }

            if (count($searchConditions) > 0) {
                $whereItems[] = "(" . implode(" OR ", $searchConditions) . ")";
            }
        }
        $whereItems = implode(" AND ", $whereItems);

        $where = '';
        if (trim($whereItems) != '') {
            $where = " WHERE $whereItems " . $filter['stringCondition'];
        } else if ($filter['stringCondition'] != '') {
            $where = " WHERE " . $filter['stringCondition'];
        }


        return $where;
    }

    /**
     * Chuyển đổi các condition từ filter thành các item trong điều kiện where của truy vấn
     *
     * @param array $conditionItem 
     * @return string
     */
    public static function convertConditionToWhereItem($conditionItem, $filterableColumns, &$relatedColumns)
    {
        $colName = $conditionItem['column'];
        $relatedColumns[$colName] = true;
        $mapColumns = [];
        foreach ($filterableColumns as $col) {
            $mapColumns[$col['name']] = $col;
        }
        $conds = [];

        if (array_key_exists('conditions', $conditionItem) && count($conditionItem['conditions']) > 0) {
            $cond = [];
            foreach ($conditionItem['conditions'] as $item) {
                $value = '';
                if (array_key_exists('value', $item)) {
                    $value = $item['value'];
                }

                if (!($item['name'] == 'contains' && $value == '')) {
                    $cond[] = self::bindValueToWhereItem($item['name'], $colName, $value, $mapColumns);
                }
            }

            $conjunction = array_key_exists('operation', $conditionItem) ? $conditionItem['operation'] : ' AND';
            $conds[] = implode(" " . $conjunction . " ", $cond);
        }

        if (array_key_exists('valueFilter', $conditionItem)) {
            $colType = $mapColumns[$colName];
            $values = '';
            if ($colType == 'number') {
                $values = implode(' , ', $conditionItem['valueFilter']['values']);
                $values = "($values)";
            } else {
                $values = implode("' , '", $conditionItem['valueFilter']['values']);
                $values = "('$values')";
            }
            $op = $conditionItem['valueFilter']['operation'];
            $conds[] = "\"$colName\" $op $values ";
        }
        return implode(' AND ', $conds);
    }

    public static function bindValueToWhereItem($op, $colName, $value, $mapColumns)
    {

        $COLUMN = 'SYMPER_COLUMN_PLACE_HOLDER';
        $VALUE = 'SYMPER_VALUE_PLACE_HOLDER';

        if (array_key_exists($colName, $mapColumns)) {
            $colDef = $mapColumns[$colName];
            if ($op == 'in' || $op == 'not_in') {
                $colType = $colDef['type'];
                if ($colType == 'number') {
                    $value = implode(' , ', $value);
                    $value = "($value)";
                } else {
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
        if (array_key_exists($colName, $mapColumns)) {
            $colDef = $mapColumns[$colName];
            if (!array_key_exists($op, self::$notCheckType)) {
                if ($colDef['type'] != 'number') {
                    $value = "'$value'";
                }
                $colName = "\"$colName\"";
            } else if ($colDef['type'] != 'string') {
                $colName = "CAST(\"$colName\" AS VARCHAR)";
            }
        }

        $str = str_replace($COLUMN, $colName, $str);
        $str = str_replace($VALUE, $value, $str);
        return $str;
    }

    private static function standardlizeFilterData($filter, $filterableColumns, $selectableColumns)
    {
        $result = $filter;
        if (!array_key_exists('page', $filter)) {
            $result['page'] = 1;
        }

        if (!array_key_exists('pageSize', $filter)) {
            $result['pageSize'] = 50;
        }

        if (!array_key_exists('filter', $filter)) {
            $result['filter'] = [];
        }

        if (!array_key_exists('search', $filter)) {
            $result['search'] = '';
        }

        $result = self::toJoinConditionIfExist($result, $filterableColumns, $selectableColumns);
        return $result;
    }

    public static function toJoinConditionIfExist($filter, $filterableColumns, $selectableColumns)
    {
        $joinCond = [
            'table'     => "",
            'condition' => [
                // cấu trúc data như bên dưới
                // [
                //     'column1'   => 'user_create',
                //     'operator'  => '=',
                //     'column2'   => 'email'
                // ]
            ],
            'masks'      => [
                // cấu trúc data như bên dưới
                // [
                //     'column1'  => 'user_create',
                //     'column2'  => 'display_name'
                // ]
            ]
        ];
        $cols = array_merge($filterableColumns, $selectableColumns);
        foreach ($cols as $key => $col) {
            if(array_key_exists('linkTo', $col)){
                $linkTo = $col['linkTo'];
                $joinCond['table'] = $linkTo['table'];
                $joinCond['condition'][] = [
                    'column1'   => $col['name'],
                    'operator'  => '=',
                    'column2'   => $linkTo['column']
                ];

                $joinCond['masks'][] = [
                    'column1'   => $col['name'],
                    'column2'   => $linkTo['mask']
                ];
            }
        }

        if(count($joinCond['condition']) > 0){
            $filter['joinInfo'] = $joinCond;
        }
        return $filter;
    }
}