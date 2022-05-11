<?php
namespace Model;
use SqlObject;
class ActionPack extends SqlObject
{
    public const STATUS_ENABLE = 1;
    public const STATUS_DISABLE = 0;
    public $id,
        $name,
        $description,
        $status,
        $userCreate,
        $userUpdate,
        $createAt,
        $updateAt,
        $tenantId;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'string', 'primary'=>true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'userCreate'        =>  [ 'name' => 'user_create',          'type' => 'string'],
        'userUpdate'        =>  [ 'name' => 'user_update',          'type' => 'string'],
        'createAt'          =>  [ 'name' => 'create_at',            'type' => 'datetime'],
        'updateAt'          =>  [ 'name' => 'update_at',            'type' => 'datetime'],
        'tenantId'          => [ 'name' => 'tenant_id_',            'type' => 'number']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'action_pack';
    }
    public static function getTopicName(){
       return 'action_pack';
    }
    function saveOperation($listOperation, $operationAndFilter = []){
        if($this->id!=''){
            $this->removeAllOperation();
            foreach($listOperation as $operationId=>$filter){
                // if(!empty($filter)){
                //     $filter = explode(",",$filter);
                //     for ($i=0; $i < count($filter); $i++) { 
                //         $this->insertActionPack($operationId, $filter[$i]);
                //     }
                // }else{
                //     $this->insertActionPack($operationId, "");
                // }
                if(isset($operationAndFilter[$operationId])){
                    $data = $operationAndFilter[$operationId];
                    $this->insertActionPack($operationId, $data['formulaStruct'], $data['formulaValue']);
                }else{
                    $this->insertActionPack($operationId, '', '');
                }
            }
        }   
    }
    function saveFilter($listFilter){
        if(!empty($this->id)){
            $this->removeAllFilter();
            for ($i=0; $i < count($listFilter); $i++) { 
                $filter = $listFilter[$i];
                FilterInActionPack::create($filter['id'], $this->id,$filter);
            }
        }
    }

    public static function standardObjIden($str)
    {
        if(strpos($str, 'document_instance') === 0){
            $str = str_replace('document_instance','document_definition', $str).':0';
        }
        return $str;
    }

    public static function getConditionFromTree($tree, &$usedFilterIds)
    {
        if($tree['nodeType'] == 'item'){
            $usedFilterIds[$tree['column']['id']] = true;
            return '__'.$tree['column']['id'].'__';
        }else if(isset($tree['children'])){
            $treeStrs = [];
            foreach ($tree['children'] as $subChild) {
                $subStr = self::getConditionFromTree($subChild, $usedFilterIds);
                if($subStr != ''){
                    $treeStrs[] = "($subStr)";
                }
            }
            return implode(' '.$tree['name'].' ', $treeStrs);
        }else{
            return '';
        }
    }

    public static function replaceRealFilterValue($rsl, $usedFilterIds)
    {
        $ids = array_keys($usedFilterIds);
        $ids = "'".implode("','", $ids)."'";
        $filters = Filter::getByTop('', " id IN ($ids) ");
        foreach ($filters as $item) {
            $usedFilterIds[$item->id] = $item->formula;
        }
        foreach ($rsl as &$item) {
            $matches = [];
            preg_match_all('/\(__([a-z0-9-_]+)__\)/i', $item['formulaValue'], $matches);
            foreach ($matches[0] as $matchedId) {
                $id = str_replace('(__', '', $matchedId);
                $id = str_replace('__)', '', $id);
                $item['formulaValue'] = str_replace('__'.$id.'__', $usedFilterIds[$id], $item['formulaValue']);
            }
        }
        return $rsl;
    }

    function attachFilterToOperation($listFilter, $apId){
        $mapFilterIdToFilterStruct = [];
        $filterObjs = [];
        if(count($listFilter) > 0){
            foreach ($listFilter as $item) {
                $mapFilterIdToFilterStruct[$item['id']] = $item;
            }
    
            $filterIds = array_keys($mapFilterIdToFilterStruct);
            $filterIds = "'".implode("','", $filterIds)."'";
    
            $filterObjs = Filter::getByTop('',"id IN ($filterIds)");
        }
        // Lấy danh sách filter
        // $mapFilterById = [];
        // foreach ($filterObjs as $obj) {
        //     $mapFilterById[$obj->id] = $obj;
        // }

        // lấy ra các cấu trúc filter cần thiết
        $usedObjIden = [];
        $operationAndFilter = [];
        $usedOperations = [
            'action'    => [],
            'objectIden'    => []
        ];
        $usedFilterIds = [];

        foreach ($filterObjs as $obj) {
            $objectIdentifier = self::standardObjIden($obj->objectIdentifier);
            if(!isset($usedObjIden[$objectIdentifier])){
                $filterStruct = $mapFilterIdToFilterStruct[$obj->id];
                $filterStruct = json_decode($filterStruct['filterStruct'], true);
                $usedObjIden[$objectIdentifier] = true;
                foreach ($filterStruct as $item) {
                    foreach ($item['actions'] as $actionItem) {
                        $newItem = [
                            'objectIdentify'    => $objectIdentifier,
                            'action'            => $actionItem['field'],
                            'formulaValue'      => self::getConditionFromTree($item['conditions'][0], $usedFilterIds),
                            'formulaStruct'     => json_encode($item['conditions'], JSON_UNESCAPED_UNICODE),
                            'actionPackId'      => $apId
                        ];
                        $usedOperations[$newItem['objectIdentify'].'_'.$newItem['action']] = '';
                        $operationAndFilter[] = $newItem;
                        $usedOperations['action'][$newItem['action']] = true; 
                    }
                }
                $usedOperations['objectIden'][$objectIdentifier] = true;
            }
        }
        $usedObjectIdens = "'".implode("','", array_keys($usedOperations['objectIden']))."'";
        $usedActions = "'".implode("','", array_keys($usedOperations['action']))."'";

        $operationObjs = Operation::getByTop('', " object_identifier IN ($usedObjectIdens) AND action IN ($usedActions)");
        $mapObjIdenAndction = [];
        foreach ($operationObjs as $obj) {
            $mapObjIdenAndction[$obj->objectIdentifier.'_'.$obj->action] = $obj;
        }
        $rsl = [];
        foreach ($operationAndFilter as &$item) {
            $key = $item['objectIdentify'].'_'.$item['action'];
            if(isset($mapObjIdenAndction[$key])){
                $operationObj = $mapObjIdenAndction[$key];
                $rsl[$operationObj->id]  = [
                    'formulaValue' => $item['formulaValue'],
                    'formulaStruct' => $item['formulaStruct']
                ];
            }
        }

        if(count($usedFilterIds) > 0){
            $rsl = self::replaceRealFilterValue($rsl, $usedFilterIds);
        }
        return $rsl;
        /**
         * Lấy ra các filter group struct ứng với từng objectIden
         * 
         */

        /**
         *  đích: lấy được cấu trúc nhóm filter ứng với từng operation
         *  {
         *      objectIdentify: '',
         *      action: '',
         *      filterValue: '',
         *      filterStruct: '',
         *      actionPackId: ''
         * }
         */

        
    }
    private function insertActionPack($operationId, $formulaStruct,$formulaValue){
        if(Operation::count("id='".$operationId."'")>0){
            $operationInActionPackObj =  new OperationInActionPack();
            $operationInActionPackObj->actionPackId = $this->id;
            $operationInActionPackObj->operationId = $operationId;
            $operationInActionPackObj->filter = '';
            $operationInActionPackObj->formulaStruct = $formulaStruct;
            $operationInActionPackObj->formulaValue  = $formulaValue;
            $operationInActionPackObj->save();
        }
    }
    function removeAllOperation(){
        Connection::exeQuery("DELETE FROM operation_in_action_pack WHERE action_pack_id='".$this->id."'");
    }
    function removeAllFilter(){
        Connection::exeQuery("DELETE FROM filter_in_action_pack WHERE action_pack_id='".$this->id."'");
    }
    public static function checkNameExist($name, $id = false){
        if($id == false){
            $listObject = self::getByTop('',"name = '$name'");
        }else{
            $listObject = self::getByTop('',"name = '$name' and id !='". $id."'");
        }
        if(count($listObject) > 0){
            return true;
        }
        return false;
    }
}