<?php

namespace Controller;
use Library\MSI\MSExcelManager;
use Library\MSI\MSFileManager;
use Library\MSI\MSAuth;


class MSI extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'test';
        $this->requireLogin = false;
    }
    public function getToKenMSServer(){
        $mSI = new MSAuth();
        $toKen=$mSI->getTokenFromMSServer();
        //nếu save to local thì gọi thêm hàm saveTokenToLocal
        // $mSI->saveTokenToLocal($toKen);
        $this->output['data']=$toKen;
    }
    //nếu sử dụng cách save to local thì gọi hàm refreshToken mỗi 30p để lấy token mới
    // public function refreshToken(){
    //     $mSI = new MSAuth();
    //     $toKen=$mSI->refreshToken();
    //     $mSI->saveTokenToLocal($toKen);
    // }ut['d
    public function getToken(){
        $mSI = new MSAuth();
        $toKen=$mSI->getToken();
        print_r($toKen);
    }
    public function fileMacroHandler(){
        $mSI = new MSAuth();
        $file=new MSFileManager();
        $token=$mSI->getToken();
        
        $fileId = $this->parameters['fileId'];
        $macroInfo = $this->parameters['macroInfo'];
        // $fileId='013NJUP3NENHUSSY5UAZEZBVKHH6TLQDIR';
        // $macroInfo=['Module1','test'];
        $fileInfo=$file->getFileInfo($fileId);
        $timeDownload=$file->handleDownloadFile($fileInfo);
        $timeRunMacro=$file->runMarco($fileInfo,$macroInfo);
        $arr =$file->uploadFileAfterRunMacro($fileInfo);
        $GetLink=$file->createShareLink($arr['fileId']);
        $this->output['data']=["thời gian  lấy token :".$token['time'],"thời gian lấy file info:".$fileInfo['time'],"thời gian download :".$timeDownload,"thời gian chạy macro :".$timeRunMacro,"thời gian chạy upload : ".$arr['time'],"thời gian lay link : ".$GetLink['time'],$GetLink['sharelink']];
    }
    public function deleteFile(){
        $mSI = new MSAuth();
        $file=new MSFileManager();
        $mSI->getToken();
        $fileId = $this->parameters['fileId'];
        // $fileId='013NJUP3IODW7WYIZ5ZFE2BL5OBB7HAHJ5';
        $file->deleteFile($fileId);
    }
    public function copyFile(){
        $mSI = new MSAuth();
        $file=new MSFileManager();
        $mSI->getToken();
        $fileId = $this->parameters['fileId'];
        // $fileId='013NJUP3PI3LZMUN2K4FGL2FSHG6YDC7WZ';
        $fileInfo=$file->getFileInfo($fileId);
        $copy= $file->copyFile($fileInfo);
        $this->output['data']=$copy;
    }
    public function updateDataAndRunMacro(){
        $mSI = new MSAuth();
        $excel= new MSExcelManager(); 
        $file=new MSFileManager();
        $token=$mSI->getToken();
        $updateInfo= $this->parameters['updateInfo'];
        $fileId = $this->parameters['fileId'];
        $macroInfo = $this->parameters['macroInfo'];
        $sheet = $updateInfo["tab"];
        $range=$updateInfo["range"];
        $data= $updateInfo["data"];
        $fileInfo=$file->getFileInfo($fileId);
        $copyFileId= $file->copyFile($fileInfo);
        $fileInfoAfter=$file->getFileInfo($copyFileId['fileId']);
        $updateData=$excel->updateData($fileInfoAfter,$sheet,$range,$data);
        if((isset($macroInfo['module']) ?$macroInfo['module']: false )&&(isset($macroInfo['function']) ?$macroInfo['function']: false) ){
            $timeDownload=$file->handleDownloadFile($fileInfoAfter);
            $timeRunMacro=$file->runMarco($fileInfoAfter,$macroInfo);
            $arr =$file->uploadFileAfterRunMacro($fileInfoAfter);
        }else{
            $timeDownload=0;
            $timeRunMacro=0;
            $arr['time']=0;
        }
        $getLink=$file->createShareLink($fileInfoAfter['fileId']);
        $this->output['data']=[
            "time"=>[
                "token"=>$token['time'],
                "getFileInfoBeforeCopy"=>$fileInfo['time'],
                "update"=>$updateData['time'],
                "getFileInfoAfterCopy"=>$fileInfoAfter['time'],
                "download"=>$timeDownload,
                "runMarco"=>$timeRunMacro,
                "upload"=>$arr['time'],
                "getLink"=>$getLink['time'],
            ],
            "shareLink"=>$getLink['sharelink']
        ];
        
    }
    //data để test nếu dùng postman
    // $fileId='013NJUP3P2XWDLACYXCVCYPXAAPZLRRBDI';
    // $macroInfo=['Module1','test'];
    
    

    
}