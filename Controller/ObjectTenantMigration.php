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

class ObjectTenantMigration extends Controller{
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'migrate';
        $this->requireLogin = true;
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
            $ids = "'".implode("','", $rsl)."'";
            $idObj = [];
            $listFilter = Filter::getByTop('',"id  IN (".$ids.")");
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