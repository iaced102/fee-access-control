<?php
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Message;
use Library\Request;
use Library\Str;
use Model\ObjectIdentifier;
use Model\Connection;

class ObjectIdentifyService extends Controller
{
    
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }

    public function save(){
        if($this->checkParameter(['listObj','source','target'])){
            $listObj = $this->parameters['listObj'];
            $source = $this->parameters['source'];
            $target = $this->parameters['target'];
            // $rsl = ObjectIdentifier::migrateObjectsByIds($source, $target, $listObj);

            // if ($rsl === false){
            //     $this->output = [
            //         'status'    => STATUS_SERVER_ERROR,
            //         'message'   => 'can not migrate object identify',
            //     ];
            // } else {
            //     $this->output = [
            //         'status'    => STATUS_OK,
            //         'message'   => 'migrate success: '.implode(",",$rsl),
            //     ];
            // }

            $dataPost = [
                'source' => $source,
                'target' => $target,
                'ids'    => implode(",", $listObj)
            ];
            $response = Request::request("https://dev-object-relation.symper.vn/objects/tenant-migrate",$dataPost,'POST',false, 'application/json', false);
            var_dump($response);
            return $response;
        }

    }
    
}