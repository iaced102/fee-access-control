<?php
namespace Library;
class Redirect{
    

    public static function redirect($url){
        header('location: '.$url);
    }
    public static function redirect404(){
        self::Redirect('/404.html');
        exit;
    }
    public static function redirectByJavascript($url){
        echo "<script>window.location='".$url."'</script>";
    }
}