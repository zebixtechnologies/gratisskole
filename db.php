<?php

include $_Server['DOCUMENT_ROOT']. 'class/z_db_mysql.php';

$obj = new dbconn();


$dbName = 'denmark';
$user   = 'root';
$pass   = '';
$obj->connect('',$dbName,$user,$pass);