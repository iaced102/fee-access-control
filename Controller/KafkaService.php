<?php
namespace Controller;

use Library\MessageBus;
use Library\Auth;
use Library\CacheService;
use Library\Message;
use Library\Str;
use Model\ActionPack;
use Model\Operation;
use Model\OperationInActionPack;
use Model\PermissionRole;
use Model\RoleAction;
use Model\Users;
class KafkaService extends Controller
{
    
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'list';
        $this->requireLogin = false;
    }
    function subscribe(){
        
        MessageBus::subscribeMultiTopic(
            ['users','user_group','user','role_action'],
            '',
            function($topic,$item){
                if($topic=='role_action'){
                    $this->processRoleAction($item);
                }
            },
            '/KafkaService/subscribe',
            '/KafkaService/stopSubscribe'
        );
    }
    function stopSubscribe(){
        if($this->checkParameter(['processId'])){
            $processId = intval($this->parameters['processId']);
            $result = posix_kill($processId,9);
            $this->output['status'] = $result?STATUS_OK: STATUS_SERVER_ERROR;
        }
    }
    function processRoleAction($item){
        CacheService::clear();
    }
}