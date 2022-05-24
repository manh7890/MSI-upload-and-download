<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Model\Users;

class Api extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'test';
        $this->requireLogin = false;
    }
    function login(){
        if(isset($this->parameters['email'])){
            $email = trim(addslashes(strip_tags($this->parameters['email'])));
            $listUser = Users::getByTop(1,"email='$email'");
            if(count($listUser)>0){
                $profile = $listUser[0]->getProfile();
                $this->output['profile'] = $profile;
                $this->output['access_token'] = Auth::getJwtToken($profile);
                
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status'] = STATUS_NOT_FOUND;
                $this->output['message'] = Message::getStatusResponse(STATUS_NOT_FOUND);
            }
        }
        else{
            $this->output['status'] = STATUS_BAD_REQUEST;
            $this->output['message'] = Message::getStatusResponse(STATUS_BAD_REQUEST);
        }
    }   
    
    public function testGet(){
        $this->output['message'] = 'get';
        $this->output['parameter'] = $this->parameters;
        $this->output['token_check'] = Auth::getBearerToken();//$this->parameters;
        
    }
    public function testPut(){
        $this->output['message'] = 'put';
        $this->output['parameter'] = $this->parameters;
    }
    
    public function testDelete(){
        $this->output['message'] = 'delete';
        $this->output['parameter'] = $this->parameters;
    }
    
    public function testPatch(){
        $this->output['message'] = 'patch';
        $this->output['parameter'] = $this->parameters;
    }
    public function getDemoToken(){
        $this->output['status'] = STATUS_OK;
        $this->output['data'] = $this->parameters;
        $this->output['token'] = Auth::getJwtToken($this->parameters);
    }
    

}