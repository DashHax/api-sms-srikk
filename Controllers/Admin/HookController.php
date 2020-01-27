<?php
class HookController implements Controller {
    /**
     * @var Settings
     */
    private $settings;

    public function __construct($sett)
    {
        $this->settings = $sett;
    }
   
    public function GetRoutingTable() {
        return [
            '/hook/return_payment/*' => 'Return_Payment',
            '/hook/callback_payment' => 'Callback_Payment'
        ];
    }

    function Return_Payment() {
        $request = Flight::request();
        $query = Flight::request()->query;

        if (!isset($query['status_id']) || !isset($query['order_id']) || !isset($query['transaction_id']) || !isset($query['msg']) || !isset($query['hash'])) {
            Flight::render("hooks/invalid_payment.php", ['logo'=>srikklogo()]);
            Flight::stop();
            return;
        }

        $secret = $this->settings->getCart("secret");
        $hash = md5($secret . $query['status_id'] . $query['order_id'] . $query['transaction_id'] . $query['msg']);
        
        if ($hash != $query['hash']) {
            Flight::render("hooks/invalid_payment.php", ['logo' => srikklogo()]);
            Flight::stop();
            return;
        }

        $msg = "Bank response: " . $query['msg'];
        $msg = str_replace("_", " ", $msg);
        $msg = rawurlencode($msg);

        $trx_result = PaymentManager::ProcessTransaction($query['status_id'], $query['transaction_id'], $query['order_id'], $msg);

        if ($trx_result == false) {
            Flight::render("hooks/invalid_payment.php", ['logo' => srikklogo(), 'exception' => PaymentManager::$error->getMessage()]);
            Flight::stop();
            return;
        }

        $devmode = strpos($request->host, "ngrok") !== false;
        $redirectUrl = $devmode ? "localhost:5000" : $this->settings->get("sys", "frontend");

        $redirectUrl .= "#/dashboard/payment_result/" . $trx_result['status'] . "/" . $query['order_id'];
        if ($trx_result['status'] == "failed") {
            $redirectUrl .= "/" . $msg;
        }

        Flight::redirect("http://" . $redirectUrl);
    }

    public function Callback_Payment() {
        
        $raw = Flight::request()->getBody();
        $data = explode("&", $raw);
        $args = [];

        foreach ($data as $arg) {
            $parts = explode("=", $arg);
            $arg[$parts[0]] = $parts[1];
        }

        file_put_contents(__DIR__ . "/output.txt", $data);
        echo "OK";
    }
}

?>