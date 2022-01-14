<?php
namespace Controller;

use Library\MessageBus;
use Library\Auth;
use Library\CacheService;
use Library\Message;
use Library\Str;
use Model\ActionPack;
use Model\ObjectIdentifier;
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
        $listTopic = Operation::getListType();
        MessageBus::subscribeMultiTopic(
            $listTopic,
            'accesscontrol.symper.vn',
            function($topic,$item){
                $this->processObject($topic,$item);
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
    public function processObject($type,$item){
        if(isset($item['event'])&&($item['event']=="create"||$item['event']=="update" )&& isset($item['data']) && isset($item['data']['id'])){
            $object = new ObjectIdentifier();
            $object->type = $type;
            $object->objectIdentifier = $type.":".$item['data']['id'];
            if(isset($item['data']['name'])){
                $object->name = $item['data']['name'];
            }
            if(isset($item['data']['title'])){
                $object->title = $item['data']['title'];
            }
            if(isset($item['data']['type'])){
                $object->objectType = $item['data']['type'];
            }
            if(isset($item['data']['new']['name'])){
                $object->name = $item['data']['new']['name'];
            }
            if(isset($item['data']['new']['title'])){
                $object->name = $item['data']['new']['title'];
            }
            
            $object->save();
           
        }
        else if(isset($item['event'])&&$item['event']=="delete"&& isset($item['data']) && isset($item['data']['id'])){
            $object = new ObjectIdentifier();
            $object->objectIdentifier = $type.":".$item['data']['id'];
            $object->delete();
        }
        
    } 
}