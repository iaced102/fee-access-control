<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 3/22/18
 * Time: 17:40
 */

namespace Controller;

use Library\Auth;
use Library\Environment;
use Library\Redirect;
use Library\Message;
use Library\MessageBus;
use Library\Request;
use Library\Str;
use SqlObject;

class Controller
{
    public $defaultAction;
    public $currentAction;
    public $output = array();
    public $requireLogin = true;
    public $parameters = [];
    private $processUuid;
    private $requestTime;
    public function __construct()
    {
        $this->processUuid = SqlObject::createUUID();
    }
    public function run()
    {
        $this->requestTime = microtime(true);
        $this->checkRequireLogin();
        $action = $this->currentAction != '' ? $this->currentAction : $this->defaultAction;
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'GET', 'PATCH'])) {
            $dataKafka = [
                'parameters'    => $this->parameters,
                'method'        => $_SERVER['REQUEST_METHOD'],
                'action'        => $action,
                'processUuid'   => $this->processUuid,
                'uri'           => $_SERVER['REQUEST_URI'],
                'host'          => $_SERVER['HTTP_HOST'],
                'queryString'   => $_SERVER['QUERY_STRING'],
                'userAgent'     => $_SERVER['HTTP_USER_AGENT'],
                'clientIp'      => $_SERVER['REMOTE_ADDR'],
                'serverIp'      => $_SERVER['SERVER_ADDR'],
                'timeStamp'     => Str::currentTimeString(),
                'date'          => date("d-m-Y")
            ];
            MessageBus::publish('request-input','log', json_encode($dataKafka, JSON_UNESCAPED_UNICODE));
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
        if (!isset($this->output['status'])) {
            $this->output['status'] = STATUS_OK;
        }
        if ((!isset($this->output['message'])) || $this->output['message'] == '') {
            $this->output['message'] = Message::getStatusResponse($this->output['status']);
        }
        print json_encode($this->output);
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'GET', 'PATCH'])) {
            $dataJsonStr = json_encode($this->output, JSON_UNESCAPED_UNICODE);
            // Nếu kích thước 900 Kbs thì cắt chuỗi chỉ lấy 1 phần đầu
            if(strlen($dataJsonStr) >  900000){
                $dataJsonStr = substr($dataJsonStr,0,10000);
            }
            $endTime = microtime(true);
            $dataKafka = [
                'output'        => $dataJsonStr,
                'processUuid'   => $this->processUuid,
                'error'         => error_get_last(),
                'timeStamp'     => Str::currentTimeString(),
                'date'          => date("d-m-Y"),
                'requestTime'   => $endTime - $this->requestTime,
                'clientIp'      => $_SERVER['REMOTE_ADDR'],
                'serverIp'      => $_SERVER['SERVER_ADDR'],
                'uri'           => $_SERVER['REQUEST_URI'],
                'host'          => $_SERVER['HTTP_HOST'],
                'method'        => $_SERVER['REQUEST_METHOD'],
            ];
            MessageBus::publish('request-output','log', json_encode($dataKafka, JSON_UNESCAPED_UNICODE));
        }
    }
}
