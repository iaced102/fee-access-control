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
        'document_definition'   => ['name'=>'loại văn bản',         'ownerDomain' => 'document-management.symper.vn',   'action'=>['create','edit','submit','import','drop','restore','list','list_trash','submit_by_workflow']],
        'document_instance'     => ['name'=>'văn bản',              'ownerDomain' => 'document-management.symper.vn',   'action'=>['update','delete','restore','detail','list_instance','print','list_trash','update_by_workflow']],
        'workflow_definition'   => ['name'=>'quy trình',            'ownerDomain' => 'workflow.symper.vn',              'action'=>['list','create','deploy','drop','update','list_instance','start_instance','view']],
        'workflow_instance'     => ['name'=>'thể hiện quy trình',   'ownerDomain' => 'workflow.symper.vn',              'action'=>['detail','drop']],
        'syql'                  => ['name'=>'công thức',            'ownerDomain' => 'syql.symper.vn',                  'action'=>['create','update','execute']],
        'account'               => ['name'=>'người dùng',           'ownerDomain' => 'account.symper.vn',               'action'=>['add','update','info','detail','change_pass','disable','change_avatar','list','set_role']],
        'report'                => ['name'=>'báo cáo',              'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','update','view','list','drop']],
        'report_folder'         => ['name'=>'thư mục báo cáo ',     'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','rename','remove']],
        'dashboard'              => ['name'=>'dashboard',           'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','update','view','list','drop', 'export-data']],
        'dataflow'              => ['name'=>'dataset',              'ownerDomain' => 'bi.symper.vn',                    'action'=>['create','drop','list','update','detail']],
        'orgchart'              => ['name'=>'sơ đồ tổ chức',        'ownerDomain' => 'orgchart.symper.vn',              'action'=>['create','update','drop','list','detail','view_all','view_only_owner','view_only_sub']],
        'job'                   => ['name'=>'công việc',            'ownerDomain' => 'orgchart.symper.vn',              'action'=>['set_permission','set_user']],
        'department'            => ['name'=>'phòng ban',            'ownerDomain' => 'orgchart.symper.vn',              'action'=>['set_manager','view_all','view_only_owner','view_only_sub']],
        'role'                  => ['name'=>'vài trò người dùng',   'ownerDomain' => 'orgchart.symper.vn',              'action'=>['create','update','drop','list','update_permission','add_user']],
        'operation'             => ['name'=>'hành động',            'ownerDomain' => 'accesscontrol.symper.vn',         'action'=>['create','update','remove','list']],
        'action_pack'           => ['name'=>'nhóm hành động',       'ownerDomain' => 'accesscontrol.symper.vn',          'action'=>['create','update','detail','remove','list','add_operation','remove_operation']],
        'permission_pack'       => ['name'=>'nhóm quyền hạn',       'ownerDomain' => 'accesscontrol.symper.vn',         'action'=>['create','update','detail','remove','list','add_action_pack','remove_action_pack']],
        'application_definition'=> ['name'=>'Ứng dụng',             'ownerDomain' => 'core.symper.vn',                  'action'=>['view','create','update','remove']],
        'file'                  => ['name'=>'file',                 'ownerDomain' => 'file.symper.vn',                  'action'=>['view','create','update','remove']],
        'timesheet'             => ['name'=>'timesheet',            'ownerDomain' => 'timesheet.symper.vn',             'action'=>['list','view','create','update','remove']],
        "dashboard_tab" => [
            "name" => "Tab trong dashboard",
            "ownerDomain" => "bi-service.symper.vn",
            "action" => ['view', 'export-data']
        ],
        "dataset" => [
            "name" => "Dataset",
            "ownerDomain" => "bi-service.symper.vn",
            "action" => ['query']
        ],
        "document_control" => [
            "name" => "Control",
            "ownerDomain" => "document-management.symper.vn",
            "action" => ['hide','readonly', 'remove']
        ],
        "document_table" => [
            "name" => "Document table",
            "ownerDomain" => "document-management.symper.vn",
            "action" => ['hide','old_rows_readonly','old_rows_not_remove',]
        ],
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

// "task_manager_project" => [
//     "name" => "Dự án",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_project_category" => [
//     "name" => "Loại dự án",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_project_setting" => [
//     "name" => "Cài đặt dự án",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['config']
// ],
// "task_manager_access" => [
//     "name" => "Truy cập",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_role" => [
//     "name" => "Vai trò người dùng",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_permission" => [
//     "name" => "Phân quyền người dùng",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_kanban_board" => [
//     "name" => "Bảng Kanban",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_sprint" => [
//     "name" => "Sprint",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_component" => [
//     "name" => "Sprint",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_version" => [
//     "name" => "Sprint",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_issue_type" => [
//     "name" => "Loại tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_sub_task" => [
//     "name" => "Công việc chi tiết",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_task_life_cycle" => [
//     "name" => "Vòng đời của tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_field" => [
//     "name" => "Trường thông tin của tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_time_tracking" => [
//     "name" => "Theo dõi tiến độ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['config']
// ],
// "task_manager_issue_link" => [
//     "name" => "Liên kết tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_status" => [
//     "name" => "Trạng thái",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_priority" => [
//     "name" => "Mức độ ưu tiên",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail']
// ],
// "task_manager_issue" => [
//     "name" => "Tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['add','list','edit','delete','detail','editOwnIssue','addAttachFile','editAttachFile','removeAttachFile','assignUser','linkIssue','moveIssue','addComment','editAllComment','editOwnComment','deleteAllComment','deleteOwnComment']
// ],
// "task_manager_issue_field_config" => [
//     "name" => "Cấu hình trường thông tin của tác vụ",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['config']
// ],

// "task_manager_report_config" => [
//     "name" => "Báo cáo",
//     "ownerDomain" => "task-management-service.symper.vn",
//     "action" => ['view']
// ],