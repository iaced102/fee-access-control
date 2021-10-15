<?php
namespace Model;
use SqlObject;
class FilterInActionPack extends SqlObject
{
    public $filterId,
        $actionPackId,
        $action;
    public static $mappingFromDatabase = [
        'filterId'            =>  [ 'name' => 'filter_id',              'type' => 'string'],
        'actionPackId'        =>  [ 'name' => 'action_pack_id',         'type' => 'number'],
        'action'              =>  [ 'name' => 'action',                 'type' => 'string'],
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
    public static function create($filterId, $actionPackId, $action){
        $obj = new FilterInActionPack();
        $obj->actionPackId = $actionPackId;
        $obj->filterId = $filterId;
        $obj->action = json_encode($action);
        $obj->insert();
    }
    public static function getFilterInActionPack($actionPackId){
        $sql = "select * from filter_in_action_pack fa left join filter f on fa.filter_id = f.id where fa.action_pack_id = $actionPackId";
        return Connection::getDataQuerySelect($sql);
    }
}