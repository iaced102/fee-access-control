<?php

use Controller\ActionPackService;
use Library\Database;
use Library\MessageBus;
use Model\ActionPack;
use Model\ObjectIdentifier;
use Model\Objects;

define("DIR", dirname(__FILE__));
require 'Config/Init.php';
$type='document_definition';
$item = json_decode('{"event":"update","data":{"id":"1953","name":"kaklkhaond","title":"kllk h\u00f3adsad","note":"","version":2,"parent_id":0,"is_editting":0,"create_at":"2020-09-17 00:00:00","update_at":"2020-09-21 16:15:25","fields":[{"name":"bcd","type":"text"},{"name":"a","type":"text"}]},"time":1600679725.731644}',true);
$object = new ObjectIdentifier();
$object->type = $type;
$object->objectIdentifier = $type.":".$item['data']['id'];
if(isset($item['data']['name'])){
    $object->name = $item['data']['name'];
}
if(isset($item['data']['title'])){
    $object->title = $item['data']['title'];
}
$object->save();