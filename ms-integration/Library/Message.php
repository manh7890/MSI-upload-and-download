<?php
namespace Library;
class Message{  
    public static function getStatusResponse($statusCode){
        if(isset($GLOBALS[STORE_STATUS][$statusCode])){
            return $GLOBALS[STORE_STATUS][$statusCode];
        }
        else{
            return $GLOBALS[STORE_STATUS][STATUS_SERVER_ERROR];
        }
    }
}