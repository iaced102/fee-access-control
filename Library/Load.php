<?php
namespace Library;
class Load{
    public static function autoLoad(){
        require_once DIR.'/Controller/Controller.php';
        require_once DIR.'/Model/Model.php';
        require_once DIR.'/Model/SqlObject.php';
        self::requireDir('/Library/');
        self::requireDir('/Config/');
        self::requireDir('/Model/');
        self::requireDir('/Controller/');
    }
    public static function requireDir($dirname){
        $ListFile=scandir(DIR.$dirname);
        foreach ($ListFile as $fileName) {
            if($fileName != '..' && $fileName != '.'){
                $path = DIR.$dirname.'/' . $fileName;
                if (is_file($path)) {
                    require_once $path;
                }
                else if(is_dir($path)){
                    self::requireDir($dirname.'/' . $fileName);
                }
            }
        }
    }
}   