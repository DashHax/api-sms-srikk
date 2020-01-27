<?php
class AdminPaymentsController implements Controller {
    public function GetRoutingTable()
    {
        return [
            '/admin/payments/types' => 'PaymentTypes',
            '/admin/payments/types/new' => 'PaymentTypes_New',
            '/admin/payments/types/edit' => 'PaymentTypes_Edit',
            '/admin/payments/types/delete' => 'PaymentTypes_Remove',
            '/admin/payments/bills/trx' => 'Invoices_List',
            '/admin/payments/bills/bill' => 'Invoices_List',
            '/admin/payments/bills/new' => 'Invoices_New',
            '/admin/payments/bills/delete' => 'Invoices_Delete',
        ];
    }

    public function PaymentTypes() {
        if (ispost()) {
            cors();

            try {
                $payments_results = DB::query("SELECT * FROM payment_types");

                if ($payments_results) {
                    Flight::json(['status'=>'success', 'result'=>$payments_results]);
                } else {
                    Flight::json(['status'=>'error', 'message' => 'Unknown error']);
                }
            } catch (Exception $ex) {
                Flight::json(['status' => 'error', 'message' => $ex->getMessage()]);
            }
        }
    }

    public function PaymentTypes_New() {
        if (ispost()) {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            try {
                $new_result = PaymentManager::AddPaymentType($data['name'], $data['desc'], $data['unitprice'], $data['limit']);

                if ($new_result) {
                    Flight::json(['status'=>'success', 'result'=>$new_result]);
                } else {
                    Flight::json(['status'=>'error', 'message'=>PaymentManager::$error->getMessage()]);
                }
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function PaymentTypes_Edit() {
        if (ispost()) {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            try {
                $edit_result = PaymentManager::EditPaymentType($data['id'], $data['name'], $data['desc'], $data['unitprice'], $data['limit']);

                if ($edit_result) {
                    Flight::json(['status' => 'success']);
                } else {
                    Flight::json(['status' => 'error', 'message' => PaymentManager::$error->getMessage()]);
                }
            } catch (Exception $ex) {
                Flight::json(['status' => 'error', 'message' => $ex->getMessage()]);
            }
        }
    }

    public function PaymentTypes_Remove() {
        if (ispost()) {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);
            $multiple = (isset($data) && isset($data['multiple'])) ? $data['multiple'] : false;

            try {
                if (!$multiple) {
                    $delete_result = PaymentManager::DeletePaymentType($data['id']);

                    if ($delete_result) {
                        Flight::json(['status' => 'success']);
                    } else {
                        Flight::json(['status' => 'error', 'message' => PaymentManager::$error->getMessage()]);
                    }
                } else {
                    $delete_result = PaymentManager::DeleteMultiplePaymentType($data['ids']);

                    if ($delete_result) {
                        Flight::json(['status' => 'success']);
                    } else {
                        Flight::json(['status' => 'error', 'message' => PaymentManager::$error->getMessage()]);
                    }
                }
            } catch (Exception $ex) {
                Flight::json(['status' => 'error', 'message' => $ex->getMessage()]);
            }
        }
    }

    public function Invoices_List() {
        if (ispost()) {
            cors();

            try {
                $req = Flight::request();
                $body = $req->data;
                $istrx = strpos($req->url, "trx") !== false;

                $data = null;
                $search_arg = null;

                if (isset($body['request'])) {
                    $data = json_decode($body['request'], true);

                    if (isset($data['search']) && $data['search'] == true) {
                        $search_arg = $data['term'];
                    }
                }

                $list = PaymentManager::GetAllTransactionsAdmin($istrx, null, null, $search_arg);

                if (is_array($list)) {
                    Flight::json(['status'=>'success', 'result'=>$list]);
                } else {
                    Flight::json(['status'=>'error', 'message'=>'Unable to obtain all transactions!']);
                }
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function Invoices_New() {
        if (ispost()) {
            cors();

            try {
                $body = Flight::request()->data;
                $data = json_decode($body['request'], true);

                $orderid = $data['orderID'];
                $paymentid = $data['payment'];
                $msg = $data['msg'];
                $userid = $data['user'];
                $targets = $data['targets'];

                $result = PaymentManager::CreatePaymentBill($orderid, $msg, $userid, $paymentid, $targets);

                if ($result == true) {
                    $new_bill = PaymentManager::GetTransaction($orderid);
                    Flight::json(['status'=>'success', 'result'=>$new_bill]);
                } else {
                    throw PaymentManager::$error;
                }
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function Invoices_Delete() {
        if (ispost()) {
            cors();

            try {
                $body = Flight::request()->data;
                $data = json_decode($body['request'], true);

                $orderIds = $data['orders'];

                $delete_result = PaymentManager::DeleteTransactions($orderIds);

                if ($delete_result == false) throw PaymentManager::$error;

                Flight::json(['status'=>'success']);
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }
}

?>