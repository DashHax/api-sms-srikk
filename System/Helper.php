<?php

function cors()
{
    header("Access-Control-Allow-Origin: *");
}

function ispost() {
    return Flight::request()->method == "POST";
}

function srikklogo() {
    return file_get_contents(__DIR__ . "/logo.base64");;
}

?>