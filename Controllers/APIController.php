<?php
class APIController implements Controller {
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
            '/api/dashboard/info' => 'Dashboard_Info',
            '/api/news' => 'HandleNews',
            '/api/payments/list' => 'Payments_List',
            '/api/payments/get' => 'Payments_Retrieve',
            '/api/payments/request' => 'Payments_Request',
            '/api/students/new' => 'Students_New',

            '/api/helper/search_parents' => 'Helper_SearchParents',
            '/api/helper/get_children' => 'Helper_GetChildren'
        ];
    }

    public function Dashboard_Info() {
        if (ispost()) {
            cors();
            
            try {
                $body = Flight::request()->data;

                $id = TokenManager::GetPayload($body['token'])[0];

                $upstanding_payment = PaymentManager::GetTopmostTransaction($id);

                if (!$upstanding_payment) throw PaymentManager::$error;

                $result = [
                    'outstanding_payment' => $upstanding_payment
                ];

                Flight::json(['status'=>'success', 'result'=>$result]);

            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function HandleNews() {
        cors();
        if (Flight::request()->method == "POST") {
            $news_list = DB::query("SELECT news.id, news.title, news.content, news.date, news.edited, users.fullname AS author FROM news INNER JOIN users ON news.`by` = users.id ORDER BY date DESC");
            Flight::json(['status'=>'success', 'data'=>$news_list]);
        } else {
            Flight::notFound();
        }
    }

    public function Payments_List() {
        if (ispost()) {
            cors();

            try {
                $body = Flight::request()->data;
                $token = $body['token'];
                $id = TokenManager::GetPayload($token)[0];

                $result = PaymentManager::GetAllTransactions($id);
                
                if ($result == 'false') throw PaymentManager::$error;

                Flight::json(['status'=>'success', 'result'=>$result]);
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);                
            }
        }
    }

    public function Payments_Retrieve() {
        if (Flight::request()->method == "GET") {
            return;
        }

        cors();

        try {
            $token = Flight::request()->data['token'];
            $userid = TokenManager::GetPayload($token)[0];

            $payments = DB::query("SELECT `id` AS code, `name`, `unitprice` AS value FROM payment_types");
            
            $childrens = DB::query("SELECT id, `name` FROM child WHERE parentid=%s AND ownership='parential' ORDER BY `name`;", $userid); //$_SESSION['loggedin']['id']);

            $results = ['payments'=>$payments, 'childrens'=>$childrens];

            Flight::json(['status'=>'success', 'data'=>$results]); 
        } catch (Exception $ex) {
            Flight::json(['status'=>'failed', 'error'=>$ex->getMessage()]);
        }
    }

    public function Payments_Request() {
        $url = $this->settings->getCart("link");
        $key = $this->settings->getCart("key"); //"748155737732231";
        $secret = $this->settings->getCart("secret"); //"14370-277";
        cors();
        
        if (Flight::request()->method == "POST") {
            $data = Flight::request()->data;
            try {
                $token = $data['token'];
                $req = json_decode($data['request'], true);

                $existing_payment = isset($req['existing']) ? $req['existing'] : false;

                if (!$existing_payment) {
                    $fee = $req['fee'];
                    $target = $req['target'];

                    $payment_info = DB::query("SELECT * FROM payment_types WHERE id = %s", $fee);
                    if (count($payment_info) > 0) {
                        $payment = $payment_info[0];

                        if (intval($payment['limit']) == 0 || count($target) <= intval($payment['limit'])) {
                            $id = TokenManager::GetPayload($token)[0];

                            $done = PaymentManager::CreateTransaction($id, $payment, $target, $url, $key, $secret);

                            if ($done) {
                                Flight::json(['status' => 'success', 'result' => $done]);
                            } else {
                                Flight::json(['status' => 'error', 'message' => 'Pembayaran tidak dapat dilaksanakan! Sila laporkan kepada pembangun web. ' . PaymentManager::$error->getMessage()]);
                            }
                        } else {
                            Flight::json(['status' => 'error', 'message' => 'Pembayaran hanyalah boleh untuk sebanyak ' . $payment['limit'] . ' unit!']);
                        }
                    } else {
                        Flight::json(['status' => 'error', 'message' => "Invalid payments"]);
                    }
                } else {
                    $payment_id = $req['fee'];
                    
                    $search_payment = DB::query("SELECT
                                                    payments.id,
                                                    payments.userid,
                                                    payments.`status`,
                                                    payments.trx,
                                                    UNIX_TIMESTAMP(payments.date) AS unixdate,
                                                    payments.payment_type,
                                                    payments.`code`,
                                                    payments.msg,
                                                    payments.type,
                                                    payment_types.unitprice 
                                                FROM
                                                    payments
                                                    INNER JOIN payment_types ON payments.payment_type = payment_types.id WHERE payments.id = %s", $payment_id);

                    if (count($search_payment) > 0) {

                        $pay = $search_payment[0];
                        $amount = intval($pay['unitprice']) * intval($req['count']);

                        $detail = "Pembayaran semula bagi pembayaran ID " . $payment_id . " bertarikh " . date('d/m/Y h:i:s', intval($pay['unixdate']));

                        $html = PaymentManager::GenerateTransactionForm($secret, $detail, $amount, $payment_id);
                        $done = ['form' => $html, 'url' => $url . "/" . $key];
                        Flight::json(['status'=>'success', 'result'=>$done]);
                    } else {
                        Flight::json(['status'=>'error', 'message'=>'Invalid payments']);
                    }
                }
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->__toString()]);
            }
            
        } 
    }

    public function Students_New() {
        if (Flight::request()->method == "POST") {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

        }
    } 

    public function Helper_SearchParents() {
        if (ispost()) {
            cors();

            try {
                $body = Flight::request()->data;
                $req = json_decode($body['request'], true);
                $term = $req['term'];

                $search = DB::query("SELECT id, fullname FROM users WHERE ((username LIKE %ss) OR (fullname LIKE %ss)) AND usertype = 'users' ORDER BY fullname;", $term, $term);
                
                Flight::json(['status'=>'success', 'result'=>$search]);
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function Helper_GetChildren() {
        if (ispost()) {
            cors();

            try {
                $body = Flight::request()->data;
                $req = json_decode($body['request'], true);
                $pid = $req['parent'];

                $search = DB::query("SELECT id, name FROM child WHERE parentid = %s ORDER BY name", $pid);

                Flight::json(['status' => 'success', 'result' => $search]);
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }
}
?>