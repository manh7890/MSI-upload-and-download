<?php
namespace Library;
class Redirect{
    

    public static function redirect($url){
        header('location: '.$url);
    }
    public static function redirect404(){
        self::Redirect('/404.html');
        exit;
    }
    public static function redirect403(){
        header('Content-Type: application/json');
        $output = [
            'status'=>STATUS_PERMISSION_DENIED,
            'message'=> Message::getStatusResponse(STATUS_PERMISSION_DENIED)
        ];
        print json_encode($output);
        
        exit;
    }
    public static function redirectByJavascript($url){
        echo "<script>window.location='".$url."'</script>";
    }
}