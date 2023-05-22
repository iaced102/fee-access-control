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
    // function saveOperation($listOperation, $operationAndFilter = []){
    //     if($this->id!=''){
    //         $this->removeAllOperation();
    //         foreach($listOperation as $operationId=>$filter){
    //             // if(!empty($filter)){
    //             //     $filter = explode(",",$filter);
    //             //     for ($i=0; $i < count($filter); $i++) { 
    //             //         $this->insertActionPack($operationId, $filter[$i]);
    //             //     }
    //             // }else{
    //             //     $this->insertActionPack($operationId, "");
    //             // }
    //             if(isset($operationAndFilter[$operationId])){
    //                 $data = $operationAndFilter[$operationId];
    //                 $this->insertActionPack($operationId, $data['formulaStruct'], $data['formulaValue']);
    //             }else{
    //                 $this->insertActionPack($operationId, '', '');
    //             }
    //         }
    //     }   
    // }
    
    function saveOperation($listOperation, $operationAndFilter = []){
        if($this->id!=''){
            $this->removeAllOperation();
            $operationInActionpacks = [];
            $operationIds = "{".implode(",", array_keys($listOperation))."}";
            $where = ["conditions" => "id = ANY($1)", "dataBindings" => [$operationIds]];
            $listExistOperations = Operation::getByStatements('', $where);
            
            foreach($listExistOperations as $op){
                $operationId = $op->id;
                $newObj = null;
                if(isset($operationAndFilter[$operationId])){
                    $data = $operationAndFilter[$operationId];
                    $newObj = $this->getOperationInActionpackObj($operationId, $data['formulaStruct'], $data['formulaValue']);
                }else{
                    $newObj = $this->getOperationInActionpackObj($operationId, '', '');
                }
                $operationInActionpacks[] = $newObj;
            }
            OperationInActionPack::insertBulk($operationInActionpacks);
        }   
    }

    public function getOperationInActionpackObj($operationId, $formulaStruct,$formulaValue){
        $operationInActionPackObj =  new OperationInActionPack();
        $operationInActionPackObj->actionPackId = $this->id;
        $operationInActionPackObj->operationId = $operationId;
        $operationInActionPackObj->filter = '';
        $operationInActionPackObj->formulaStruct = $formulaStruct;
        $operationInActionPackObj->formulaValue  = $formulaValue;
        return $operationInActionPackObj;
    }
    // function saveFilter($listFilter){
    //     if(!empty($this->id)){
    //         $this->removeAllFilter();
    //         for ($i=0; $i < count($listFilter); $i++) { 
    //             $filter = $listFilter[$i];
    //             FilterInActionPack::create($filter['id'], $this->id,$filter);
    //         }
    //     }
    // }
    
    
    function saveFilter($listFilter){
        if(!empty($this->id)){
            $this->removeAllFilter();
            $list = [];
            for ($i=0; $i < count($listFilter); $i++) { 
                $filter = $listFilter[$i];
                $list[] = FilterInActionPack::create($filter['id'], $this->id,$filter, false);
            }
            FilterInActionPack::insertBulk($list);
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
        $ids = "{".implode(",", $ids)."}";
        $where = ["conditions" => "id = ANY($1)", "dataBindings" => [$ids]];
        $filters = Filter::getByStatements('',$where);
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
        foreach ($listFilter as $item) {
            $mapFilterIdToFilterStruct[$item['id']] = $item;
        }
        $filterIds = array_keys($mapFilterIdToFilterStruct);
        $filterIds = "{".implode(",", $filterIds)."}";

        $where = ["conditions" => "id = ANY($1)", "dataBindings" => [$filterIds]];
        $filterObjs = Filter::getByStatements('',$where);
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
        $usedObjectIdens = "{".implode(",", array_keys($usedOperations['objectIden']))."}";
        $usedActions = "{".implode(",", array_keys($usedOperations['action']))."}";
        $where = ["conditions" => "object_identifier = ANY($1) AND action = ANY($2)", "dataBindings" => [$usedObjectIdens,$usedActions]];
        $operationObjs = Operation::getByStatements('', $where);
        $mapObjIdenAndAction = [];
        foreach ($operationObjs as $obj) {
            $mapObjIdenAndAction[$obj->objectIdentifier.'_'.$obj->action] = $obj;
        }
        $rsl = [];
        foreach ($operationAndFilter as &$item) {
            if(isset($mapObjIdenAndAction[$item['objectIdentify'].'_'.$item['action']])){
                $operationObj = $mapObjIdenAndAction[$item['objectIdentify'].'_'.$item['action']];
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
        if(Operation::count("id=$1",[$operationId])>0){
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
        $id = $this->id;
        OperationInActionPack::deleteMulti("action_pack_id=$1",[$id]);
    }
    function removeAllFilter(){
        $id = $this->id;
        FilterInActionPack::deleteMulti("action_pack_id=$1",[$id]);
    }
    public static function checkNameExist($name, $id = false){
        if($id == false){
            $where = ["conditions" => "name = $1", "dataBindings" => [$name]];
            $listObject = self::getByStatements('',$where);
        }else{
            $where = ["conditions" => "name = $1 and id !=$2", "dataBindings" => [$name,$id]];
            $listObject = self::getByStatements('',$where);
        }
        if(count($listObject) > 0){
            return true;
        }
        return false;
    }
}