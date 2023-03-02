<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Library\Request;
use Model\Users;

class ObjectTenantMigration extends Controller{
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'migrate';
        $this->requireLogin = true;
    }

    /**
     * @param array $listObj mảng của các object, có dạng:
     * [
     *      [
     *          'id' => '',
     *          'name' => '', // optional
     *          'title' => '',
     *          'objectType' => '', // optional
     *      ]
     * ]
     */
    public function saveObjectIdentify($objType,$listObj,$target){
        $arr = [];
        if(count($listObj)>0){
            foreach($listObj as $k=>$v){
                $obj = ['objectIdentifier'=>$objType.':'.$v->id,
                    'title'=>$v->title,
                    'name'=>$objType=='document_definition'? $v->name:'',
                    'type'=>$objType,
                    'objectType'=>isset($v->objectType)?$v->objectType:'',
                    'tenantId'=>$target
                ];
                array_push($arr,$obj);
            }
            $dataPost = [
                'listObj'=>$arr
            ];
            $token = "Bearer ".Auth::getBearerToken();
            $response =Request::request(ACCESS_CONTROL_SERVICE.'/object-identify',$dataPost,'POST',$token, 'application/json', false);
            return $response;
        }
    }

    /**
     * Thực hiện migrate dữ liệu của các object trong hệ thống từ tenant này sang tenant khác
     */
    public function migrate()
    {   
        if (!$this->checkParameter(['source','target','objectType','ids'])) {
            $this->output = [
                'status'    => STATUS_BAD_REQUEST,
                'message'   => Message::getStatusResponse(STATUS_BAD_REQUEST),
                'data'      => []
            ];
            return;
        }


        $source = $this->parameters['source'];
        $target = $this->parameters['target'];
        $objectType = $this->parameters['objectType'];
        $ids = $this->parameters['ids'];

        // Ví dụ về xử lý việc migrate
        // if($objectType == 'users'){
        //     Users::migrateObjectsByIds($source, $target, $ids);
        // }else if ($objectType == 'dashboard') {
        //     ...
        // }
    }
}