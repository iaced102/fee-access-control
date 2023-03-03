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
        if($this->checkParameter(['listObj'])){
            $listObj = $this->parameters['listObj'];
            if(count($listObj) > 0){
                $arrToInsert=[];
                $arrIdDel=[];
                $query = ['BEGIN'];
                foreach($listObj as $k=>$v){
                    $objIden = $v['objectIdentifier'];
                    $tenantTarget = $v['tenantId'];
                    Auth::ignoreTokenInfo();
                    $findObjectIden = ObjectIdentifier::getByTop('',"object_identifier = '$objIden' and tenant_id_ = $tenantTarget");
                    array_push($arrToInsert,$v);
                    if(count($findObjectIden)>0) {
                        array_push($arrIdDel,$findObjectIden[0]->objectIdentifier);
                    }
                }
                
                $arrIdDel=implode("','",$arrIdDel);
                $deleteQuery="DELETE FROM object_identifier WHERE tenant_id_ = $tenantTarget AND object_identifier IN ('$arrIdDel')";
                array_push($query,$deleteQuery);

                $insertData=[];
                foreach($arrToInsert as $key => $value){
                    $objectIdentifier=$value['objectIdentifier'];
                    $name=$value['name'];
                    $type=$value['type'];
                    $objectType=$value['objectType'];
                    $title=$value['title'];
                    $tenantId=$value['tenantId'];
                    array_push($insertData,"('$objectIdentifier','$name','$type','$objectType','$title','$tenantId')");
                }
                $insertData=implode(",\n",$insertData);
                $insertQuery = "INSERT INTO object_identifier (object_identifier,name,type,object_type,title,tenant_id_) VALUES $insertData";
                array_push($query,$insertQuery);

                array_push($query,'COMMIT');

                $result = Connection::exeQuery(implode(";", $query));
                if($result != false){
                    $this->output['status'] = STATUS_OK;
                    $this->output['message'] = 'save object identify success';
                }else{
                    $this->output['status'] = STATUS_BAD_REQUEST;
                    $this->output['message'] = 'can not save object identify';
                }
            } else {
                $this->output['status'] = STATUS_BAD_REQUEST;
                $this->output['message'] = 'not have list object';
            }
        }

    }
    
}