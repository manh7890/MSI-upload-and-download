<?php
namespace Library;
class Test{
    public const FUNC_NO_AVAILABLE = 'test_func_no_available';
    public const FUNC_EXE_QUERY_DB = 'funcTestExeQuery';
    public const FUNC_QUERY_DB = 'funcTestQuery';
    public const FUNC_GET_CACHE = 'funcTestGetCache';
    public const FUNC_SET_CACHE = 'funcTestSetCache';
    public const FUNC_REQUEST = 'funcTestRequest';
    
    public static function setFunction($functionName,$func){
        $GLOBALS[$functionName] = $func;
    }
    public static function callFunction($functionName,$param=''){
        if(isset($GLOBALS[$functionName])&&is_callable($GLOBALS[$functionName])){
            return $GLOBALS[$functionName]($param);
        }
        return self::FUNC_NO_AVAILABLE;
    }
    
}