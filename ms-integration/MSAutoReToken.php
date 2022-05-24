<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
$client = new Client([
    // Base URI được sử dụng với request
    'base_uri' => "http://localhost",
]);
$response = $client->request('Get', "/ms-integration/MSIRefreshToken" );