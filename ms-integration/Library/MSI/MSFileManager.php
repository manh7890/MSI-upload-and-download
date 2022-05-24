<?php
namespace Library\MSI;
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Library\MSI\MSAuth;
use Microsoft\Graph\Graph;
class MSFileManager {
    const DIR = "C:\\xampp\\htdocs\\ms-integration\\SaveFileExcel\\";
    const CODE = '0.AVYAl427NDIwUk2vmgp_xhxSuLTOeViNendNkIIyu7FtFdxWAD4.AgABAAIAAAD--DLA3VO7QrddgJg7WevrAgDs_wQA9P-ViOMVtuH-rklNQlZarLXU0ZSNWZOYrp4uf_HndTgn9NjDXN2_whwUXyr6Uy6WgdOFMrmYlYWgPQUfk_GXx_-41tyy7YK_oCD1Vj1OsUXX7dCGZ6zCCYgdru42THr_IgC4rlcPMSQIjSH9H6BOiOfymwIILkXkDPiKXRzQuUoZLdmSj0s88v8XZTEsq9xGAMX6x2GvPjvrHifwSlU3V3hyb1Ir3iQGdXFPFt8WWN9P_l9QKTLpXDAgh9Zc6tlKedlKOyC2-aAh8yTaaRvgI3gn6EelXuI2IT6QnNQRx0YNcNFtwGQ7ygy9K4rYTOwE61Atn7GvfxshaH5PehfXcUea5lWWtjGE4VIQJI99yOkZkb03IRQkek3C7flKKv9DenYsv60Exfrdwur02IgSogwBo6Ac_tbdjdeH_SUQN1czskgitZUVGOWCIWyJzd6jJUz6MTqITf_nBMEIuNlq6sunOgYc9yFi1LuGmz8FKiUprw2EB0ju1zTmU-QC7TZ9aRZMIZxOgPjGWzsTUob2vHGe4DnJoCK0DOhAVomXBNiJl5jgBJE3vqcdGoxrUuQA6trAbdzPfoCX4i1Jc8OVK0MQcIMkPRYJQCHRc93xXnIE5kpQNn7lT0koFCzHqsxprTYste9DF9Iv8fhB--PSM2LPECdd1-CbIdMdo0nGImpRELfTki6O7n3hD2Yl4OBB4fYlpK-sQ_0JSIvRspgEKrXk7U7dXp_qibfYtv8FWQBwy2gKdsaDh0IJJbjRWSKTjUGq0w3E';
    const CLIENT_ID = "5879ceb4-7a8d-4d77-9082-32bbb16d15dc";
    const TENANT_ID = "34bb8d97-3032-4d52-af9a-0a7fc61c52b8";
    const REDIRECT_URI = 'http://localhost/connectToOneDrive/';
    const CLIENT_SECRET = 'yFB8Q~dj821UrPG4kZk3o4ULkg9dotf9JOYYnb_V';
    const BASE_URI = 'https://login.microsoftonline.com';
    const URL = '/common/oauth2/v2.0/token';
    const DRIVEID = 'b!jMhM1pxi2Eqqj6G55wiv9nM6rBrbE9pGv8FObEsgbq2JqAphIKJ3R6OWkB4LFD-v';
    const SAVE_FILE = 'Token.txt';
    public $parentId = '013NJUP3N4XNMECUKF4JEYELPOFVYBZAZH';
    public $driveId = 'b!jMhM1pxi2Eqqj6G55wiv9nM6rBrbE9pGv8FObEsgbq2JqAphIKJ3R6OWkB4LFD-v';
    public $parentName ="SymperPlatformDashboardEmbedSpreadsheet";
    function createUploadSession($fileName){
        $MSAuth = new MSAuth();
        $token = $MSAuth->getToken();
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/items/$this->parentId:/$fileName:/createUploadSession"
            // 'base_uri' => "https://graph.microsoft.com/v1.0/drive/root:/$this->parenId:/createUploadSession"
        ]);
        $res = $client->request('POST', "", [
                'headers' => [
                    'Content-Type'=> 'application/json',
                    'Authorization' => $token
                ],
                'body' => json_encode([
                    "item"=>[
                        "@microsoft.graph.conflictBehavior"=> "replace"
                    ]
                    
                ])
            ]);
        $body = $res->getBody();
        $arr_body = json_decode($body);
        return ($arr_body->uploadUrl);
    }

    function uploadSmallFile($fileName){
        // FILE <4MB
        $MSAuth = new MSAuth();
        $token = $MSAuth->getToken();
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/root:/$this->parentName/$fileName:/content"
        ]);
        $res = $client->request('PUT', "", [
                'headers' => [
                    'Content-Type'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Authorization' => $token
                ],
                'body' => fopen("C:\Users\user\Desktop\\".$fileName, "r")
                
            ]);
            $body = $res->getBody();
            $arr_body = json_decode($body);
            var_dump($arr_body);

            $fileId = $arr_body->id;
            $shareLink = self::getShareLink($fileId,$token);
            $arr_file_info=['fileName'=>$fileName,'fileId'=>$fileId,'shareLink'=>$shareLink];
            return($arr_file_info);
    }

    function uploadLargeFile($fileName,$fileSize){
        $uploadUrl =self::createUploadSession($fileName);
        $MSAuth = new MSAuth();
        $token = $MSAuth->getToken();
        $begin = $fileSize-1;
        $client = new Client([
        ]);
        $res = $client->request('PUT', $uploadUrl, [
                'headers' => [
                    'Content-Type'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Length'=> $fileSize,
                    'Content-Range'=> "bytes 0-$begin/$fileSize",
                    'Authorization' => $token
                ],
                'body' => fopen("C:\Users\user\Desktop\\".$fileName, "r")

                // 'multipart' => [
                //                 [
                //                     'name'     => "file.",
                //                     'contents' => fopen("C:\Users\user\Desktop\\".$fileName, 'r'),
                //                     // 'filename' => $fileName
                //                 ]
                //             ]
            ]);
            $body = $res->getBody();
            $arr_body = json_decode($body);
            var_dump($arr_body);
            $fileId = $arr_body->id;
            $shareLink = self::getShareLink($fileId,$token);
            $arr_file_info=['fileName'=>$fileName,'fileId'=>$fileId,'shareLink'=>$shareLink];
            return($arr_file_info);
    }

    function getShareLink($fileId,$token = null){
        if ($token === null) {
            $MSAuth = new MSAuth();
            $token = $MSAuth->getToken();
        }
        
        
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/items/$fileId/createLink"

        ]);
        $res = $client->request('POST', "", [
                'headers' => [
                    'Content-Type'=> 'application/json',
                    'Authorization' => $token
                ],
                'body' => json_encode([
                    "type"=> "view",
                    "scope"=> "anonymous"
                ])
            ]);

            $body = $res->getBody();
            $arr_body = json_decode($body);
            
        return ($arr_body->link->webUrl);
    }

    
    function uploadSmallFileMacro($fileName,$token){
        // FILE <4MB
        
        
        
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/root:/$this->parentName/$fileName:/content"
        ]);
        $res = $client->request('PUT', "", [
                'headers' => [
                    'Content-Type'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Authorization' => $token
                ],
                'body' => fopen("C:\\xampp\\htdocs\\ms-integration\\SaveFileExcel\\".$fileName, "r")
                
            ]);
            $body = $res->getBody();
            $arr_body = json_decode($body);
            

            
            $fileId = $arr_body->id;
            
            return $fileId;
    }
    function uploadLargeFileMacro($fileName,$fileSize,$token){
        $uploadUrl =self::createUploadSession($fileName);
        
        
        $begin = $fileSize-1;
        $client = new Client([
        ]);
        $res = $client->request('PUT', $uploadUrl, [
                'headers' => [
                    'Content-Type'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Length'=> $fileSize,
                    'Content-Range'=> "bytes 0-$begin/$fileSize",
                    'Authorization' => $token
                ],
                'body' => fopen("C:\\xampp\\htdocs\\ms-integration\\SaveFileExcel\\".$fileName, "r")

                // 'multipart' => [
                //                 [
                //                     'name'     => "file.",
                //                     'contents' => fopen("C:\Users\user\Desktop\\".$fileName, 'r'),
                //                     // 'filename' => $fileName
                //                 ]
                //             ]
            ]);
            
            $body = $res->getBody();
            $arr_body = json_decode($body);
            
            $fileId = $arr_body->id;
            
           
            return $fileId;
    }
    public function getTime()
    {

        $milliseconds = microtime(true);
        return $milliseconds;
        
    }
    public function timeCalculator($timeStart, $timeEnd)
    {
        $time = number_format($timeEnd - $timeStart, 2) . 's';
        return $time;
    }


    public function getFileInfo($fileId )
    {
        $timeBeforeGetFileInfo = $this->getTime();
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        
        $accessToken = $token['accessToken'];
        
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com"
        ]);
        $response = $client->request('GET', "/v1.0/me/drive/items/" . $fileId, [
            'headers' => [
                'Authorization' => $accessToken
            ]
        ]);
        $body = $response->getBody();
        $arrBody  = json_decode($body, true);
        $timeAfterGetFileInfo = $this->getTime();
        $timeGetFileInfo = $this->timeCalculator($timeBeforeGetFileInfo, $timeAfterGetFileInfo);
        return  array("name" => $arrBody['name'], "fileId" => $fileId,"time"=>$timeGetFileInfo);
    }


    public function handleDownloadFile($fileInfo)
    {
        
        $timeBeforeDownload = $this->getTime();
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        


        $user_accessToken = $token['accessToken'];
        
        $graph = new Graph();
        $graph->setAccessToken($user_accessToken);
        // Download to server
        $graph->createRequest("GET", "/me/drives/" . self::DRIVEID . "/items/" . $fileInfo['fileId'] . "/content")
            ->download(self::DIR . $fileInfo['name']);
        

        $timeAfterDownload = $this->getTime();
        
        return $this->timeCalculator($timeBeforeDownload, $timeAfterDownload);
    }


    public function runMarco($fileInfo, $macroInfo)
    {
        
        $timeBeforeRunMarco = $this->getTime();
        $myFileMacro = fopen(self::DIR . $fileInfo['name'] . ".vbs", "w+");
        if (!$myFileMacro) {
            die('Error creating the file' . self::DIR . $fileInfo['name']);
        }
        $textVBS = "Set objExcel = CreateObject(\"Excel.Application\")
            objExcel.Application.Run \"'" . self::DIR . $fileInfo['name'] . "'!" . $macroInfo['module'] . "." . $macroInfo["function"] . "\"
            objExcel.DisplayAlerts = False
            objExcel.ActiveWorkbook.Save
            objExcel.Application.Quit
            Set objExcel = Nothing";
        file_put_contents(self::DIR . $fileInfo['name'] . ".vbs", $textVBS, FILE_APPEND | LOCK_EX);
        fclose($myFileMacro);
        exec('wscript "' . self::DIR . $fileInfo['name'] . ".vbs" . '"');
        $timeAfterRunMarco = $this->getTime();
        
        return $this->timeCalculator($timeBeforeRunMarco, $timeAfterRunMarco);
    }


    public function uploadFileAfterRunMacro($fileInfo)
    {
        
        $beforeRun = $this->getTime();
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        
        $accessToken = $token['accessToken'];
        $fileName = $fileInfo['name'];
        
        
        $fileSize = filesize(self::DIR . $fileName);
        if ($fileSize < 4000000) {
            $fileId=$this->uploadSmallFileMacro($fileName, $accessToken);
            
        } else {
            $fileId=$this->uploadLargeFileMacro($fileName, $fileSize, $accessToken);
            
        }
        $afterRun = $this->getTime();
        $time = $this->timeCalculator($beforeRun, $afterRun);
        
        return  array("time"=>$time,"fileId"=>$fileId);
    }
    public function createShareLink($fileId){
        $beforeRun = $this->getTime();
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        
        $accessToken = $token['accessToken'];
        $get = new MSFileManager();
        $shareLink = $get->getShareLink($fileId,$accessToken);
        $afterRun = $this->getTime();
        $time = $this->timeCalculator($beforeRun, $afterRun);
        return array("time"=>$time,"sharelink"=>$shareLink);
    }
    
    public function deleteFile($fileId){
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        $accessToken = $token['accessToken'];
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com"
        ]);
        $client->request("DELETE", "/v1.0/me/drive/items/" . $fileId, [
            'headers' => [
                'Authorization' => $accessToken
            ]
        ]);
    }
    public function copyFile($fileInfo){
        
        $timeBefore = $this->getTime();
        $MSAuth = new MSAuth();
        $token =$MSAuth->getToken();
        $accessToken = $token['accessToken'];
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com"
        ]);
        $postPart = str_replace(' ','',microtime());

        $response=$client->request("POST", "/v1.0/me/drive/items/" . $fileInfo['fileId'] ."/copy", [
            'headers' => [
                'Authorization' => $accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                "parentReference" =>[
                    
                    "driveId" =>$this->driveId,
                    "id" =>$this->parentId,
                ],
                "name"=> "(copy)".$postPart.$fileInfo['name']
            ])
        ]);
        $header = $response->getHeaders();
        $url=trim($header['Location'][0]);
        $client = new Client([
            'base_uri' => $url
        ]);
        $response=$client->request("GET", "", [  
        ]);
        $body = $response->getBody();      
        $arrBody  = json_decode($body, true);
        $timeAfter = $this->getTime();
        $timeGetFileInfo = $this->timeCalculator($timeBefore, $timeAfter);
        return array("fileId"=>$arrBody['resourceId'],"time"=>$timeGetFileInfo) ;
    }

    
}