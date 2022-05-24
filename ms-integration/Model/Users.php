<?php
namespace Model;

use Library\Auth;
use Library\Database;
use Library\Environment;
use Library\Request;
use SqlObject;
class Users extends SqlObject
{
    public 
        $id,
        $firstName,
        $lastName,
        $userName,
        $displayName,
        $email,
        $phone,
        $status = 1,
        $avatar,
        $createAt,
        $updateAt;
    public static $mappingFromDatabase = [
        'id'            =>  [ 'name' => 'id',              'type' => 'number','primary'=>true, 'auto_increment' => true],
        'firstName'     =>  [ 'name' => 'first_name',      'type' => 'string'],
        'lastName'      =>  [ 'name' => 'last_name',       'type' => 'string'],
        'userName'      =>  [ 'name' => 'user_name',       'type' => 'string'],
        'displayName'   =>  [ 'name' => 'display_name',    'type' => 'string'],
        'email'         =>  [ 'name' => 'email',           'type' => 'string'], 
        'phone'         =>  [ 'name' => 'phone',           'type' => 'string'],
        'status'        =>  [ 'name' => 'status',          'type' => 'number'],
        'avatar'        =>  [ 'name' => 'avatar',          'type' => 'string'],
        'createAt'      =>  [ 'name' => 'create_at',       'type' => 'datetime'],
        'updateAt'      =>  [ 'name' => 'update_at',       'type' => 'datetime'],
    ];
    public function __construct($data=[]){
        parent::__construct($data,false);
    }
    public static function getTableName(){
        return 'users';
    }
   
    public function getProfile(){
        $profile = [
            'id'        =>  $this->id,
            'email'     =>  $this->email,
            'fullName'  => $this->displayName
        ];
        return $profile;
    }

    
    public static function createTableAndSyncUserData()
    {
        // tạo table
        $db = new Database();
        $db->createTable(new Users());
        Users::deleteMulti('1 = 1');

        // Đồng bộ dữ liệu từ service account sang
        $page = 1;
        $pageSize = 1000;
        $tk =  Auth::getBearerToken();
        $url = "https://".Environment::getPrefixEnvironment()."account.symper.vn/users?page=$page&pageSize=$pageSize";
        if(is_null($tk)){
            $token = "eyJhbGciOiJSUzI1NiIsInR5cGUiOiJKV1QifQ==.eyJpZCI6IjIiLCJuYW1lIjoiTmd1eVx1MWVjNW4gVmlcdTFlYzd0IERpbmgiLCJlbWFpbCI6ImRpbmhudkBzeW1wZXIudm4iLCJ1c2VyRGVsZWdhdGUiOnsiaWQiOiI4OTMiLCJmaXJzdE5hbWUiOiJQaFx1MDBmYWMiLCJsYXN0TmFtZSI6Ik5ndXlcdTFlYzVuIiwidXNlck5hbWUiOiJQaFx1MDBmYWMgVEdcdTAxMTAgTmd1eVx1MWVjNW4gVlx1MDEwM24iLCJkaXNwbGF5TmFtZSI6Ik5ndXlcdTFlYzVuIFZcdTAxMDNuIFBoXHUwMGZhYyIsImVtYWlsIjoicGh1Y252QGRla2tvLnZuIiwicGhvbmUiOiIiLCJzdGF0dXMiOiIxIiwiYXZhdGFyIjoiIiwidHlwZSI6InVzZXIifSwidHlwZSI6ImJhIn0=.MGE3ZjFkNDE1ZDhkZjYzNmIwMzM3MDQ1MmFhNTI4NDMwOWMwNjhjODQ2Y2IwMTdiMWQwMTI3OThkNmQwYmM1MTBmMDRlNjJlMDQ5YjcxYzY4MzljMTY1NDU1MjZhZDllMTJjMmNlZmVkOTRlZWIwNmQ1NmZiYTExOWYwM2VlYTY5MTI5YmVjYWRiN2Q3NTE4MmUyZThmYzA2ZjRkODNjZjIxNTNhZjUyOWJkOWVjZWRhMWE4ZDM0ZTk2OTQ5ZTBjNWQ0NjliNjUxMWE3YTIyZWQyMDkwMjdmNGI4Y2NlNGZkMWRkOTIxMzMyZGFmMTQ3NmE2Yzk1NTc1ZmZkZmUxMGE5ZmFmMzkyOTBkMjYyY2RlZDQzYTU2ZDhlZTUzYjZjOTNiMWJhNmI1MmY3NmY4ZDRmM2E3MmU4MGRlM2E0MGNhZTIxOWM5ZDRlZDA1MmQ5M2YyNWYwMzcyYjRlNWIxYjAzZTJkYWFjNTllNzM4ODU2MTBkNWI5ZjY1MGRjY2Y0NTczMDZlMjczYWM2OWUzMGFjNjM1YjAwNjM2MDAyMzU5MTFjZWQyMjUyMDQwYTExMjY2MThhM2E2ODNlZGQ4ODU3ODM4MTU0OTJlZGZlNDQ0NTE4ZGZmNjY1NTY5YmI3ZmFkM2MwOTVhNjViOGQzMjgzYWNmMjU1MmI2ZDYwYmY=";
        }
        $loadedRecordsCount = 0;
        $totalRecords = 1;
        while ($loadedRecordsCount < $totalRecords ) {
            $result = Request::request($url, false, 'GET', $token);
            $records = $result['data']['listObject'];
            if($page == 1){
                $totalRecords = intval($result['data']['total']);
            }
            $arr = [];
            foreach ($records as $r) {
                $arr[] = new Users($r);
            }
            Users::insertBulk($arr);
            $loadedRecordsCount += count($records);
            $page += 1;
        }
    }
}