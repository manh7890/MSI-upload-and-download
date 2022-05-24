<?php
namespace Model;
use SqlObject;
class fileInfo extends SqlObject{
    public $filename,
        $fileid,
        $sharelink,
        $stt;
        
    public static $mappingFromDatabase = [
        'filename'                =>  [ 'name' => 'filename',                   'type' => 'string', 'primary'=>true],
        'fileid'              =>  [ 'name' => 'fileid',                 'type' => 'string'],
        'sharelink'              =>  [ 'name' => 'sharelink',                 'type' => 'string'],
        'stt'              =>  [ 'name' => 'stt',                 'type' => 'number']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'file_info';
    }

}