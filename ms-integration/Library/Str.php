<?php
namespace Library;
class Str{
    //
    public static function subString($str,$leng){
        if($leng < strlen($str)){
            return substr($str,0,$leng).'....';
        }
        else{
            return $str;
        }
    }
    public static function currentTimeString(){
        return date(DATETIME_FORMAT);
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function isJson($dataString){
        $data = json_decode($dataString);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    public static function getArrayFromUnclearData($data){
        $array = [];
        if(is_array($data)){
            $array = $data;
        }
        else if(is_string($data)){
            $array = json_decode($data,true);
            if(!is_array($array)){
                $array = explode(',',$data);
            }
        }
        return $array;
    }
    public static function bindDataToString($String,$variables){
        if(is_array($variables)&& count($variables)>0 && stripos($String,"{")!==false){
            foreach($variables as $key=>$value){
                $String = str_ireplace("{$key}",$value,$String);
                if(stripos($String,"{")===false){
                    break;
                }
            }
        }
        return $String;
    }
    public static function createUUID(){
        return sprintf('%08x-%04x-%04x-%04x-%04x%08x',
            microtime(true),
            getmypid(),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            Auth::getCurrentIP()
        );
    }

    public static function removeStringWrapper($str, $wrapper = "'")
    {
        $sections = [];
        $count = 1;
        $rsl = [
            'str'           => '',
            'placeHolder'   => []
        ];
        $str = str_replace("''", "__SYMPER_PLACEHOLDER_STRING_0", $str);
        $newStr = $str;
        $strLength = strlen($str);

        
        for($i = 0; $i < $strLength; $i++){
            if($str[$i] == $wrapper && $str[$i] != "\\$wrapper"){
                if(count($sections) == 0){
                    $sections[] = [
                        'start' => $i,
                        'end'   => -1
                    ];
                }else{
                    // Nếu đã ghi nhận chuỗi bắt đầu
                    $lastItem = &$sections[count($sections) - 1];
                    if($lastItem['end'] == -1){
                        $lastItem['end'] = $i;
                        $needReplaceStr = substr($str, $lastItem['start'], $lastItem['end'] - $lastItem['start'] + 1);
                        $key = "__SYMPER_PLACEHOLDER_STRING_$count"."_";
                        $newStr = str_replace($needReplaceStr, $key, $newStr);
                        $count += 1;
                        $rsl['placeHolder'][$key] = $needReplaceStr;
                    }else{
                    // Nếu cụm trước đó đã khép
                        $sections[] = [
                            'start' => $i,
                            'end'   => -1
                        ];
                    }
                }
            }
        }
        
        $rsl['placeHolder']['__SYMPER_PLACEHOLDER_STRING_0'] =  "''"; // ký tự đặc biệt của '
        $rsl['str'] = $newStr;
        return $rsl;
    }


    /**
     * Lấy các function xuất hiện trong chuỗi
     */
    public static function getAllFunctionsCallInString($str, $functionName, $openStr = '(', $closeSrt = ')')
    {
        $startIndexs = [];
        $strPatt = "\b$functionName\s*\\".$openStr;

        $newStr = self::removeStringWrapper($str);
        $str = $newStr['str'];


        preg_match_all("/$strPatt/i", $str, $startIndexs, PREG_OFFSET_CAPTURE);
        $matches = $startIndexs[0];
        $count = count($matches);
        if($count > 0){
            $rsl = [];
            for ($i=0; $i < ($count - 1); $i++) { 
                $start = $matches[$i][1];
                $end = $matches[$i+1][1] ;
                $rsl[] = self::getSingleFunctionCallInString(substr($str, $start, $end - $start), $openStr = '(', $closeSrt = ')');
            }

            $start = $matches[$count - 1][1];
            $end = strlen($str);

            $rsl[] = self::getSingleFunctionCallInString(substr($str, $start, $end - $start), $openStr = '(', $closeSrt = ')');
            foreach ($rsl as $idx => $value) {
                foreach ($newStr['placeHolder'] as $pkey => $newValue) {
                    $rsl[$idx] = str_replace($pkey, $newValue, $rsl[$idx]);
                }
            }
            return $rsl;
        }else{
            return [];
        }
    }

    /**
     * Lấy function đầu tiên xuất hiện trong chuỗi
     */
    public static function getSingleFunctionCallInString($str, $openStr = '(', $closeSrt = ')')
    {
        $stackCount = 0;
        $endIndex = 0;
        for ($i=strpos($str, $openStr); $i < strlen($str); $i++) { 
            if($str[$i] == $openStr){
                $stackCount += 1;
            }else if($str[$i] == $closeSrt){
                $stackCount -= 1;
            }
            if($stackCount == 0){
                $endIndex = $i+1;
                break;
            }
        }
        return substr($str, 0, $endIndex);
    }
}