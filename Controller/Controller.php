<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 3/22/18
 * Time: 17:40
 */
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Redirect;
use Library\Message;
use Library\Request;

class Controller{
    public $defaultAction;
    public $currentAction;
    public $output = array();
    public $requireLogin = true;
    public $parameters = [];
    public function __construct(){
    }
    public function run(){
        $this->checkRequireLogin();
        $action = $this->currentAction !='' ? $this->currentAction : $this->defaultAction;
        if (in_array($_SERVER['REQUEST_METHOD'],['POST','PUT','DELETE'])) {
            $dataKafka = [
                'parameters'=>$this->parameters,
                'method'    => $_SERVER['REQUEST_METHOD'],
                'action'    => $action,
                'uri'       => $_REQUEST['uri'],
                'queryString' => $_SERVER['QUERY_STRING'],
                'userAgent' => $_SERVER['HTTP_USER_AGENT'],
                'clientIp' => $_SERVER['REMOTE_ADDR']
            ];
            $messageBusData = ['topic'=>'request-input', 'event' => 'log','resource' => json_encode($dataKafka),'env' => Environment::getEnvironment()];
            Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        }
        if(method_exists($this,$action)){
            $this->$action();
        }
        else{
            Redirect::redirect404();
        }
        $this->returnOutput();
    }
    
    private function checkRequireLogin(){
        if($this->requireLogin && (!$this->checkLoggedIn())){
            print 'Bạn không có quyền truy cập!';
            exit;
        }
    }
    private function checkLoggedIn(){
        $token = Auth::getBearerToken();
        if(!empty($token)){
            $dataLogin = Auth::getJwtData($token);
            if(!empty($dataLogin)){
                return true;
            }
        }
        return false;
    }
    public function checkLoggedInAsSupporter(){
        $dataLogin = Auth::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['id']) &&isset($dataLogin['type'])&&$dataLogin['type']=='ba' ){
                return true;
            }
        }
        $this->output = [
            'status' => STATUS_PERMISSION_DENIED,
            'message'=> Message::getStatusResponse(STATUS_PERMISSION_DENIED)
        ];
        return false;
    }
    public function checkPermission($type,$name,$action){
        $dataLogin = Auth::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['user_roles'][$type][$name][$action])){
                return $dataLogin['user_roles'][$type][$name][$action];
            }
        }
        return false;
    }
    
    public function getCurrentSupporter(){
        $dataLogin = Auth::getDataToken();
        if(!empty($dataLogin)){
            if(isset($dataLogin['id']) &&isset($dataLogin['type'])&&$dataLogin['type']=='ba' ){
                return [
                    'email' => $dataLogin['email'],
                    'id' => $dataLogin['id']
                ];
            }
        }
        return false;
    }
   
    public function checkParameter($listParameters){
        if(is_array($listParameters) && count($listParameters)>0 ){
            foreach($listParameters as $parameter){
                if(!isset($this->parameters[$parameter])){
                    $this->output = [
                        'status' => STATUS_BAD_REQUEST,
                        'message'=> Message::getStatusResponse(STATUS_BAD_REQUEST)
                    ];
                    return false;
                }
            }
        }
        return true;
    }
    private function returnOutput(){
        header('Content-Type: application/json');
        if(!isset($this->output['status'])){
            $this->output['status'] = STATUS_OK;
        }
        if((!isset($this->output['message'])) || $this->output['message']==''){
            $this->output['message'] = Message::getStatusResponse($this->output['status']);
        }
        print json_encode($this->output);
        if (in_array($_SERVER['REQUEST_METHOD'],['POST','PUT','DELETE'])) {
            $messageBusData = ['topic'=>'request-output', 'event' => 'log','resource' => json_encode($this->output),'env' => Environment::getEnvironment()];
            Request::request(MESSAGE_BUS_API.'publish', $messageBusData, 'POST');
        }
    }
}