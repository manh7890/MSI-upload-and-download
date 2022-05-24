<?php

namespace Library\MSI;


// <<<<<<< HEAD

require 'vendor/autoload.php';

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
use Library\MSI\MSFileManager;

class MSAuth
{   
    public $parentId = '013NJUP3N4XNMECUKF4JEYELPOFVYBZAZH';
    public $driveId = 'b!jMhM1pxi2Eqqj6G55wiv9nM6rBrbE9pGv8FObEsgbq2JqAphIKJ3R6OWkB4LFD-v';
    public $parentName ="SymperPlatformDashboardEmbedSpreadsheet";
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
    public function getTokenFromMSServer()
    {
        $fileManager = new MSFileManager();
        $timeBeforeGetToken = $fileManager->getTime();
        $guzzle = new \GuzzleHttp\Client();
        $url = 'https://login.microsoftonline.com/' . self::TENANT_ID . '/oauth2/token';
        $user_token = json_decode($guzzle->post($url, [
            'form_params' => [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'resource' => 'https://graph.microsoft.com/',
                'grant_type' => 'password',
                'username' => 'admin@symper.vn',
                'password' => 'M11c~YZ$CNHcT1LsqgIB'
            ],
        ])->getBody()->getContents());
        $timeAfterGetToken = $fileManager->getTime();
        $timeGetToken = $fileManager->timeCalculator($timeBeforeGetToken, $timeAfterGetToken);
        return array("accessToken" => $user_token->access_token, "refreshToken" => $user_token->refresh_token, "time"=>$timeGetToken);
    }

    
    public function getToken()
    {
        
        
        
        if(empty($GLOBALS['mtok'])){
            $token = $this->getTokenFromMSServer();
            $GLOBALS['mtok']=$token;
            
            
            
        }
        
        return $GLOBALS['mtok'];
        //nếu dùng refresh token va luu vao local thì uncomment 2 dòng dưới rồi return data[0]
        // $myFile =file_get_contents(self::SAVE_FILE);
        // $data=explode(" ",$myFile);
        
    }


    
}   
    // public function saveTokenToLocal($arrToken){
    //     $myFile = fopen(self::SAVE_FILE, "w+") ;
    //     if (!$myFile) {
    //         die('Error creating the file  Token.txt' );
    //     }
    //     foreach ($arrToken as $token){
    //         file_put_contents(self::SAVE_FILE, $token." ", FILE_APPEND | LOCK_EX);
                               
    //     }
        
    // }
    // public  function refreshToken(){
        
    //     $myFile =file_get_contents(self::SAVE_FILE);
    //     $data=explode(" ",$myFile);      
    //     $resetToken=$data[1];
    //     $client = new Client([
            
    //         'base_uri' => self::BASE_URI,
    //     ]);
    //     $response = $client->request('POST', self::URL, [
    //         'form_params' => [
    //             'client_id' => self::CLIENT_ID,
    //             'redirect_uri' => self::REDIRECT_URI,
    //             'client_secret' => self::CLIENT_SECRET,
    //             'refresh_token' =>  $resetToken,
    //             'grant_type' => 'refresh_token',

    //         ]
    //     ]);
        
    //         $body = $response->getBody();
    //         $arrBody = json_decode($body);
            
                        
    //         return [$arrBody->access_token,$arrBody->refresh_token];
            
        
    // }
