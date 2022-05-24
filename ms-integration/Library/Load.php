<?php
namespace Library;
class Load{
    public static $fileMap = [];
    public static function autoLoad(){
        if (file_exists(DIR . '/vendor/autoload.php')){
            require_once DIR . '/vendor/autoload.php';
        }
        self::requireDir('/Controller');
        self::requireDir('/Model');
        self::requireDir('/Library');
        spl_autoload_register(function ($class_name) {
            $classPath = explode('\\',$class_name);
            $class_name = end($classPath).".php";
            if(isset(self::$fileMap[$class_name])){
                foreach(self::$fileMap[$class_name] as $path){
                    require_once $path;
                }
            }
        });
        self::requireDir('/Config',true);
    }
    public static function requireDir($dirname,$require=false){
        $ListFile=scandir(DIR.$dirname);
        foreach ($ListFile as $fileName) {
            if($fileName != '..' && $fileName != '.'){
                $path = DIR.$dirname.'/' . $fileName;
                if (is_file($path)) {
                    self::addFileToFileMap($path,$fileName);
                    if($require){
                        require_once $path;
                    }
                }
                else if(is_dir($path)){
                    self::requireDir($dirname.'/' . $fileName,$require);
                }
            }
        }
    }
    private static function addFileToFileMap($path,$fileName){
        if(!isset(self::$fileMap[$fileName])){
            self::$fileMap[$fileName] = [];
        }
        self::$fileMap[$fileName][] = $path;
    }
}   