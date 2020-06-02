<?php
namespace Library;
class Router{
    public $method = '';
    public $uri = '';
    public $controller = '';
    public $action = '';
    public $parameters = [];
    public function __construct($method,$uri,$controller,$action='',$parameters=[]){
        $this->method = $method;
        $this->uri = $uri;
        $this->controller = $controller;
        $this->action = $action;
        $this->parameters = $parameters;
    }
    public function run(){
        if($this->method=='redirect'){
            $this->redirect();
        }
        else{
            if(class_exists('\\Controller\\'.$this->controller)){
                $controllerClass='\\Controller\\'.$this->controller;
                $controllerObject = new $controllerClass();
                $controllerObject->currentAction = $this->action;
                $extendParameters = $this->getExtendParameters();
                $parameters = array_merge($this->parameters,$extendParameters);
                $controllerObject->parameters = $parameters;
                $controllerObject->run();
            }
            else{
                Redirect::redirect404();
            }
        }
        
    }
    public  function redirect(){
        Redirect::redirect($this->controller);
    }
    public function getExtendParameters(){
        switch($this->method){
            case 'post':
                return $_POST;
            case 'get':
                return $_GET;            
            default:
                return $this->getPhpInputParameters();                         
        }
    }
    private  function getPhpInputParameters(){
        $parameters = [];
        parse_str(file_get_contents("php://input"),$parameters);
        return $parameters;
    }
    
    
   

}