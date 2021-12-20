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
        $updateAt;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number'],
        'userCreate'        =>  [ 'name' => 'user_create',          'type' => 'string'],
        'userUpdate'        =>  [ 'name' => 'user_update',          'type' => 'string'],
        'createAt'          =>  [ 'name' => 'create_at',            'type' => 'datetime'],
        'updateAt'          =>  [ 'name' => 'update_at',            'type' => 'datetime'],
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
    function saveOperation($listOperation){
        if($this->id!=''){
            $this->removeAllOperation();
            foreach($listOperation as $operationId=>$filter){
                if(!empty($filter)){
                    $filter = explode(",",$filter);
                    for ($i=0; $i < count($filter); $i++) { 
                        $this->insertActionPack($operationId, $filter[$i]);
                    }
                }else{
                    $this->insertActionPack($operationId, "");
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
    private function insertActionPack($operationId, $filter){
        if(Operation::count("id=".$operationId)>0){
            $operationInActionPackObj =  new OperationInActionPack();
            $operationInActionPackObj->actionPackId = $this->id;
            $operationInActionPackObj->operationId = $operationId;
            $operationInActionPackObj->filter = $filter;
            $operationInActionPackObj->save();
        }
    }
    function removeAllOperation(){
        Connection::exeQuery("DELETE FROM operation_in_action_pack WHERE action_pack_id=".$this->id);
    }
    function removeAllFilter(){
        Connection::exeQuery("DELETE FROM filter_in_action_pack WHERE action_pack_id=".$this->id);
    }
}