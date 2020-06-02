<?php
namespace Library;
class Route{
    public static $routersData = [
        'redirect'=>[],
        'get'=>[],
        'post'=>[],
        'put'=>[],
        'patch'=>[],
        'delete'=>[]
    ];
    public static function performRequest($uri){
        if(isset(self::$routersData['redirect'][$uri])){
            self::$routersData['redirect'][$uri]->run();
        }
        else{
            $method = self::getMethod();
            if(isset(self::$routersData[$method][$uri])){
                self::$routersData[$method][$uri]->run();
            }
            else{
                $runRouterWithParameter = self::runRouterWithParameter($method,$uri);
                if(!$runRouterWithParameter){
                    self::runDefaultRouter($method,$uri);
                }
            } 
        }   
    }
    private static function runRouterWithParameter($method,$uri){
        if(count(self::$routersData[$method])>0){
            foreach(self::$routersData[$method] as $router){
                if(RouteParameterBinder::bindRouterParameter($router,$uri)){
                    $router->run();
                    return true;
                }
            }
        }
        return false;
    }
    private static function runDefaultRouter($method,$uri){
        $controllerData = explode('/',$uri);
        $controller = $controllerData[0];
        $action = isset($controllerData[1])?$controllerData[1]: '';
        $routerObject = new Router($method,$uri,$controller,$action);
        $routerObject->run();
    }
    private static function getMethod(){
        if(isset($_SERVER['REQUEST_METHOD'])){
            return strtolower($_SERVER['REQUEST_METHOD']);
        }
        else{
            return 'get';
        }
    }
    public static function redirect($uri,$newUri){
        self::addRouter('redirect',$uri,$newUri);
    }
    public static function get($uri,$controller,$action='',$parameters=[]){
        self::addRouter('get',$uri,$controller,$action,$parameters);
    }
    public static function post($uri,$controller,$action='',$parameters=[]){
        self::addRouter('post',$uri,$controller,$action,$parameters);
    }
    public static function put($uri,$controller,$action='',$parameters=[]){
        self::addRouter('put',$uri,$controller,$action,$parameters);
    }
    public static function patch($uri,$controller,$action='',$parameters=[]){
        self::addRouter('patch',$uri,$controller,$action,$parameters);
        
    }
    public static function delete($uri,$controller,$action='',$parameters=[]){
        self::addRouter('delete',$uri,$controller,$action,$parameters);
    }
    private static function addRouter($method,$uri,$controller,$action='',$parameters=[]){
        $routerObject = new Router($method,$uri,$controller,$action,$parameters);
        self::$routersData[$method][$uri]=$routerObject;
    }
    
}