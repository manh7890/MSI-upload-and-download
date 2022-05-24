<?php
namespace Controller;

use Library\MessageBus;
use Library\Auth;
use Library\CacheService;
use Library\Message;
use Library\Str;
use Model\ActionPack;
use Model\LogTime;
use Model\ObjectIdentifier;
use Model\Operation;
use Model\OperationInActionPack;
use Model\PermissionRole;
use Model\RoleAction;
use Model\Task;
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
        $listTopic = ['account','role'];
        MessageBus::subscribeMultiTopic(
            $listTopic,
            'sdocument.symper.vn',
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
    function processObject($type,$item){
        if($type=='account'){
            $this->processAccount($item);
        }
        else if($type=='role'){
            $this->processRole($item);
        }
        else//cac truong hop khac
        {

        }
    } 
    function processAccount($item){
        $obj =  new Users($item['data']);
        if(isset($item['event'])){
            if($item['event']=="create"){
                $obj->save();
            }
            else if($item['event']=='update'){
                $obj =  new Users($item['data']['new']);
                $obj->save();
                
            }
            else  if($item['event']=="delete"){
                $obj->delete();
            }
        }
    }
    function processRole($item){
        $obj =  new Role($item['data']);
        if(isset($item['event'])){
            if($item['event']=="create"){
                $obj->save();
            }
            else if($item['event']=='update'){
                $obj =  new Role($item['data']['new']);
                $obj->save();
                
            }
            else  if($item['event']=="delete"){
                $obj->delete();
            }
        }
    }
}