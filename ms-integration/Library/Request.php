<?php
namespace Library;
class Request {
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36';
    protected $url;
    protected $timeout;
    protected $post;
    protected $postFields;
    protected $dataResponse;
    protected $includeHeader;
    protected $status;
    protected $authorization;
    protected $method = "POST";
    public    $authentication = 0;
    public    $token = '';
    public    $authName = '';
    public    $authPass = '';

    public function __construct($url,$timeOut = 30,$includeHeader = false)
    {
        $this->url = $url;
        $this->timeOut = $timeOut;
        $this->includeHeader = $includeHeader;
    }
    public function useAuth($use){
        $this->authentication = 0;
        if($use == true) $this->authentication = 1;
    }

    public function setName($name){
        $this->authName = $name;
    }
    public function setPass($pass){
        $this->authPass = $pass;
    }
    public function setMethod($method){
        $this->method = $method;
    }

    public function setIncludeHeader($includeHeader)
    {
        $this->includeHeader = $includeHeader;

        return $this;
    }
    public function setAuthorization($authorization){
        $this->authorization = $authorization;
    }
    public function setToken($token){
        $this->token = $token;
    }

    public function setPost ($postFields)
    {
        $this->post = true;
        $this->postFields = $postFields;
    }

    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function send()
    {
        //demo authen
        $s = curl_init();
        curl_setopt($s,CURLOPT_URL,$this->url);
        $authorization ="Authorization: Bearer ". Auth::getBearerToken();
        curl_setopt($s,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded',$authorization));
        
        curl_setopt($s,CURLOPT_TIMEOUT,$this->timeOut);
        curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
        if($this->authentication == 1){
            curl_setopt($s, CURLOPT_USERPWD, $this->authName.':'.$this->authPass);
        }
        if($this->post)
        {
            curl_setopt($s,CURLOPT_POST,true);
            curl_setopt($s,CURLOPT_POSTFIELDS,http_build_query($this->postFields));
        }
        curl_setopt($s,CURLOPT_CUSTOMREQUEST,$this->method);
        curl_setopt($s,CURLOPT_USERAGENT,$this->userAgent);
        $this->dataResponse = curl_exec($s);
        $this->status = curl_getinfo($s,CURLINFO_HTTP_CODE);
        
        curl_close($s);
    }

    public function getHttpStatus()
    {
        return $this->status;
    }

    public function result(){
        return $this->dataResponse;
    }


    public static function request($url, $dataPost = false, $method = 'GET',$token = false){
        $resultTest = Test::callFunction(Test::FUNC_REQUEST,$url);
        if($resultTest!==Test::FUNC_NO_AVAILABLE){
            return $resultTest;
        }
        $request = new Request($url);
        if($dataPost != false){
            $request->setPost($dataPost);
        }
        if($token != false){
            $request->setToken($token);
        }
        $request->setMethod($method);
        $request->send();
        $response = $request->result();
        // trường hợp data trả về không đúng định dạng json
        if(Str::isJson($response)){
            $jsonData = json_decode($response, true);
            return $jsonData;
        }
        else{
            return false;
        }
	}
    
}
?>