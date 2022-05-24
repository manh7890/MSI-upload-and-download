<?php
namespace Library;
class Process{
    public static function returnAndContinue(){
        $size = ob_get_length();
        header("Content-Length: ". $size . "\r\n");
        ob_end_flush();
        flush();
        if(session_id()){
            session_write_close();
        } 
    }
}