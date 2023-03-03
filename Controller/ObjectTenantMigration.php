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
                    'title'=>isset($v->title) ? $v->title : $v->name,
                    'name'=>isset($v->name) ? $v->name : $v->title,
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
        $ids = explode(',', $ids);

        if($objectType == 'filter'){
            //clone filter
            $rsl = Filter::migrateObjectsByIds($source, $target, $ids);
            self::checkRsl($rsl);

            //get id object identifier
            $idsFilter = "'".implode("','", $rsl)."'";
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
            self::checkRsl($rsl);

            $list = Filter::getByTop('',"id in ('$idsFilter')",'','id,name');
            
            $rsl = self::saveObjectIdentify('filter',$list,$target);
            $this->output['message_migrate_object_identify'] = $rsl;
            $this->output["message"]=Message::getStatusResponse(STATUS_OK);
            $this->output["status"]=STATUS_OK;
        }else if ($objectType == 'action_pack') {

            //clone action pack
            $idActionPack = ActionPack::migrateObjectsByIds($source, $target, $ids);
            self::checkRsl($idActionPack);

            //clone filter in action pack
            $rsl = FilterInActionPack::migrateObjectsByParents($source, $target,'action_pack_id', $idActionPack);
            self::checkRsl($rsl);

            //clone filter in action pack
            $rsl = Filter::migrateObjectsByIds($source, $target, $rsl);
            self::checkRsl($rsl);

            //clone operation in action pack
            $rsl = OperationInActionPack::migrateObjectsByParents($source, $target,'action_pack_id', $idActionPack);
            self::checkRsl($rsl);

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
            self::checkRsl($rsl);

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
            self::checkRsl($rsl);

            $idActionPack=implode("','",$idActionPack);
            $list = ActionPack::getByTop('',"id in ('$idActionPack')",'','id,name');
            
            $rsl = self::saveObjectIdentify('action_pack',$list,$target);
            $this->output['message_migrate_object_identify'] = $rsl;
            $this->output["message"]=Message::getStatusResponse(STATUS_OK);
            $this->output["status"]=STATUS_OK;
        } else if ($objectType == 'permission_pack') {

            //clone permission
            $permissionId = PermissionPack::migrateObjectsByIds($source, $target, $ids);
            self::checkRsl($permissionId);

            //clone action pack in permission
            $rsl = ActionInPermissionPack::migrateObjectsByParents($source, $target,'permission_pack_id', $permissionId);
            self::checkRsl($rsl);

            //clone permission role
            $rsl = PermissionRole::migrateObjectsByParents($source, $target,'permission_pack_id', $permissionId);
            self::checkRsl($rsl);

            $permissionId=implode("','",$permissionId);
            $list = PermissionPack::getByTop('',"id in ('$permissionId')",'','id,name');
            
            $rsl = self::saveObjectIdentify('permission_pack',$list,$target);
            $this->output['message_migrate_object_identify'] = $rsl;
            $this->output["message"]=Message::getStatusResponse(STATUS_OK);
            $this->output["status"]=STATUS_OK;
        }
    }
    public function checkRsl($rsl){
        if ($rsl === false){
            $this->output = [
                'status'    => STATUS_SERVER_ERROR,
                'message'   => Message::getStatusResponse(STATUS_SERVER_ERROR),
            ];
        }
    }
}