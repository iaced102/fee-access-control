<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 3/22/18
 * Time: 17:40
 */

namespace Controller;

use Library\Auth;
use Library\Redirect;
use Library\Message;
use Library\Str;
class Controller
{
    public $defaultAction;
    public $currentAction;
    public $output = array();
    public $requireLogin = true;
    public $ignoreLogParameters = false;
    public $ignoreLogOuput = false;
    public $parameters = [];
    private $logData;
    public function __construct()
    {
        $this->logData = [];
    }
    public function run()
    {
        $this->checkRequireLogin();
        $action = $this->currentAction != '' ? $this->currentAction : $this->defaultAction;
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'GET', 'PATCH'])) {
            $this->logData = [
                'parameters'    => ($this->ignoreLogParameters) ? "" : json_encode($this->parameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'method'        => $_SERVER['REQUEST_METHOD'],
                'action'        => $action,
                'requestTime'   => microtime(true),
                'uri'           => $_SERVER['REQUEST_URI'],
                'host'          => $_SERVER['HTTP_HOST'],
                'queryString'   => $_SERVER['QUERY_STRING'],
                'userAgent'     => $_SERVER['HTTP_USER_AGENT'],
                'clientIp'      => $_SERVER['REMOTE_ADDR'],
                'clientId'      => isset($_SERVER['HTTP_CLIENTID']) ? $_SERVER['HTTP_CLIENTID'] : "0",
                'serverIp'      => $_SERVER['SERVER_ADDR'],
                'timeStamp'     => Str::currentTimeString(),
                'date'          => date("d-m-Y")
            ];
        }
        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            Redirect::redirect404();
        }
        $this->returnOutput();
    }

    private function checkRequireLogin()
    {
        if ($this->requireLogin && (!$this->checkLoggedIn())) {
            print 'Bạn không có quyền truy cập!';
            exit;
        }
    }
    private function checkLoggedIn()
    {
        $token = Auth::getBearerToken();
        if (!empty($token)) {
            $dataLogin = Auth::getJwtData($token);
            if (!empty($dataLogin)) {
                return true;
            }
        }
        return false;
    }
    public function checkLoggedInAsSupporter()
    {
        $dataLogin = Auth::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['id']) && isset($dataLogin['type']) && $dataLogin['type'] == 'ba') {
                return true;
            }
        }
        $this->output = [
            'status' => STATUS_PERMISSION_DENIED,
            'message' => Message::getStatusResponse(STATUS_PERMISSION_DENIED)
        ];
        return false;
    }
    public function checkPermission($type, $name, $action)
    {
        $dataLogin = Auth::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['user_roles'][$type][$name][$action])) {
                return $dataLogin['user_roles'][$type][$name][$action];
            }
        }
        return false;
    }

    public function getCurrentSupporter()
    {
        $dataLogin = Auth::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['id']) && isset($dataLogin['type']) && $dataLogin['type'] == 'ba') {
                return [
                    'email' => $dataLogin['email'],
                    'id' => $dataLogin['id']
                ];
            }
        }
        return false;
    }

    public function checkParameter($listParameters)
    {
        if (is_array($listParameters) && count($listParameters) > 0) {
            foreach ($listParameters as $parameter) {
                if (!isset($this->parameters[$parameter])) {
                    $this->output = [
                        'status' => STATUS_BAD_REQUEST,
                        'message' => Message::getStatusResponse(STATUS_BAD_REQUEST)
                    ];
                    return false;
                }
            }
        }
        return true;
    }
    private function returnOutput()
    {
        header('Content-Type: application/json');
        if (!is_array($this->output)) {
            print $this->output;
        } else {
            if (!isset($this->output['status'])) {
                $this->output['status'] = STATUS_OK;
            }
            if ((!isset($this->output['message'])) || $this->output['message'] == '') {
                $this->output['message'] = Message::getStatusResponse($this->output['status']);
            }
            print json_encode($this->output);
        }
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'GET', 'PATCH'])) {
            $dataJsonStr = ($this->ignoreLogOuput) ? "" : json_encode($this->output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // Nếu kích thước 400 Kbs thì bỏ qua
            if(strlen($dataJsonStr) >  400000){
                $dataJsonStr = "";
            }
            $userId = Auth::getCurrentUserId();
            $tenantId = Auth::getTenantId();
            $this->logData["requestTime"] = microtime(true) - $this->logData["requestTime"];
            $this->logData["output"] = $dataJsonStr;
            $lastErr = error_get_last();
            $this->logData["error"] = !empty($lastErr) ? json_encode($lastErr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
            $this->logData["tenantId"] = "$tenantId";
            $this->logData["userId"] = ($userId != false) ? "$userId" : "";
            $this->logData["userRole"] = Auth::getCurrentRole();
            $this->logData["statusCode"] = !is_array($this->output) ? 200 : $this->output['status'];
            file_put_contents(__DIR__ . "/../log/request-" . date("d-m-Y") . ".log", json_encode($this->logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
        }
    }
}
