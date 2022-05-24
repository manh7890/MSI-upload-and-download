<?php

/**
 * Class phục vụ cho việc tạo ra câu lệnh SQL cho việ filter các bản ghi
 */
class ModelFilterHelper
{

    public static $notCheckType = [
        'begins_with'   => true,
        'ends_with'     => true,
        'contains'      => true,
        'not_contain'   => true,
    ];


    public static function getAllRelatedColumns($filter)
    {
        $rsl = [];

        return $rsl;
    }

    public static function getMultilJoinTable($info)
    {
        // LEFT JOIN users AS tb2 ON tb1.user_last_update = tb2.email
        $rsl = [];
        foreach ($info as $item) {
            $joinTable = $item['table'];
            $ttb = $item['table2TmpName'];
            $col1 = $item['column1'];
            $col2 = $item['column2'];
            if(array_key_exists('castTypeColumn1', $item)){
                $col1 = "$col1::".$item['castTypeColumn1'];
            }
            if(array_key_exists('castTypeColumn2', $item)){
                $col2 = "$col2::".$item['castTypeColumn2'];
            }
            $rsl[] = "LEFT JOIN $joinTable AS $ttb ON tb1.$col1 = $ttb.$col2";
        }
        return implode(' ', $rsl);
    }

    public static function getJoinedSQL($table, $filter, $relatedColumns)
    {
        $joinInfo = $filter['linkTable'];
        $items = self::getMaskedItems($joinInfo, $relatedColumns);
        $moreJoin = self::getMultilJoinTable($joinInfo);
        $items = array_values($items);
        $items = implode(" , ", $items);
        $sql = "SELECT $items FROM $table AS tb1 $moreJoin";
        return "($sql) tb_temp ";
    }
    

