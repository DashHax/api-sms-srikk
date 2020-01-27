<?php
//?status=[TXN_STATUS]&order=[ORDER_ID]&ref=[TXN_REF]&msg=[MSG]&h=[HASH]

$status = $_GET['status'];
$order = $_GET['order'];
$ref = $_GET['ref'];
$msg = $_GET['msg'];
$hash = $_GET['h'];


$checkstr = "2268-442" . urldecode($status);
$checkstr .= urldecode($order); 
$checkstr .= urldecode($ref);
$checkstr .= urldecode($msg);

$gen_hash = md5($checkstr);

echo $hash . "<br />";
echo $gen_hash . "<br />";
?>