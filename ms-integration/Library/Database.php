<?php
namespace Library;

use Model\Connection;

class Database{  
    private  $modelClass = false;
    private  $objectModel = false;
    private static $dataTypeMapping = [
        'string'            =>  'text',
        'number'            =>  'INTEGER',
        'datetime'          =>  'TIMESTAMP WITHOUT TIME ZONE',
        'timestamp'         =>  'TIMESTAMP WITHOUT TIME ZONE',
        'label'             =>  'VARCHAR(256)',
        'integer'           =>  'INTEGER',
        'text'              =>  'TEXT',
        'rich-text'         =>  'TEXT',
        'email'             =>  'VARCHAR(256)',
        'time'              =>  'TIMESTAMP WITHOUT TIME ZONE',
        'user'              =>  'INTEGER',
        'month'             =>  'INTEGER',
        'image'             =>  'VARCHAR(256)',
        'persent'           =>  'DOUBLE PRECISION',
        'float'             =>  'DOUBLE PRECISION',
        'currency'          =>  'DOUBLE PRECISION',
        'date'              =>  'DATE',
        'double'            =>  'DOUBLE PRECISION',
    ];
    public  function createTable($data){
        $this->getObjectModel($data);
        if($this->objectModel!=false){
            if(!$this->checkTableExist()){
                $columnQuerys           = $this->getSqlCreateColumn();
                $columnQueryString      = implode(",", $columnQuerys);
                $sqlQuery               = "CREATE TABLE ".$this->modelClass::getTableName()." ($columnQueryString)";
                $result = Connection::exeQuery($sqlQuery);
                if($result!=false){
                    return [
                        'result'=>true,
                        'message'=>'Create table successful'
                    ];
                }
                else{
                    return [
                        'result'=>false,
                        'message'=>'Create table fail'
                    ];
                }
            }
            else{
                return [
                    'result'=>false,
                    'message'=>'Table already exists'
                ];
            }
        }
        else{
            return [
                'result'=>false,
                'message'=>'Data model not found'
            ];
        }
        
    }
    private  function getObjectModel($data){
        if(is_string($data)){
            $modelClass = "\\Model\\$data";
            if(class_exists($modelClass)){
                $this->objectModel =  new $modelClass();
                $this->modelClass = $modelClass;
            }
        }
        else if(is_object($data)){
            $this->objectModel =  $data;
            $this->modelClass = get_class($data);
        }
    }
    private  function checkTableExist(){
        $modelClass = 
        $tableName = $this->modelClass::getTableName();
        $command = "SELECT EXISTS (SELECT table_name FROM information_schema.tables WHERE table_name = '$tableName');";
        $result = Connection::getDataQuerySelect($command);{
            if(isset($result[0]['exists'])&&$result[0]['exists']=='t'){
                return true;
            }
        }
        return false;
         
    }
    private  function getSqlCreateColumn(){
        $result = [];
        $columns = $this->modelClass::$mappingFromDatabase;
        
        foreach($columns as $column){
            if(isset($column['name'])&&isset($column['type'])){
                $name = $column['name'];
                $type = self::getDataType($column['type']);
                if(isset($column['auto_increment'])&&$column['auto_increment']==true){
                    $type = "SERIAL";
                }
                $result[] = "$name $type";
                if(isset($column['primary']) && $column['primary'] == true){
                    $result[] = "PRIMARY KEY ($name)";
                }
            }
        }
        return $result;
    }
    private static function getDataType($type){
        $type = strtolower($type);
        if(isset(self::$dataTypeMapping[$type])){
            return self::$dataTypeMapping[$type];
        }
        return $type;
    }
}