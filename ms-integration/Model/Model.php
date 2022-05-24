<?php
namespace Model;
use Model\Connection;
use Library\Auth;
use ModelFilterHelper;

class Model{
    public static $mappingFromDatabase = [];
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
    public static function getPrimaryKey(){
        return 'id';
    }
    public static function getValueForSqlCommand($columnData,$value){
        $type = strtolower($columnData['type']);
        if(!($type == 'number' 
            || $type == 'integer' 
            || $type == 'double precision' 
            || $type == 'numeric' 
            || $type == 'smallint' 
            || $type == 'real')){
            $value = pg_escape_string($value);
            return  "'$value'";
        }
        else{
            return $value===null||$value===''?'null':$value;
        }
    }
    /*  Dev create: Dinh
    *   CreateTime: 31/03/2020
    *   description: dinh update function get thành private, không được phép sử dụng hàm get trực tiếp từ bên ngoài,
    *   bắt buộc filter theo tanentid trước khi query.
    */
    private static function get($command,$returnObject=true,$returnArrayKeyAsField=false){
        $className = get_called_class();
        $resultData  = Connection::getDataQuerySelect($command);
        if($returnObject){
            $arrayResult = [];
            if(!empty($resultData)){
                foreach($resultData as $row){
                    $newObj = new $className($row);
                    
                    if($returnArrayKeyAsField===true){
                        $primaryKey = static::getPrimaryKey();
                        $arrayResult[$newObj->$primaryKey] = $newObj;
                    }
                    else if(is_string($returnArrayKeyAsField)){
                        $arrayResult[$newObj->$returnArrayKeyAsField] = $newObj;
                    }
                    else{
                        array_push($arrayResult,$newObj);
                    }
                    
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
        $primaryKey = static::getPrimaryKey();
        $primaryColumnData  = static::getColumnNameInDataBase($primaryKey,true);
        $primaryColumnName  = $primaryColumnData['name']; 
        $primaryValue       = self::getValueForSqlCommand($primaryColumnData,$id);
        $where              = self::mergeConditionQuery([$primaryColumnName. " = ".$primaryValue,$filterTenantQuery]);
        $command            = "SELECT * FROM $tableName WHERE $where";
        $listObject         = self::get($command);
        if(isset($listObject[0])){
            return $listObject[0];
        }
        return false;
    }

    public static function getByAll($returnArrayKeyAsField=false){
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $command            = "SELECT * FROM $tableName ";
        $command            .= $filterTenantQuery!=''?' WHERE '.$filterTenantQuery:'';
        return self::get($command,true,$returnArrayKeyAsField);
    }
    public static function getByTop($top='',$where='',$order='',$fields=false,$otherTable=false,$hasDistinct=false,$returnArrayKeyAsField=false){
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
        return self::get($command,true,$returnArrayKeyAsField);
    }

    public static function getByPaging($currentPage, $pageSize,$order,$where,$fields=false,$otherTable=false,$hasDistinct=false,$returnArrayKeyAsField=false){
        $top = $pageSize." OFFSET ".(($currentPage-1)*$pageSize);
        return self::getByTop($top,$where,$order,$fields,$otherTable,$hasDistinct,$returnArrayKeyAsField);
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
        $primaryKey = static::getPrimaryKey();
        $primaryColumnData  = static::getColumnNameInDataBase($primaryKey,true);
        $primaryColumnName  = $primaryColumnData['name'];
        $command            = "SELECT COUNT( DISTINCT $tableName.$primaryColumnName) AS count FROM $tableName";
        $command            .= ($where!='')?' WHERE '.$where:'';
        return self::countByQuery($command);
    }
    public static function countByQuery($command){
        $result = 0;        
        $resultData =Connection::getDataQuerySelect($command);
        if(isset($resultData[0]['count'])){
            $result = $resultData[0]['count'];
        }
        return intval($result);
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
                if(is_array($columnData) && $columnData != false && (!isset($columnData['auto_increment']) || $columnData['auto_increment']==false ||$value!='')){
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
    public function save(){
        $primaryKey = static::getPrimaryKey();
        $primaryColumnData  = static::getColumnNameInDataBase($primaryKey,true);
        $primaryColumnName  = $primaryColumnData['name'];
        $primaryValue       = self::getValueForSqlCommand($primaryColumnData,$this->$primaryKey);
        if($this->$primaryKey=='' || static::count("$primaryColumnName=$primaryValue")==0){
            $this->insert();
        }
        else{
            $this->update();
        }
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
    public static function insertBulk($listObject){
        if(is_array($listObject) && count($listObject)>0){
            $listObject = array_values($listObject);
            $tableName = static::getTableName();
            $columns = [];
            $values = [];
            for($i=0;$i<count($listObject);$i++){ 
                $listVar = get_object_vars($listObject[$i]);
                $values[$i]=[];
                foreach($listVar as $key => $value){
                    $columnData = static::getColumnNameInDataBase($key,true);
                    if(is_array($columnData) && $columnData != false && (!isset($columnData['auto_increment']) || $columnData['auto_increment']==false|| $value!='')){
                        if($i===0){
                            $columns[] = $columnData['name'];
                        }
                        $values[$i][]  = self::getValueForSqlCommand($columnData,$value);
                    }   
                }
                $values[$i] = "(".implode(",",$values[$i]).")";
                
            }
            $keysCommand    = implode(",",$columns);
            $valuesCommand  = implode(",\n",$values);
            $command        = "INSERT INTO $tableName ($keysCommand) VALUES $valuesCommand";
            $result         = Connection::exeQuery($command);
            return $result;
        }
        return false;
        
        
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
        $primaryKey = static::getPrimaryKey();
        $primaryColumnData  = static::getColumnNameInDataBase($primaryKey,true);
        $primaryColumnName  = $primaryColumnData['name'];
        $primaryValue       = self::getValueForSqlCommand($primaryColumnData,$this->$primaryKey);
        $where              = self::mergeConditionQuery([$primaryColumnName. " = ".$primaryValue,$filterTenantQuery]);
        $command            = "UPDATE ".$tableName." SET $keysCommand WHERE $where";
        return connection::exeQuery($command);
    }

    public static function updateMulti($set,$condition){
        $tableName = static::getTableName();
        $command            = "UPDATE ".$tableName." SET $set WHERE $condition";
        return connection::exeQuery($command);
    }
    public function delete()
    {
        $tableName          = static::getTableName();
        $filterTenantQuery  = static::getFilterTenantQuery();
        $primaryKey = static::getPrimaryKey();
        $primaryColumnData  = static::getColumnNameInDataBase($primaryKey,true);
        $primaryColumnName  = $primaryColumnData['name'];
        $primaryValue       = self::getValueForSqlCommand($primaryColumnData,$this->$primaryKey);
        $where              = self::mergeConditionQuery([$primaryColumnName. " = ".$primaryValue,$filterTenantQuery]);
        $command            = "DELETE FROM $tableName WHERE $where";
        return connection::exeQuery($command);
    }
    private static function mergeConditionQuery($listQuery){
        $listQuery = array_filter($listQuery);
        return implode(' AND ',$listQuery);
    }


    public static function makeUnionQuery($table, $filter, $filterableColumns, $selectableColumns)
    {
        $unionMode = $filter['unionMode'];
        unset($filter['unionMode']);
        $items = [];
        $count = 1;
        foreach ($unionMode['items'] as &$it) {
            $it['filter'] = array_merge($it['filter'], $filter['filter']);
            $newFilter = array_merge($filter, $it);
            $sql = ModelFilterHelper::getSQLFromFilter($table, $newFilter, $filterableColumns, $selectableColumns);
            $newItem = $sql['full'];
            $items[] = "SELECT * FROM ($newItem) tmp_tb$count";
            $count += 1;
        }

        $connectItemsKey = isset($unionMode['all']) ? ($unionMode['all'] ? ' UNION ALL ': ' UNION ') : ' UNION ALL ';
        return [
            'full'  => implode($connectItemsKey, $items),
            'count' => 'SELECT 1 AS count_items'
        ];
    }


    /**
     * Lấy danh sách bản ghi theo filter
     *
     * @param array $filter Cấu hình cho việc filter, cấu trúc của filter được quy định trong document về framework
     * @param array $moreConditions Thêm các điều kiện lọc vào trong filter 
     * @param array $filterableColumns danh sách các cột được phép áp dụng filter
     * @param array $selectableColumns danh sách các cột được phép select dữ liệu
     * @param string $table Tên bảng hoặc câu Lệnh SQL chứa dataset cần filter dữ liệu
     * @param string $sqlOnly Chỉ trả về SQL
     * @param string $returnSQL data trả về của hàm có chứa câu lệnh SQL hay không
     * @return array
     */
    public static function getByFilter($filter, $moreConditions = [], $filterableColumns = [], $selectableColumns = [], $table = '', $sqlOnly = false, $returnSQL = false)
    {
        $calledClass = get_called_class();
        $returnObject = false;
        $callFromModel = $calledClass == 'Model\Model';
        $filter = self::standardlizeFilterData($filter, $moreConditions, $callFromModel);
        $filterableColumns = count($filterableColumns) > 0 ? $filterableColumns :  self::getFilterableColumns();
        $selectableColumns = count($selectableColumns) > 0 ? $selectableColumns :  self::getSelectableColumns();
        $needTotal = true;

        if($calledClass != 'Model\Model' ){
            if($table == ''){
                $returnObject = true;
                $table = static::getTableName();
            }else if($table == static::getTableName()){
                $returnObject = true;
            }
        }

        $sql = '';
        if(isset($filter['unionMode'])){
            $needTotal = false;
            $sql = self::makeUnionQuery($table, $filter, $filterableColumns, $selectableColumns);
        }else{
            $sql = ModelFilterHelper::getSQLFromFilter($table, $filter, $filterableColumns, $selectableColumns);
        }
        $GLOBALS['get-by-filter'] = $sql;
        $data = [
            'total' => 0
        ];
        if(!$sqlOnly){
            $data['list'] = self::get($sql['full'], $returnObject);
            $data['list'] = $data['list'] == false ? [] : $data['list'];

            if($needTotal){
                $data['total'] = self::get($sql['count'], false)[0]['count_items'];
            }
        }

        $data['sql'] = '';
        if($returnSQL){
            $data['sql'] = $sql;
        }
        return $data;
    }

    /**
     * Lấy danh sách các cột được phép áp dụng filter của model,
     * Mặc định tất cả các cột được định nghĩa trong Model đều có thể filter
     * Nếu muốn cột nào đó không được phép filter (và select ) thì thêm option "notFilter" vào định nghĩa cột trong model
     *
     * @return array
     */
    public static function getFilterableColumns()
    {
        $columns = static::$mappingFromDatabase;
        $result = [];
        foreach ($columns as $prop => $column) {
            if(!array_key_exists('notFilter', $column) || $column['notFilter'] == false){
                $result[] = $column;
            }
        }
        return $result;
    }


    /**
     * Lấy danh sách các cột được phép select trong câu lệnh SQL 
     * Mặc định tất cả các cột được định nghĩa trong Model đều có thể đưa vào select
     * Nếu muốn cột nào đó không được phép select thì thêm option "notSelect" vào định nghĩa cột trong model
     *
     * @return array
     */
    public static function getSelectableColumns()
    {
        $columns = static::$mappingFromDatabase;
        $result = [];
        foreach ($columns as $prop => $column) {
            if(!array_key_exists('notSelect', $column) || $column['notSelect'] == false){
                $result[] = $column;
            }
        }
        return $result;
    }

    /**
     * Chuẩn hóa cấu trúc filter 
     *
     * @param array $filter filter nhận được từ client
     * @param array $moreConditions Các điều kiện khác cần truyền vào
     * @return void
     */
    public static function standardlizeFilterData($filter, $moreConditions = [], $callFromModel = true)
    {
        $columns = [];
        if(array_key_exists('columns', $filter)){
            foreach ($filter['columns'] as $columnName) {
                $columns[] = self::getProperColumnName($columnName, $callFromModel);
            }
        }
        $filter['columns'] = $columns;

        if(array_key_exists('groupBy', $filter)){
            $groupByColumns = [];
            foreach ($filter['groupBy'] as $columnName) {
                $groupByColumns[] = self::getProperColumnName($columnName, $callFromModel);
            }
            $filter['groupBy'] = $groupByColumns;
        }

        
        if(array_key_exists('aggregate', $filter)){
            foreach ($filter['aggregate'] as &$item) {
                $item['column'] = self::getProperColumnName($item['column'], $callFromModel);
            }
        }
        
        if(array_key_exists('filter', $filter)){
            foreach ($filter['filter'] as $index  => &$item) {
                $item['column'] = self::getProperColumnName($item['column'], $callFromModel);
            }
        }

        if(count($moreConditions) > 0 ){
            if(!array_key_exists('filter', $filter)){
                $filter['filter'] = [];
            }
            $filter['filter']  = array_merge($filter['filter'], $moreConditions);
        }

        if(array_key_exists('sort', $filter)){
            foreach ($filter['sort'] as $index => $sortItem) {
                $filter['sort'][$index]['column'] = self::getProperColumnName($sortItem['column'], $callFromModel);
            }
        }

        
        if(array_key_exists('sort', $filter)){
            foreach ($filter['sort'] as $index => $sortItem) {
                $filter['sort'][$index]['column'] = self::getProperColumnName($sortItem['column'], $callFromModel);
            }
        }

        if(!array_key_exists('stringCondition', $filter)){
            $filter['stringCondition'] = '';
        }

        if(!array_key_exists('linkTable', $filter)){
            $filter['linkTable'] = [];
        }

        return $filter;
    }


    /**
     * Trả về tên phù hợp với các cột của kết quả trả về
     * @param originColumn tên cột có trong cấu hình
     * @param callFromModel Biến chỉ định xem hàm filter có được gọi từ class Model hay gọi từ các class kết thừa Model
     */
    public static function getProperColumnName($originColumn, $callFromModel)
    {
        $mappingFromDatabase = static::$mappingFromDatabase;
        if($callFromModel){
            return $originColumn;
        }else{
            if(array_key_exists($originColumn, $mappingFromDatabase)){
                return $mappingFromDatabase[$originColumn]['name'];
            }else {
                return '';
            }
        }
    }
}