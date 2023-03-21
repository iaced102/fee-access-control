<?php
namespace Controller;
use Model\Filter;
use Model\ActionPack;
use Model\ActionInPermissionPack;
use Model\ObjectIdentifier;
use Model\Operation;
use Model\OperationInActionPack;
use Model\PermissionPack;
use Model\FilterInActionPack;
use Model\PermissionRole;
use Library\Message;
use Library\Auth;
use Library\Request;
use Controller\ObjectIdentifyService;
use Exception;

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
    public function saveObjectIdentify($objType,$listId,$target,$source){
        $arr = [];
        if(count($listId)>0){
            foreach($listId as $k=>$v){
                array_push($arr,$objType.':'.$v);
            }
            $dataPost = [
                'listObj'=>$arr,
                'target'=>$target,
                'source'=>$source
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
        $ids = explode(',', $ids);

        try {
            if($objectType == 'filter'){
                $this->migrateFilter($source,$target,$ids);
            }else if ($objectType == 'action_pack') {
                $this->migrateActionPack($source,$target,$ids);
            } else if ($objectType == 'permission_pack') {
                $this->migratePermissionPack($source,$target,$ids);
            }
               
        } catch (\Throwable $th) {
            $this->ouput=[
                'status' => STATUS_BAD_REQUEST,
                'message' => $th->getMessage()
            ];
        }
    }
    public function migrateFilter($source,$target,$ids){
        //clone filter
        $arrIdFilter = Filter::migrateObjectsByIds($source, $target, $ids);
        if($arrIdFilter===false){
            throw new Exception('err when migrate filter');
        }

        //get id object identifier
        $idsFilter = "'".implode("','", $arrIdFilter)."'";
        $idObj = [];
        $listFilter = Filter::getByTop('',"id  IN (".$idsFilter.")");
        if (count($listFilter) > 0){
            foreach($listFilter as $key => $value){
                $arr = explode(',', $value->objectIdentifier);
                $idObj = array_merge($idObj,$arr);
            }
        }
        $idObj = array_unique($idObj);
        //clone object identifier
        $rsl = ObjectIdentifier::migrateObjectsByParents($source, $target,'object_identifier', $idObj);
        if($rsl===false){
            throw new Exception('err when migrate object identifier in filter');
        }
        
        $rsl = self::saveObjectIdentify('filter',$arrIdFilter,$target,$source);
        $this->output['message_migrate_object_identify'] = $rsl;
        $this->output["message"]=Message::getStatusResponse(STATUS_OK);
        $this->output["status"]=STATUS_OK;
    }

    public function migrateActionPack($source,$target,$ids){
        //clone action pack
        $idActionPack = ActionPack::migrateObjectsByIds($source, $target, $ids);
        if($idActionPack===false){
            throw new Exception('err when migrate action pack');
        }

        //clone filter in action pack
        $rsl = FilterInActionPack::migrateObjectsByParents($source, $target,'action_pack_id', $idActionPack);
        if($rsl===false){
            throw new Exception('err when migrate filter in action pack');
        }

        //clone filter in action pack
        $rsl = Filter::migrateObjectsByIds($source, $target, $rsl);
        if($rsl===false){
            throw new Exception('err when migrate filter');
        }

        //clone operation in action pack
        $rsl = OperationInActionPack::migrateObjectsByParents($source, $target,'action_pack_id', $idActionPack);
        if($rsl===false){
            throw new Exception('err when migrate operation in action pack');
        }

        //get id operation
        $ids = implode("','",$rsl);
        $idObj = [];
        $listOperation = OperationInActionPack::getByTop('',"id  IN ('".$ids."')");
        if (count($listOperation) > 0){
            foreach($listOperation as $key => $value){
                array_push($idObj,$value->operationId);
            }
        }
        $idObj = array_unique($idObj);

        //clone operation
        $rsl = Operation::migrateObjectsByIds($source, $target, $idObj);
        if($rsl===false){
            throw new Exception('err when migrate operation');
        }

        //get id object identifier
        $ids = implode("','",$rsl);
        $idObj = [];
        $listObj = Operation::getByTop('',"id  IN ('$ids')");
        if (count($listObj) > 0){
            foreach($listObj as $key => $value){
                array_push($idObj,$value->objectIdentifier);
            }
        }
        $idObj = array_unique($idObj);

        //clone object identifier
        $rsl = ObjectIdentifier::migrateObjectsByParents($source, $target,'object_identifier', $idObj);
        if($rsl===false){
            throw new Exception('err when migrate object identifier');
        }

        $rsl = self::saveObjectIdentify('action_pack',$idActionPack,$target,$source);
        $this->output['message_migrate_object_identify'] = $rsl;
        $this->output["message"]=Message::getStatusResponse(STATUS_OK);
        $this->output["status"]=STATUS_OK;
    }

    public function migratePermissionPack($source,$target,$ids){
         //clone permission
        $permissionId = PermissionPack::migrateObjectsByIds($source, $target, $ids);
        if($permissionId===false){
            throw new Exception('err when migrate permission');
        }
        //clone action pack in permission
        $rsl = ActionInPermissionPack::migrateObjectsByParents($source, $target,'permission_pack_id', $permissionId);
        if($rsl===false){
            throw new Exception('err when migrate action pack in permission');
        }

        //clone permission role
        $rsl = PermissionRole::migrateObjectsByParents($source, $target,'permission_pack_id', $permissionId);
        if($rsl===false){
            throw new Exception('err when migrate permission role');
        }

        $rsl = self::saveObjectIdentify('permission_pack',$permissionId,$target,$source);
        $this->output['message_migrate_object_identify'] = $rsl;
        $this->output["message"]=Message::getStatusResponse(STATUS_OK);
        $this->output["status"]=STATUS_OK;
    }
    
}