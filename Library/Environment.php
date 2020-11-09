<?php
namespace Library;
class Environment{
    public static function getPrefixEnvironment(){
        if($GLOBALS['env']['environment']!=""){
            return $GLOBALS['env']['environment'].".";
        }
        else{
            return "";
        }
    }
}