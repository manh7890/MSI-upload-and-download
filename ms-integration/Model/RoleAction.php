<?php
namespace Model;

use Library\MessageBus;
use SqlObject;
class RoleAction extends SqlObject{
    
   
    public $id,
        $objectIdentifier,
        $action,
        $objectType,
        $name,
        $roleIdentifier,
        $status;
    public static $mappingFromDatabase = [
        // 'id'                =>  [ 'name' => 'id',                   'type' =>'number',  'primary'=>true, 'auto_increment' => true],
        'objectIdentifier'  =>  [ 'name' => 'object_identifier',    'type' => 'string'],
        'action'            =>  [ 'name' => 'action',               'type' => 'string'],
        'objectType'        =>  [ 'name' => 'object_type',          'type' => 'string'],
        'name'              =>  [ 'name' => 'name',                 'type' => 'string'],
        'roleIdentifier'    =>  [ 'name' => 'role_identifier',      'type' => 'string']
        // 'status'            =>  [ 'name' => 'status',               'type' => 'number']
    ];
    public function __construct($data=[]){
        parent::__construct($data);
    }
    public static function getTableName(){
        return 'role_action';
    }
    public static function getTopicName(){
       return 'role_action';
    }
    public static function refresh(){
        Connection::exeQuery("REFRESH MATERIALIZED VIEW CONCURRENTLY ".self::getTableName());
        MessageBus::publish("role_action","update","has update");
    }
    public static function createView(){
        $createViewQuery = "
        CREATE MATERIALIZED VIEW ".self::getTableName()." AS
        SELECT 
            operation.object_identifier as object_identifier,
            operation.action as action,
            operation.object_type as object_type,
            operation.name as name,
            operation.status as status,
            permission_role.role_identifier as role_identifier
        FROM
            operation,
            operation_in_action_pack,
            action_in_permission_pack,
            permission_role
        WHERE
            operation.id=operation_in_action_pack.operation_id
            AND operation_in_action_pack.action_pack_id = action_in_permission_pack.action_pack_id
            AND action_in_permission_pack.permission_pack_id=permission_role.permission_pack_id
        ";
    }
   
}