    public static function getMaskedItems($joinInfo, $relatedColumns)
    {
        $items = [];
        foreach ($relatedColumns as $colName => $flag) {
            $items[$colName] = "tb1.$colName";
        }

        $map = [];
        foreach ($joinInfo as $item) {
            $map[$item['column1']] = $item;
        }
        $columns = array_keys($relatedColumns);
        foreach ($columns as $col) {
            if (is_array($col)) {
                $name = $col['name'];
                if (array_key_exists($name, $map)) {
                    $mask = $map[$name]['mask'];
                    $tempTb = $map[$name]['table2TmpName'];            
                    $items[$col['name']] = "$tempTb." . $mask . " AS $name";
                } else {
                    $items[$col['name']] = "tb1." . $name;
                }
            } else {
                if (array_key_exists($col, $map)) {
                    $mask = $map[$col]['mask'];
                    $tempTb = $map[$col]['table2TmpName'];            
                    $items[$col] = "$tempTb." . $mask . " AS $col";
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

        if (count($filter['linkTable']) > 0) {
            $table = self::getJoinedSQL($table, $filter, $relatedColumns);
        }
        
        if(array_key_exists('aggregate', $filter)){
            $columns = self::getSelectItemsWhenHasAgg($filter['aggregate'], $groupBy);
        }else{
            $columns = implode("\" , \"", $columns);
            $columns = "\"$columns\"";
        }

        return [
            'full'  => " SELECT $distnct $columns FROM $table $where $groupBy $sort LIMIT $limit ",
            'count' => " SELECT COUNT(*) as count_items FROM (SELECT $distnct $columns FROM $table $where $groupBy) tmp_table",
        ];
    }
    
    public static function getSelectItemsWhenHasAgg($aggCols, $groupBy)
    {
        $columns = [];
        if($groupBy != ''){
            $groupByCol = str_replace('GROUP BY ', '', $groupBy);
            $columns[] = $groupByCol;
        }
        foreach ($aggCols as $item) {
            $func = $item['func'];
            $col = $item['column'];
            $columns[] = "$func($col) AS $col";
        }
        $columns = implode(' , ', $columns);
        return $columns;
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
        if(array_key_exists('aggregate', $filter) && count($filter['aggregate']) > 0){
            foreach ($filter['aggregate'] as $item) {
                $relatedColumns[$item['column']] = true;
                $columns[] = $item['column'];
            }
        } else if (array_key_exists('columns', $filter) && count($filter['columns']) > 0) {
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
            $searchKey = pg_escape_string("$searchKey");
            $searchConditions = [];
            $searchColumns = $columns;
            if($filter['searchColumns'] != '*'){
                $searchColumns = explode(',', $filter['searchColumns']);
            }
            foreach ($searchColumns as $colName) {
                $relatedColumns[$colName] = true;
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
        $dataType = isset($conditionItem['dataType']) ? $conditionItem['dataType'] : 'text';
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

                if (!($item['name'] == 'contains' && $value == '')) {$condItem = self::bindValueToWhereItem($item['name'], $colName, $value, $mapColumns, $dataType);
                    if($condItem){
                        $cond[] = $condItem;
                    }
                }
            }

            $conjunction = array_key_exists('operation', $conditionItem) ? $conditionItem['operation'] : ' AND';
            $conds[] = implode(" " . $conjunction . " ", $cond);
        }

        if (array_key_exists('valueFilter', $conditionItem)) {
            $colType = $mapColumns[$colName];
            $values = '';
            foreach ($conditionItem['valueFilter']['values'] as $key => $vl) {
                $conditionItem['valueFilter']['values'][$key] = pg_escape_string("$vl");
            }
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

    public static function bindValueToWhereItem($op, $colName, $value, $mapColumns, $dataType)
    {

        $COLUMN = 'SYMPER_COLUMN_PLACE_HOLDER';
        $VALUE = 'SYMPER_VALUE_PLACE_HOLDER';

        if (array_key_exists($colName, $mapColumns)) {
            $colDef = $mapColumns[$colName];
            if ($op == 'in' || $op == 'not_in') {
                $colType = $colDef['type'];
                if(is_array($value)){
                    foreach ($value as $key => $vl) {
                        $value[$key] = pg_escape_string("$vl");
                    }
                }
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
        if($dataType == 'number' || $dataType == 'date' || $dataType == 'datetime'){
            $mapOpertationToSQL['empty'] = "($COLUMN IS NULL)";
            $mapOpertationToSQL['not_empty'] = "($COLUMN IS NOT NULL)";
        }

        $str = $mapOpertationToSQL[$op];
        if (array_key_exists($colName, $mapColumns)) {
            $colDef = $mapColumns[$colName];
            if (!array_key_exists($op, self::$notCheckType)) {
                $value = pg_escape_string("$value");
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

        if (!array_key_exists('searchColumns', $filter)) {
            $result['searchColumns'] = '*';
        }

        $result = self::toJoinConditionIfExist($result, $filterableColumns, $selectableColumns);
        $result = self::addParamsToJoinCondition($result);

        return $result;
    }

    public static function addParamsToJoinCondition($filter)
    {
        if(array_key_exists('linkTable', $filter)){
            $joinCond = &$filter['linkTable'];
            $counter = 2;
            foreach ($joinCond as &$cond) {
                $tmpTb = "tb$counter";
                $cond['table2TmpName'] = $tmpTb;
                $counter += 1;
            }
        }
        return $filter;
    }

    public static function uniqueMultidimArray($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();
       
        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    public static function toJoinConditionIfExist($filter, $filterableColumns, $selectableColumns)
    {
        $joinCond = [
            // [
            //     'column1'   => 'user_create',
            //     'operator'  => '=',
            //     'column2'   => 'email'
            //     'table'     => 'users'
            //     'mask'   => 'email'
            // ]
        ];
        $cols = array_merge($filterableColumns, $selectableColumns);
        $cols = self::uniqueMultidimArray($cols, 'name');
        foreach ($cols as $key => $col) {
            if(array_key_exists('linkTo', $col)){
                $linkTo = $col['linkTo'];
                $joinCond[] = [
                    'column1'   => $col['name'],
                    'operator'  => '=',
                    'column2'   => $linkTo['column'],
                    'mask'      => $linkTo['mask'],
                    'table'     => $linkTo['table']
                ];
            }
        }
        if(count($joinCond) > 0){
            $filter['linkTable'] = $joinCond;
        }
        return $filter;
    }
}