<?php
namespace Library;
class Auth{
    //
    public static function Hash($Password){
        return hash_hmac('sha256',$Password,PRIVATE_KEY);
    }

    public static function sign($data){
        $privatePEMKey = file_get_contents(DIR.'/Crypt/private.pem');
        $privatePEMKey = openssl_get_privatekey($privatePEMKey,'Symper@@');
        $signature = '';
        openssl_sign($data, $signature, $privatePEMKey,OPENSSL_ALGO_SHA256);
        $signature = bin2hex($signature);
        return $signature;
    }
    public static function verifySign($data,$signature){
        $publicPEMKey = file_get_contents(DIR.'/Crypt/public.pem');
        $publicPEMKey = openssl_get_publickey($publicPEMKey);
        $r = openssl_verify($data,hex2bin($signature),$publicPEMKey,OPENSSL_ALGO_SHA256);
        if($r==1){
            return true;
        } 
        else{
            return false;
        }   
    }
    public static function getJwtData($token){
        $dataFromCache = CacheService::getMemoryCache($token);
        if($dataFromCache != false){
            return $dataFromCache;
        }
        else{
            $dataFromToken = explode(".",$token);
            $header = $dataFromToken[0];
            $payload = $dataFromToken[1];
            $signature = $dataFromToken[2];
            if(self::verifyJwt($header,$payload,$signature)){
                $jsonData = base64_decode($payload);
                $data = json_decode($jsonData,true);
                CacheService::setMemoryCache($token,$data);
                return $data;
            }
            else{
                return false;
            }
        }
    }
    public static function verifyJwt($header,$payload,$signature){
        $signature = base64_decode($signature);
        $dataToVerify = "$header.$payload";
        return self::verifySign($dataToVerify,$signature);
    }
    public static function getJwtToken($data){
        $header = self::getJwtHeader();
        $payload = self::getJwtPayload($data);
        $signature = self::getJwtSignature($header,$payload);
        $jwtToken = "$header.$payload.$signature";
        return $jwtToken;
    }
    public static function getJwtHeader(){
        $header=[
            'alg'   => "RS256",
            'type'  => 'JWT'
        ];
        return base64_encode(json_encode($header));
    }
    public static function getJwtPayload($data){
        return base64_encode(json_encode($data));
    }
    public static function getJwtSignature($header,$payload){
        return base64_encode(self::sign("$header.$payload"));
    }
    public static function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    public static function encryptRSA($plainData ){
        $publicPEMKey   = file_get_contents(DIR.'/Crypt/public.pem');
        $publicPEMKey   = openssl_get_publickey($publicPEMKey);
        $encrypted      = '';
        $plainData      = str_split($plainData, 200);
        foreach($plainData as $chunk){
            $partialEncrypted   = '';
            $encryptionOk       = openssl_public_encrypt($chunk, $partialEncrypted, $publicPEMKey, OPENSSL_PKCS1_PADDING);
            if($encryptionOk === false){
                return false;
            }
            $encrypted .= $partialEncrypted;
        }
        return base64_encode($encrypted);
    }
    public static function decryptRSA( $data){
        $privatePEMKey  =file_get_contents(DIR.'/Crypt/private.pem');
        $privatePEMKey  = openssl_get_privatekey($privatePEMKey,'Symper@@');
        $decrypted      = '';
        $data           = str_split(base64_decode($data), 256);//2048 bit
        foreach($data as $chunk){
            $partial = '';
            $decryptionOK = openssl_private_decrypt($chunk, $partial, $privatePEMKey, OPENSSL_PKCS1_PADDING);
            if($decryptionOK === false){
                return false;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }
    public static function getDataToken(){
        $dataLogin = CacheService::getMemoryCache('JwtDataLoginCache');
        if($dataLogin == false) {
            $token = Auth::getBearerToken();
            if(!empty($token)){
                $dataLogin = self::getJwtData($token);
                CacheService::setMemoryCache('JwtDataLoginCache',$dataLogin);
            }
        }
        return $dataLogin;
    }
    public static function getTenantId(){
        $dataLogin = self::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['tenant_id'])){
                return $dataLogin['tenant_id'];
            }
        }
        return '';
    }
    public static function getCurrentRole(){
        $dataLogin = self::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['type'])&&$dataLogin['type']=='ba' && isset($dataLogin['userDelegate']['role'])){
                return $dataLogin['userDelegate']['role'];
            }
            else if(isset($dataLogin['role'])){
                return $dataLogin['role'];
            }
        }
        return false;
    }
    public static function isBa(){
        $dataLogin = self::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['type'])&&$dataLogin['type']=='ba'){
                return true;
            }
        }
        return false;
    }
    public static function getCurrentUserId(){
        $dataLogin = self::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['type'])&&$dataLogin['type']=='ba'&&isset($dataLogin['userDelegate'])&&isset($dataLogin['userDelegate']['id'])){
                
                return $dataLogin['userDelegate']['id'];
            }
            else if(isset($dataLogin['id'])){
                return $dataLogin['id'];
            }
        }
        return false;
    }
    public static function getCurrentSupporterId(){
        $token = Auth::getBearerToken();
        if(!empty($token)){
            $dataLogin = Auth::getJwtData($token);
            if(!empty($dataLogin)){
                if(isset($dataLogin['id']) &&isset($dataLogin['type'])&&$dataLogin['type']=='ba' ){
                    return $dataLogin['id'];
                }
            }
        }
        return false;
    }
    public static function getCurrentIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public static function getCurrentUserAgent(){
        return $_SERVER['HTTP_USER_AGENT'];
    }
    private static function getTokenInfo($key){
        $dataLogin = self::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['type'])&&$dataLogin['type']=='ba'&&isset($dataLogin['userDelegate'])&&isset($dataLogin['userDelegate'][$key])){
                
                return $dataLogin['userDelegate'][$key];
            }
            else if(isset($dataLogin[$key])){
                return $dataLogin[$key];
            }
        }
        return '';
    }
    public static function getTokenIp(){
        return self::getTokenInfo('ip');
    }
    public static function getTokenUserAgent(){
        return self::getTokenInfo('userAgent');
    }
    public static function getTokenLocation(){
        return self::getTokenInfo('location');
    }

}