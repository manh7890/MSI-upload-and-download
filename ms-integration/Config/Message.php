<?php
define('STATUS_OK',200);
define('STATUS_NOT_FOUND', 404);
define('STATUS_PERMISSION_DENIED', 403);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_SERVER_ERROR', 500);

define('STORE_STATUS', 'statusMessage');

 $GLOBALS[STORE_STATUS] = [
    STATUS_OK                   => 'OK',
    STATUS_NOT_FOUND            => 'Not found',
    STATUS_PERMISSION_DENIED    => 'Permission denied',
    STATUS_BAD_REQUEST          => 'Bad request',
    STATUS_SERVER_ERROR         => 'Server Error'
];
