<?php
namespace Library;

use Controller\Controller;
use Model\Model;
use Model\RoleAction;

class AccessControl{ 
    public static $variables = [];
    public static $mapOperation = [];
    
    public static function checkPermission($objectIdentifier,$action,$variables=[]){
        $variables = array_merge(self::$variables,$variables);
        $objectIdentifier = Str::bindDataToString($objectIdentifier,$variables); 
        $currentRole = Auth::getCurrentRole();
        //
        return self::getRoleActionLocal($currentRole,$objectIdentifier,$action);
    }
    public static function getRoleActionLocal($roleIdentifier,$objectIdentifier,$action){
        $key = json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'action'=>$action]);
        if(CacheService::getMemoryCache($key)==false){
            $roleAction = RoleAction::getByTop(1,"role_identifier='$roleIdentifier' AND object_identifier='$objectIdentifier' AND action='$action'");
            if(count($roleAction)>0){
                if($roleAction[0]->status==1){
                    CacheService::setMemoryCache($key,true);
                    return true;
                }
            }
            CacheService::setMemoryCache($key,false);
            return false;
        }
        else{
            return CacheService::getMemoryCache($key);
        }
        
        
    }
    public static function checkActionWithCurrentRole($objectIdentifier,$action){
        if(Auth::isBa()){
            return true;    
        }
        else{
            $roleIdentifier = Auth::getCurrentRole();
            return self::checkRoleActionRemote($roleIdentifier,$objectIdentifier,$action);
        }
        
    }
    /*
    * Lấy check flag trong memcache, nếu chưa có thì get về memcache, rồi get memcache trả về.
    */
    public static function checkRoleActionRemote($roleIdentifier,$objectIdentifier,$action){
        $key = json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier,'action'=>$action]);
        if(self::checkMemcache($roleIdentifier,$objectIdentifier)===false){
            self::getRoleActionRemote($roleIdentifier,$objectIdentifier);    
        }
        return CacheService::get($key);
        
    }
    /*
    * Lấy quyền về memcache, và set flag là đã lấy bất kể là có quyền hoặc không.
    */
    public static function getRoleActionRemote($roleIdentifier,$objectIdentifier){
        $listAction = []; 
        $dataResponse = Request::request(self::getAccessControlDomain()."/roles/$roleIdentifier/accesscontrol/$objectIdentifier");
        if(is_array($dataResponse)&&isset($dataResponse['status']) && $dataResponse['status']==STATUS_OK && isset($dataResponse['data'])){
            if(is_array($dataResponse['data']) && count($dataResponse['data'])>0){
                foreach($dataResponse['data'] as $accessControl){
                    $keyAccessControl = json_encode(['role'=>$accessControl['roleIdentifier'],'object'=>$accessControl['objectIdentifier'],'action'=>$accessControl['action']]);
                    CacheService::set($keyAccessControl,$accessControl['status']);
                    $listAction[]=$accessControl['action'];
                }
            }
        }
        CacheService::set(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]),$listAction);
    }
     /*
    * Lấy quyền về memcache all 
    */
    public static function getRoleActionRemoteAllObject($roleIdentifier){
        $listAction = []; 
        $dataResponse = Request::request(self::getAccessControlDomain()."/roles/$roleIdentifier/accesscontrol");
        if(is_array($dataResponse)&&isset($dataResponse['status']) && $dataResponse['status']==STATUS_OK && isset($dataResponse['data'])){
            if(is_array($dataResponse['data']) && count($dataResponse['data'])>0){
                foreach($dataResponse['data'] as $accessControl){
                    $keyAccessControl = json_encode(['role'=>$accessControl['roleIdentifier'],'object'=>$accessControl['objectIdentifier'],'action'=>$accessControl['action']]);
                    CacheService::set($keyAccessControl,$accessControl['status']);
                    if(!isset($listAction[$accessControl['objectIdentifier']])){
                        $listAction[$accessControl['objectIdentifier']]=[];
                    }
                    $listAction[$accessControl['objectIdentifier']][]=$accessControl['action'];
                }
            }
        }
        foreach($listAction as $object=>$actionsByObject){
            CacheService::set(json_encode(['role'=>$roleIdentifier,'object'=>$object]),$actionsByObject);
        }
        
    }
    /*
    *check xem đã được sync về chưa. Khác với đã lấy về nhưng không có quyền
    */
    public static function checkMemcache($roleIdentifier,$objectIdentifier){
        return CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));
    }
    /*
    * get List Action by Object Identifier
    */
    public static function getListAction($objectIdentifier){
        $roleIdentifier = Auth::getCurrentRole();
        $listActionInMemcache = CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));
        if($listActionInMemcache===false){
            self::getRoleActionRemote($roleIdentifier,$objectIdentifier);    
            return CacheService::get(json_encode(['role'=>$roleIdentifier,'object'=>$objectIdentifier]));//self::getListAction($roleIdentifier,$objectIdentifier);
        }
        return $listActionInMemcache;
    }
    
    public static function filterByPermission($objectIdentifier,$action){
        if(self::checkPermission($objectIdentifier,$action)){
            return true;
        }
        else{
            header('Content-Type: application/json');
            $output = [
                'status'=>STATUS_PERMISSION_DENIED,
                'message'=> Message::getStatusResponse(STATUS_PERMISSION_DENIED)
            ];
            print json_encode($output);
            exit;
        }
    }

    public static function getAccessControlDomain()
    {
        return "https://".Environment::getPrefixEnvironment()."accesscontrol.symper.vn";
    }
    

    /**
     * Lấy tất cả các operation ứng với $objectIdentifier, $action của user hiện tại
     */
    public static function getOperations($objectIdentifier, $action)
    {
        $currentRole = Auth::getCurrentRole();
        $mapOperation = self::$mapOperation;

        if(count($mapOperation) == 0){
            $remoteOperations = Request::request(self::getAccessControlDomain()."/roles/$currentRole/accesscontrol/$objectIdentifier");
            if(isset($remoteOperations['status']) && $remoteOperations['status'] == 200 &&  isset($remoteOperations['data']) && is_array($remoteOperations['data'])){
            
                foreach ($remoteOperations['data'] as $opr) {
                    $iden = $opr['objectIdentifier'];
                    $ac = $opr['action'];

                    if(!isset($mapOperation[$iden])){
                        $mapOperation[$iden] = [];
                    }

                    if(!isset($mapOperation[$iden][$ac])){
                        $mapOperation[$iden][$ac] = [];
                    }

                    $mapOperation[$iden][$ac][] = $opr;
                }
            }
            self::$mapOperation = $mapOperation;
        }

        if(isset($mapOperation[$objectIdentifier]) && isset($mapOperation[$objectIdentifier][$action]) ){
            return $mapOperation[$objectIdentifier][$action];
        }else{
            return [];
        }
    }

    /**
     * Bind giá trị của các câu query với ref(nếu có) vào bằng cách xác định chúng và gọi api sang syql để lấy dữ liệu về
     */
    public static function translateFilterStr($str)
    {
        $str = preg_replace("/\r\n|\r|\n/", ' ', $str);
        $remoteQueries = Str::getAllFunctionsCallInString($str, 'ref');
        $dataDomain = "https://".Environment::getPrefixEnvironment()."syql.symper.vn/formulas/get-data";
        foreach ($remoteQueries as $query) {
            $request = new Request($dataDomain);
            $request->setPost([
                'formula' => $query,
                'distinct' => 0
            ]);
            $request->send(Auth::getBearerToken());
            $result = $request->result();
            $jsonResult = json_decode($result, true);
            if(is_array($jsonResult) && $jsonResult['status'] == 200 && count($jsonResult['data']['data']) > 0){
                $row = $jsonResult['data']['data'][0];
                $value = array_values($row)[0];
                $str = str_replace($query, $value, $str);
            }
        }
        return $str;
    }

    /**
     * Lấy chuỗi filter cho một đối tượng ứng với action từ cấu hình filter access control 
     * @return false|String Nếu user không có quyền thì trả về false, nếu có quyền thì trả về chuỗi rỗng (khi không có filter)
     * hoặc chuỗi filter để append vào câu lệnh sql
     */
    public static function getFilterString($objectIdentifier, $action, $andAsPrefix = true)
    {
        if(Auth::isBa()){
            return '';
        }
        $oprations = self::getOperations($objectIdentifier, $action);
        if(count($oprations) > 0){
            $filterArr = [];
            $hasOperationWithoutFilter = false;
            foreach ($oprations as $op) {
                if(isset($op['filter']) && trim($op['filter']) != ''){
                    $filterArr[] = self::translateFilterStr($op['filter']);
                }else{
                    $hasOperationWithoutFilter = true;
                    break;
                }
            }

            if($hasOperationWithoutFilter || count($filterArr) == 0){
                return '';
            }else{
                $prefix = $andAsPrefix ? 'AND' : '';
                return $prefix.'( '.implode(' OR ', $filterArr).' )';
            }
        }else{
            return false;
        }
    }
}