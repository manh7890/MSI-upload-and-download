<?php
namespace Library;
class Router{
    public $method = '';
    public $uri = '';
    public $controller = '';
    public $action = '';
    public $parameters = [];
    public $permissionObjectIdentifier=false;
    public $permissionAction=false;
    public $onlyBA=false;
    public function __construct($method,$uri,$controller,$action='',$parameters=[],$permissionObjectIdentifier=false,$permissionAction=false,$onlyBA=false){
        $this->method                       = $method;
        $this->uri                          = $uri;
        $this->controller                   = $controller;
        $this->action                       = $action;
        $this->parameters                   = $parameters;
        $this->permissionObjectIdentifier   = $permissionObjectIdentifier;
        $this->permissionAction             = $permissionAction;
        $this->onlyBA                       = $onlyBA;
    }
    public function run(){
        if($this->method=='redirect'){
            $this->redirect();
        }
        else{
            if(class_exists('\\Controller\\'.$this->controller)){
                $controllerClass='\\Controller\\'.$this->controller;
                $controllerObject = new $controllerClass();
                $controllerObject->currentAction = $this->action;
                $extendParameters = $this->getExtendParameters();
                $parameters = array_merge($this->parameters,$extendParameters);
                if($this->onlyBA && Auth::isBa()==false){
                    Redirect::redirect403();
                }
                if($this->permissionObjectIdentifier!=false&&$this->permissionAction!=false){
                    $this->permissionObjectIdentifier = Str::bindDataToString($this->permissionObjectIdentifier,$parameters);
                    AccessControl::filterByPermission($this->permissionObjectIdentifier,$this->permissionAction);

                }

                $controllerObject->parameters = $parameters;
                $controllerObject->run();
            
                
            }
            else{
                Redirect::redirect404();
            }
        }
        
    }
    
    public  function redirect(){
        Redirect::redirect($this->controller);
    }
    public function getExtendParameters(){
        $parameters = $this->getPhpInputParameters();
        if($this->method=='get' && count($_GET)>0){
            $parameters = array_merge($parameters,$_GET);
        }
        return $parameters;
    }
    private  function getPhpInputParameters(){
        $inputContent = file_get_contents("php://input");
        $parameters = json_decode($inputContent,true);
        if(!is_array($parameters)){
            parse_str($inputContent,$parameters);   
        }
        if(is_array($parameters)){
            return $parameters;
        }
        else{
            return [];
        } 
    }
    
    
   

}