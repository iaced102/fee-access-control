<?php
namespace Model;

use Library\Auth;
use Library\MessageBus;
use SqlObject;
class RoleAction extends SqlObject{
    
   
    public 
        $objectIdentifier,
        $action,
        $objectType,
        $name,
        $roleIdentifier,
        $status,
        $filter,
        $filterCombination,
        $filterNew,
        $actionPackId,
        $tenantId;
    public static $mappingFromDatabase = [
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string'],
        'filter'            =>  [ 'name' => 'filter_formula',               'type' => 'string'],
        'filterNew'         =>  [ 'name' => 'filter_formula_new',               'type' => 'string'],
        'status'            =>  [ 'name' => 'filter_status',        'type' => 'string'],
        'actionPackId'      =>  [ 'name' => 'action_pack_id',        'type' => 'string'],
        'filterCombination' =>  [ 'name' => 'filter_combination',        'type' => 'string'],
        'tenantId'          =>  [ 'name' => 'tenant_id_', 'type' => 'number'],
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName($tenantId = ''){
        return $tenantId == '' ? 'role_action_'.Auth::getTenantId() : "role_action_$tenantId";
    }
    public static function getTopicName(){
       return 'role_action';
    }

    public static function printResultAndContinue($data)
    {
        // Buffer all upcoming output...
        ob_start();
        // Send your response.
        $data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        echo $data;
        // Get the size of the output.
        $size = ob_get_length();
        // Disable compression (in case content length is compressed).
        header("Content-Encoding: none");
        // Set the content length of the response.
        header("Content-Length: {$size}");
        // Close the connection.
        header("Connection: close");
        // Flush all output.
        ob_end_flush();
        ob_flush();
        flush();

        if (is_callable('fastcgi_finish_request')) {
            /*
             * This works in Nginx but the next approach not
             */
            session_write_close();
            fastcgi_finish_request();
    
            return;
        }
    }

    /**
     * hàm in ra output của controllerObj, rồi đóng connection rồi mới thực thi tiếp việc refresh view nhằm tiết kiệm thời gian chờ đợi của user
     */
    public static function closeConnectionAndRefresh($controllerObj)
    {
        self::printResultAndContinue($controllerObj->output);
        self::refresh();
    }

    public static function checkViewExist($viewName)
    {
        $rsl = Connection::exeQueryAndFetchData("SELECT matviewname FROM pg_matviews WHERE matviewname = '$viewName'");
        return $rsl != false && !empty($rsl);
    }

    public static function refresh($controllerObj = null){
        $viewName = self::getTableName();
        if(!self::checkViewExist($viewName)){
            self::createView();
            MessageBus::publish("role_action","created", ["name"  => $viewName]);
        }else{
            Connection::exeQuery("REFRESH MATERIALIZED VIEW $viewName");
            MessageBus::publish("role_action","update",["name"  => $viewName]);
        }
    }

    public static function makeNewViewForTenant($tenantId = '')
    {
        $viewName = self::getTableName($tenantId);
        if(!self::checkViewExist($viewName)){
            self::createView($tenantId);
        }
    }

    public static function createView($tenantId = ''){
        if ($tenantId == '') {
            $tenantId = Auth::getTenantId();
        }

        $createViewQuery = "
        CREATE MATERIALIZED VIEW role_action_$tenantId AS SELECT o.object_identifier,

        o.action,
    
        o.object_type,
    
        o.name,
    
        o.status,
    
        pr.role_identifier,
    
        filter.formula AS filter_formula,
    
        filter.status AS filter_status,
    
        filter.id AS filter_id,
    
        op.formula_value AS filter_formula_new,
    
        op.formula_struct AS filter_combination,
    
        app.action_pack_id,
        $tenantId AS tenant_id_
    
       FROM ((((operation o
    
         JOIN operation_in_action_pack op ON (((o.id = op.operation_id) AND (o.tenant_id_ = op.tenant_id_))))
    
         JOIN action_in_permission_pack app ON (((op.action_pack_id = app.action_pack_id) AND (op.tenant_id_ = app.tenant_id_))))
    
         JOIN permission_role pr ON (((app.permission_pack_id = pr.permission_pack_id) AND (pr.tenant_id_ = app.tenant_id_))))
    
         LEFT JOIN filter ON ((((op.filter)::text = (filter.id)::text) AND (op.tenant_id_ = filter.tenant_id_)))) WHERE o.tenant_id_ = $tenantId"; 
        return Connection::exeQuery($createViewQuery);
    }
}