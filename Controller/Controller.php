<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 3/22/18
 * Time: 17:40
 */
namespace Controller;

use Library\Auth;
use Library\CacheService;
use Library\Library;
use Library\Redirect;

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
            if(isset($dataLogin['supporter_email'])){
                return true;
            }
        }
        $this->output = [
            'status' => STATUS_PERMISSION_DENIED,
            'message'=> \Library\Message::getStatusResponse(STATUS_PERMISSION_DENIED)
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
            if(isset($dataLogin['supporter_email']) &&isset($dataLogin['supporter_id']) ){
                return [
                    'email' => $dataLogin['supporter_email'],
                    'id' => $dataLogin['supporter_id']
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
                        'message'=> \Library\Message::getStatusResponse(STATUS_BAD_REQUEST)
                    ];
                    return false;
                }
            }
        }
        return true;
    }
    private function returnOutput(){
        header('Content-Type: application/json');
        if((!isset($this->output['message']))|| $this->output['message']==''){
            $this->output['message'] = Message::getStatusResponse($this->output['status']);
        }
        print json_encode($this->output);
    }
}