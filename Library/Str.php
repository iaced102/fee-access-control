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
    public static function getArrayFromUnclearData($data){
        $array = [];
        if(is_array($data)){
            $array = $data;
        }
        else if(is_string($data)){
            $array = json_decode($data,true);
        }
        return $array;
    }
}