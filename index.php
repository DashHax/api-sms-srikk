<?php
require_once "vendor/autoload.php";
require_once "library/HTMLPurifier.auto.php";
require_once "flight/Flight.php";

require_once "System/meekrodb.php";
require_once "System/Settings.php";
require_once "System/Crypto.php";
require_once "System/TokenMgr.php";

include_once "System/Helper.php";

require_once "Services/StudentManager.php";
require_once "Services/NewsManager.php";
require_once "Services/PaymentManager.php";

require_once "Controllers/ControllerInterface.php";
require_once "Controllers/APIController.php";
require_once "Controllers/AuthController.php";

require_once "Controllers/Admin/AdminStudentController.php";
require_once "Controllers/Admin/AdminNewsController.php";
require_once "Controllers/Admin/AdminPaymentsController.php";
require_once "Controllers/Admin/HookController.php";

$sett = new Settings();
DB::$user = $sett->getDB("user");
DB::$password = $sett->getDB("pass");
DB::$host = $sett->getDB("host");
DB::$dbName = $sett->getDB("database");
DB::$throw_exception_on_error = true;

TokenManager::Start();

session_start();
date_default_timezone_set("Asia/Kuching");

$controllers = [
    new APIController($sett),
    new AuthController(),
    new AdminStudentController(),
    new AdminNewsController(),
    new AdminPaymentsController(),
    new HookController($sett)
];

foreach ($controllers as $controller) {
    $routes = $controller->GetRoutingTable();

    foreach ($routes as $url => $func) {

        Flight::route($url, [$controller, $func]);
    }
}

Flight::route("/", function ($e) {
    echo "Please visit the frontend page";
}, true);

Flight::start();
?>