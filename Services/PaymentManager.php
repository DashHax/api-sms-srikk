<?php
class PaymentManager {
    public static $error;

    public static function AddPaymentType($name, $desc, $unitprice, $limit) {
        try {
            $id = Crypto::GenerateID();

            DB::insert("payment_types", [
                'id'=>$id,
                'name'=>$name,
                'desc'=>$desc,
                'unitprice'=>$unitprice,
                'limit'=>$limit
            ]);

            return ['id'=>$id];

        } catch (Exception $th) {
            PaymentManager::$error = $th;
            return false;
        }
    }

    public static function EditPaymentType($id, $name, $desc, $unitprice, $limit)
    {
        try {
            $id = Crypto::GenerateID();

            DB::update("payment_types", [
                'name' => $name,
                'desc' => $desc,
                'unitprice' => $unitprice,
                'limit' => $limit
            ], 'id = %s', $id);

            return true;
        } catch (Exception $th) {
            PaymentManager::$error = $th;
            return false;
        }
    }

    public static function DeletePaymentType($id) {
        try
        {
            DB::delete("payment_types", "id = %s", $id);
            return true;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function DeleteMultiplePaymentType($ids) {
        try {
            $cond = new WhereClause("or");

            foreach ($ids as $id) {
                $cond->add("id = %s", $id);
            }

            DB::delete("payment_types", "%l", $cond);

            return true;

        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function CreateTransaction($userid, $paymentObj, $studentTargets, $url, $key, $secret) {
        $payment = $paymentObj;
        $target = $studentTargets;
        $html = "";

        $order_id = rand(100000000, 999999999);
        $amount = intval($payment['unitprice']) * count($target);

        $detail = "Pembayaran (" . $payment['desc'] . ") bagi " . count($target) . " pelajar [Tarikh: " . date('d/m/Y h:i:s') . ", ID: " . $order_id . "]";

        try {
            $code = Crypto::GenerateID() . Crypto::GenerateID();
            DB::insert("payments", [
                'id' => $order_id,
                'userid' => $userid,
                'status' => 'unpaid',
                'trx' => 'none',
                'date' => DB::sqlEval("NOW()"),
                'payment_type' => $payment['id'],
                'code' => $code,
                'msg' => 'Payment had been started! If you happen to see this message in the payment logs, that means you did not completed the payment step.'
            ]);

            $_target = [];
            
            foreach ($target as $student_id) {
                $_target[] = ['payment_id'=>$order_id, 'student_id' => $student_id];
            }

            DB::insert("payments_targets", $_target);
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }

        $html = PaymentManager::GenerateTransactionForm($secret, $detail, $amount, $order_id);

        return ['form' => $html, 'url' => $url . "/" . $key];
    }

    public static function GenerateTransactionForm($secret, $detail, $amount, $order_id)
    {
        $detail = str_replace(" ", "_", $detail);

        $hash = md5($secret . urldecode($detail) . urldecode(strval($amount)) . urldecode(strval($order_id)));
        $html = "";
        $html .= "<input type='hidden' name='detail' value='$detail'>";
        $html .= "<input type='hidden' name='amount' value='$amount'>";
        $html .= "<input type='hidden' name='order_id' value='$order_id'>";
        $html .= "<input type='hidden' name='hash' value='$hash'>";
        $html .= "<input type='hidden' name='name' value=''>";
        $html .= "<input type='hidden' name='email' value=''>";
        $html .= "<input type='hidden' name='phone' value=''>";

        return $html;
    }

    public static function ProcessTransaction($statusCode, $trxId, $orderId, $msg) {
        try
        {
            $dirtyTrx = false;
            $search = DB::query("SELECT * FROM payments WHERE id = %s", $orderId);
            if (count($search) == 0){
                throw new Exception("Invalid payment order ID!");
            }

            $status = $statusCode == 1 ? "success" : "failed";
            
            $changes = [
                'status' => (!$dirtyTrx ? $statusCode == 1 ? "paid" : "unpaid" : "error"),
                'trx' => $trxId,
                'msg' => $msg,
                'paiddate' => ($statusCode == 1 ? DB::sqlEval("NOW()") : null)
            ];

            if (!$dirtyTrx && $statusCode == 1) {
                $changes['type'] = 'trx';
            }

            DB::update("payments", $changes , "id = %s", $orderId);

            $result = ['status' => $status];

            return $result;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function GetTopmostTransaction($id) {
        try
        {
            $search_result = DB::query("SELECT id, status, unix_timestamp(date) * 1000 as date FROM payments WHERE userid = %s ORDER BY 'date' DESC LIMIT 1", $id);
            
            if (count($search_result) > 0) {
                $res = $search_result[0];
                return ['status'=>$res['status'], 'id'=>$res['id'], 'date'=>$res['date']];
            } else {
                return ['status'=>'nopayments'];
            }
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function GetAllTransactions($id) {
        try {
            $transactions = [];

            $payments = DB::query("SELECT payments.id, payments.`status`, payments.trx, UNIX_TIMESTAMP(payments.date) AS unixdate, payments.type, payment_types.id AS pay_id, payment_types.`name`, payment_types.unitprice, payment_types.`desc`, payments.msg FROM payments INNER JOIN payment_types ON payments.payment_type = payment_types.id WHERE payments.userid = %s ORDER BY payments.date DESC;", $id);

            foreach ($payments as $p) {
                $stds = DB::query("SELECT
                                        child.`name`" 
                                    . ($p['status'] !== "paid" ? ", child.id" : "") .    
                                    " 
                                    FROM
                                        payments_targets
                                        INNER JOIN child ON payments_targets.student_id = child.id 
                                    WHERE
                                        payments_targets.payment_id = %s", $p['id']);

                $trx = [
                    'id' => $p['id'],
                    'status' => $p['status'],
                    'trx' => $p['trx'],
                    'date' => $p['unixdate'],
                    'msg' => $p['msg'],
                    'type' => $p['type'],
                    'payment' => [
                        'id' => $p['pay_id'],
                        'name' => $p['name'],
                        'desc' => $p['desc'],
                        'unitprice' => $p['unitprice']
                    ],
                    'target' => $stds                    
                ];

                $transactions[] = $trx;
            }

            return $transactions;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return 'false';
        }
    }

    public static function GetAllTransactionsAdmin($istrx = true, $sort = null, $paging_arg = null, $search_arg = null) {
        try {
            $order_query = "";
            $search_query = "";

            if (!$sort || $sort == "date") {
                $order_query = "ORDER BY payments.date DESC";
            } else if ($sort == "paiddate") {
                $order_query = "ORDER BY payments.paiddate DESC";
            }

            if ($search_arg) {
                $search_query = " AND ((payments.id LIKE %ss) OR (users.fullname LIKE %ss)) ";
            }

            $query = "SELECT
                        payments.id,
                        payments.userid,
                        payments.`status`,
                        payments.trx,
                        UNIX_TIMESTAMP(payments.date) * 1000 AS date,
                        payments.`code`,
                        payments.msg,
                        payments.type,
                        UNIX_TIMESTAMP(payments.paiddate) * 1000 AS paiddate,
                        payment_types.`name`,
                        payment_types.unitprice,
                        payment_types.`desc`,
                        users.fullname,
                        COUNT( payments_targets.student_id ) AS totaltargets,
                        ( COUNT( payments_targets.student_id ) * payment_types.unitprice ) AS totalpayment,
                        GROUP_CONCAT(child.name SEPARATOR '*') AS targets
                    FROM
                        payments
                        INNER JOIN payment_types ON payments.payment_type = payment_types.id
                        INNER JOIN users ON payments.userid = users.id
                        INNER JOIN payments_targets ON payments.id = payments_targets.payment_id
                        INNER JOIN child ON payments_targets.student_id = child.id
                    WHERE
                        payments.type = %s " . $search_query . "
                    GROUP BY
                        payments.id
                     " . $order_query . ";";

            $trx_results = DB::query($query, $istrx ? "trx" : "bill", ($search_arg ? $search_arg : null), ($search_arg ? $search_arg : null));

            return $trx_results;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function GetTransaction($orderId) {
        try {
            $query = "SELECT
                        payments.id,
                        payments.userid,
                        payments.`status`,
                        payments.trx,
                        UNIX_TIMESTAMP(payments.date) * 1000 AS date,
                        payments.`code`,
                        payments.msg,
                        payments.type,
                        UNIX_TIMESTAMP(payments.paiddate) * 1000 AS paiddate,
                        payment_types.`name`,
                        payment_types.unitprice,
                        payment_types.`desc`,
                        users.fullname,
                        COUNT( payments_targets.student_id ) AS totaltargets,
                        ( COUNT( payments_targets.student_id ) * payment_types.unitprice ) AS totalpayment,
                        GROUP_CONCAT(child.name SEPARATOR '*') AS targets 
                    FROM
                        payments
                        INNER JOIN payment_types ON payments.payment_type = payment_types.id
                        INNER JOIN users ON payments.userid = users.id
                        INNER JOIN payments_targets ON payments.id = payments_targets.payment_id
                        INNER JOIN child ON payments_targets.student_id = child.id
                    WHERE 
                        payments.id = %s
                    GROUP BY
                        payments.id;";

            $trx_results = DB::query($query, $orderId);

            return $trx_results;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function CreatePaymentBill($orderid, $msg, $userid, $paymentId, $targets) {
        try {
            DB::insert("payments",
            [
                'id' => $orderid,
                'userid' => $userid,
                'status' => 'unpaid',
                'trx' => 'none',
                'date' => DB::sqlEval("NOW()"),
                'payment_type' => $paymentId,
                'code' => Crypto::GenerateID() . Crypto::GenerateID(),
                'msg' => $msg,
                'type' => 'bill'
            ]);

            $_target = [];

            foreach ($targets as $student_id) {
                $_target[] = ['payment_id' => $orderid, 'student_id' => $student_id];
            }

            DB::insert("payments_targets", $_target);

            return true;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }

    public static function DeleteTransactions($transaction_ids) {
        try {
            $payments_cond = new WhereClause("or");
            $targets_cond = new WhereClause("or");

            foreach ($transaction_ids as $trx) {
                $payments_cond->add("id = %s", $trx);
                $targets_cond->add("payment_id = %s", $trx);
            }

            DB::delete("payments", "%l", $payments_cond);
            DB::delete("payments_targets", "%l", $targets_cond);

            return true;
        } catch (Exception $ex) {
            PaymentManager::$error = $ex;
            return false;
        }
    }
}
?>