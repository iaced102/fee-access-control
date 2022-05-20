<?php
namespace Library;
class Request {
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36';
    protected $url;
    protected $timeout;
    protected $post;
    protected $postFields;
    protected $getData;
    protected $dataResponse;
    protected $includeHeader;
    protected $status;
    protected $authorization;
    protected $method = "POST";
    public    $authentication = 0;
    public    $token = '';
    public    $authName = '';
    public    $authPass = '';

    public function __construct($url,$timeOut = 3000,$includeHeader = false)
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
    public function setGet ($data)
    {
        $this->getData = $data;
    }

    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function send()
    {
        //demo authen
        $s = curl_init();
        if($this->getData){
            $url = $this->url . '?' . http_build_query($this->getData);
            curl_setopt($s, CURLOPT_URL, $url);
        }
        else{
            curl_setopt($s,CURLOPT_URL,$this->url);
        }
        $token = Auth::getBearerToken();
        if(!empty($this->token)){
            $token = $this->token;
        }
        $authorization ="Authorization: Bearer ". $token;
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
        if($dataPost != false && $method != "GET"){
            $request->setPost($dataPost);
        }
        if($dataPost != false && $method == "GET"){
            $request->setGet($dataPost);
        }

        if($token != false){
            $request->setToken($token);
        }
        $request->setMethod($method);
        $request->send();
        $response = $request->result();
        // if($method == 'POST' || $method == 'PUT'){
        //     MessageBus::publish('sdocument-request-logs','request-log',['data'=>$dataPost,'res'=>$response]);
        // }
        // var_dump(json_encode($dataPost));
        // var_dump($response);
        // die;
        // trường hợp data trả về không đúng định dạng json
        return json_decode($response, true);
	}
    
}
?>