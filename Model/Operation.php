<?php
namespace Model;
use SqlObject;
class Operation extends SqlObject
{
    public const STATUS_ENABLE = 1;
    public const STATUS_DISABLE = 0;
    public $id,
        $name,
        $description,
        $action,
        $objectName,
        $objectIdentifier,
        $objectType,
        $status;
    public static $mappingFromDatabase = [
        'id'                =>  [ 'name' => 'id',                   'type' => 'number', 'primary'=>true, 'auto_increment' => true],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'description'       =>  [ 'name' => 'description',          'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectName'        =>  [ 'name' => 'object_name',          'type' => 'string'],
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'status'            =>  [ 'name' => 'status',               'type' => 'number']
    ];
    public static $listAction = [
        'document_definition'   => ['name'=>'loại văn bản',         'ownerDomain' => 'document-management.symper.vn',   'action'=>['create','edit','submit','drop','restore','list','list_trash','list_instance']],
        'document_instance'     => ['name'=>'văn bản',              'ownerDomain' => 'document-management.symper.vn',   'action'=>['update','delete','restore','detail','print','list_trash']],
        'workflow_definition'   => ['name'=>'quy trình',            'ownerDomain' => 'workflow.symper.vn',              'action'=>['list','create','deploy','drop','update','list_instance','start_instance','view']],
        'workflow_instance'     => ['name'=>'thể hiện quy trình',   'ownerDomain' => 'workflow.symper.vn',              'action'=>['detail','drop']],
        'syql'                  => ['name'=>'công thức',            'ownerDomain' => 'syql.symper.vn',                  'action'=>['create','update','execute']],
        'account'               => ['name'=>'người dùng',           'ownerDomain' => 'account.symper.vn',               'action'=>['add','update','info','detail','change_pass','disable','change_avatar','list','set_role']],
        'report'                => ['name'=>'báo cáo',              'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','update','view','list','drop']],
        'report_folder'         => ['name'=>'thư mục báo cáo ',     'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','rename','remove']],
        'dashboard'              => ['name'=>'dashboard',           'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','update','view','list','drop']],
        'dataflow'              => ['name'=>'dataset',              'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','drop','list','update','detail']],
        'orgchart'              => ['name'=>'sơ đồ tổ chức',        'ownerDomain' => 'orgchart.symper.vn',              'action'=>['create','update','drop','list','detail','view_all','view_only_owner','view_only_sub']],
        'job'                   => ['name'=>'công việc',            'ownerDomain' => 'orgchart.symper.vn',              'action'=>['set_permission','set_user']],
        'department'            => ['name'=>'phòng ban',            'ownerDomain' => 'orgchart.symper.vn',              'action'=>['set_manager','view_all','view_only_owner','view_only_sub']],
        'role'                  => ['name'=>'vài trò người dùng',   'ownerDomain' => 'orgchart.symper.vn',              'action'=>['create','update','drop','list','update_permission','add_user']],
        'operation'             => ['name'=>'hành động',            'ownerDomain' => 'accesscontrol.symper.vn',         'action'=>['create','update','remove','list']],
        'action_pack'           => ['name'=>'nhóm hành động',       'ownerDomain' => 'accesscontrol.symper.vn',          'action'=>['create','update','detail','remove','list','add_operation','remove_operation']],
        'permission_pack'       => ['name'=>'nhóm quyền hạn',       'ownerDomain' => 'accesscontrol.symper.vn',         'action'=>['create','update','detail','remove','list','add_action_pack','remove_action_pack']],
        'application_definition'=> ['name'=>'Ứng dụng',             'ownerDomain' => 'core.symper.vn',                  'action'=>['view','create','update','remove']],
        'knowledge'             => ['name'=>'knowledge',            'ownerDomain' => 'kh.symper.vn',                    'action'=>['view','create','update','remove']],
        'file'                  => ['name'=>'file',                 'ownerDomain' => 'file.symper.vn',                  'action'=>['view','create','update','remove']],
        'timesheet'             => ['name'=>'timesheet',            'ownerDomain' => 'timesheet.symper.vn',             'action'=>['list','view','create','update','remove']]
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'operation';
    }
    public static function getTopicName(){
       return 'operation';
    }
    public static function getListActionByType($type){
        $type = trim(strtolower(str_replace(' ','_',$type)));
        if(isset(self::$listAction[$type])){
            return self::$listAction[$type]['action'];
        }
        return [];
    }
    public static function getListType(){
        return array_keys(self::$listAction);
    }
}