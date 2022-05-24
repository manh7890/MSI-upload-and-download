<?php
namespace Library\MSI;

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Library\MSI\MSAuth;
use Library\MSI\MSFileManager;
class MSExcelManager {

    public $itemID = '013NJUP3OXWA4YQE5XLFF3GWWUYSCHAR2N';
    public  $sheet = 'test';
     
    function updateData($fileId,$sheet,$range,$values){
        $class = new MSAuth();
        $fileManager = new MSFileManager();
        $timeBefore = $fileManager->getTime();
        $token=$class->getToken();
        $accessToken = $token['accessToken'];
        $fileId=$fileId['fileId'];
        
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/items/$fileId/workbook/worksheets/$sheet/"
        ]);
        $res = $client->request('PATCH', "range(address='$range')", [
                'headers' => [
                    'Content-Type' => 'application/json',

                    'Authorization' => $accessToken
                ],
                'body' => json_encode(
                    [
                        "values" => 
                            $values
                    ]
                )
            ]);
            $body = $res->getBody();
            $arrBody  = json_decode($body, true);
            $timeAfter = $fileManager->getTime();
            $timeGetFileInfo = $fileManager->timeCalculator($timeBefore, $timeAfter);
            return array("time"=>$timeGetFileInfo,"arr"=>$arrBody );

    }
    

    function getData($fileid,$sheet,$range){
        $class = new MSAuth();
        $token = $class->getToken();
        $client = new Client([
            'base_uri' => "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/workbook/worksheets/$sheet/"
        ]);
        $res = $client->request('GET', "range(address='$range')", [
            'headers' => [
                    'Authorization' => $token
                ]
            ]);
        echo $res->getBody();// {"type":"User"...'

    }
}