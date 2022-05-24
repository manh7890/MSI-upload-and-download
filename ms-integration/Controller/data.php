<?php
namespace Controller;
use Library\MSI\MSExcelManager;
use Library\MSI\MSFileManager;
use Model\fileInfo;

class data extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'test';
        $this->requireLogin = false;
    }
   
    public function updateData(){
        $range = $this->parameters['range'];
        $values = $this->parameters['values'];
        $filename = $this->parameters['filename'];
        $sheet = $this->parameters['sheet'];

        $fileInfo = fileInfo::getByTop('',"filename ='$filename'");
        $fileid = $fileInfo[0]->fileid;

        $get = new MSExcelManager();
        $token = $get->updateData($fileid,$sheet,$range, $values);
        echo ($token);
    
    }

    public function getData(){
        $range = $this->parameters['range'];
        $filename = $this->parameters['filename'];
        $sheet = $this->parameters['sheet'];

        $fileInfo = fileInfo::getByTop('',"filename ='$filename'");
        $fileid = $fileInfo[0]->fileid;

        $get = new MSExcelManager();

        $token = $get->getData($fileid,$sheet,$range);
        echo ($token);

    }

    public function uploadFile(){

        $fileName = $this->parameters['file'];
        $get = new MSFileManager();
        $fileSize = filesize("C:\Users\user\Desktop\\".$fileName);

        if ($fileSize <4000000 ) {
            $info = $get->uploadSmallFile($fileName);
        } else {
            $info = $get->uploadLargeFile($fileName,$fileSize);
        }
        
        $check = fileInfo::getByTop('',"filename ='$fileName'");
        $fileInfo = new fileInfo();
        $shareLink =$info['shareLink'];
        $fileId = $info['fileId'];
        // var_dump($check);
        
        if($check==[]){
            $fileInfo->filename = $info['fileName'];
            $fileInfo->fileid = $fileId;
            $fileInfo->sharelink = $shareLink;
            $fileInfo->stt = 1;
            $fileInfo->insert();
        } else {
            fileInfo::updateMulti("sharelink = '$shareLink',stt = 1, fileid ='$fileId'","filename = '$fileName'");
        }

        $files=fileInfo::getByAll();
        $this->output['data']=$files;
    }

    // public function getShareLink(){
        
    //     $get = new MSFileManager();
        
    //     $token = $get->getShareLink();
    //     echo ($token);

    // }

    public function deleteFile(){
        $fileName = $this->parameters['fileName'];
        $fileInfo = fileInfo::getByTop('',"filename ='$fileName'");
        $MSFileManager = new MSFileManager();
        $del = $MSFileManager->deleteFile($fileInfo[0]->fileid);
        fileInfo::updateMulti("stt = 0","filename = '$fileName'");


    }

    }