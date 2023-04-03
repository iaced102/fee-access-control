<?php
namespace Model;

use Library\Auth;
use SqlObject;
class FilterInActionPack extends SqlObject
{
    public $filterId,
        $actionPackId,
        $action,
        $filterStruct,
        $filterValues,
        $tenantId;
    public static $mappingFromDatabase = [
        'filterId'            =>  [ 'name' => 'filter_id',              'type' => 'string','primary'=>true],
        'actionPackId'        =>  [ 'name' => 'action_pack_id',         'type' => 'string'],
        'action'              =>  [ 'name' => 'action',                 'type' => 'string'],
        'filterStruct'        =>  [ 'name' => 'filter_struct',                 'type' => 'string'],
        'filterValues'        =>  [ 'name' => 'filter_values',                 'type' => 'string'],
        'tenantId'            => [ 'name' => 'tenant_id_',              'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'filter_in_action_pack';
    }
    public static function getTopicName(){
       return 'filter_in_action_pack';
    }
    public static function create($filterId, $actionPackId, $filter, $autoSave = true){
        $obj = new FilterInActionPack();
        $obj->actionPackId = $actionPackId;
        $obj->filterId = $filterId;
        $obj->action = json_encode($filter['action']);
        $obj->filterStruct = isset($filter['filterStruct']) ? $filter['filterStruct'] : "";
        $obj->filterValues = isset($filter['filterValues']) ? $filter['filterValues'] : "";
        if($autoSave){
            $obj->insert();
        }
        return $obj;
    }
    public static function getFilterInActionPack($actionPackId){
        $tenantId = Auth::getTenantId();
        // $sql = "select * from filter_in_action_pack fa left join filter f on fa.filter_id = f.id where fa.action_pack_id ='".$actionPackId."'";
        $sql = "SELECT * FROM (
            SELECT * FROM filter_in_action_pack WHERE tenant_id_ = $1 AND action_pack_id =$2
        ) fa LEFT JOIN (
            SELECT * FROM filter WHERE tenant_id_ = $1
        ) f on fa.filter_id = f.id";
        return Connection::getDataQuerySelect($sql,[$tenantId,$actionPackId]);
    }
}