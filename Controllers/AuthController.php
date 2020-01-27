<?php
class AuthController implements Controller {
    public function GetRoutingTable()
    {
        return [
            '/login' => 'Login',
            '/register' => 'Register',
            '/logout' => 'Logout'       
        ];
    }

    public function Login() {
        try {

            $data = Flight::request()->data;
            $user = $data['username'];
            $pass = Crypto::Hash($data['password']);

            cors();

            $check_result = DB::query("SELECT * FROM users WHERE username = %s AND password = %s", $user, $pass);
            if (count($check_result) == 0) {
                Flight::json(['status'=>'failed', 'error'=>'usernotfound']);
                return;
            }

            $user = $check_result[0];
            $_SESSION['loggedin'] = $user;
            
            $payload = [$user['id'], $user['fullname'], $user['usertype']];

            $token = TokenManager::Issue($payload);
            
            $response = ['status' => 'success', 'token' => $token];

            if ($user['usertype'] == "admin") {
                $response['auth'] = bin2hex(random_bytes(10));
            }

            Flight::json($response);
        } catch (Exception $ex) {
            var_dump($ex);
            Flight::json(['status'=>'failed', 'error'=>$ex->getMessage()]);
        }
    }

    public function Register()
    {
        try {
            $data = Flight::request()->data;
            $user = $data['username'];
            $pass = Crypto::Hash($data['password']);
            $name = $data['fullname'];
            $ref = $data['refcode'];

            cors();

            $check_result = DB::query("SELECT id FROM users WHERE username = %s", $user);
            if (count($check_result) > 0) {
                Flight::json(['status' => 'failed', 'error' => 'userexist']);
                return;
            }
            $userId = Crypto::GenerateID();
            DB::insert("users", [
                'id' => $userId,
                'username' => $user,
                'password' => $pass,
                'fullname' => $name
            ]);

            if (isset($ref)) {
                $condition = new WhereClause("and");
                $condition->add("parentid = %s", $ref);
                $condition->add("ownership = %s", "group");
                DB::update("child", ['parentid'=>$userId, 'ownership'=>'parential'], '%l', $condition);
            }

            Flight::json(['status'=>'success']);
        } catch (Exception $ex) {
            Flight::json(['status'=>'failed', 'error'=>$ex->getMessage()]);
        }
    }

    public function Logout() {
        cors();
        try {
            unset($_SESSION['loggedin']);
            session_destroy();
            Flight::json(['status' => 'success']);
        } catch (Exception $ex) {
            Flight::json(['status'=>'failed']);
        }
    }
}
?>