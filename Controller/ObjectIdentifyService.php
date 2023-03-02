<?php
namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Message;
use Library\Request;
use Library\Str;
use Model\ObjectIdentifier;

class ObjectIdentifyService extends Controller
{
    
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = true;
    }

    public function create(){
        if($this->checkParameter(['listObj'])){
            $listObj = $this->parameters['listObj'];
            if(count($listObj) > 0){
                foreach($listObj as $k=>$v){
                    $objIden = $v['objectIdentifier'];
                    $tenantTarget = $v['tenantId'];
                    Auth::ignoreTokenInfo();
                    $findObjectIden = ObjectIdentifier::getByTop('',"object_identifier = '$objIden' and tenant_id_ = $tenantTarget");
                    if(count($findObjectIden)==0){
                        $obj=new ObjectIdentifier($v);
                        $rsl = $obj->insert();
                        if($rsl == false){
                            $this->output['status'] = STATUS_BAD_REQUEST;
                            $this->output['message'] = Message::getStatusResponse(STATUS_BAD_REQUEST);
                        } else {
                            $this->output['status'] = STATUS_OK;
                            $this->output['message'] = Message::getStatusResponse(STATUS_OK);
                        }
                    }
                }
            }
        }

    }
    
}