<?php
class TokenManager {

    private static $hash_folder;
    private static $hash_duration;
    private static $hash_lastCleared;

    public static function Start() {
        TokenManager::$hash_lastCleared = time();
        TokenManager::$hash_folder = __DIR__ . "/../hash";
        TokenManager::$hash_duration = 1 * 60 * 60;
    }

    public static function Issue($payload = null) {
        if (time() - TokenManager::$hash_lastCleared >= TokenManager::$hash_duration) {
            TokenManager::_beginClearHash();
            TokenManager::$hash_lastCleared = time();
        }
        $hash = bin2hex(random_bytes(10));

        $token = [
            'hash'=>$hash,
            'time'=>time(),
        ];

        if (isset($payload)) {
            $token['p'] = json_encode($payload);
        }

        $token = json_encode($token);
        $enc_token = (Crypto::Encrypt($token));

        return $enc_token;
    }

    public static function Validate($token) {
        $token = Crypto::Decrypt($token);
        $token = json_decode($token, true);

        if (time() - $token['time'] >= TokenManager::$hash_duration) {
            return ['valid'=>false,'reason'=>'expired'];
        };

        $hash_file = TokenManager::$hash_folder . "/" . $token['hash'] . ".hash";
        if (!file_exists($hash_file)) {
            return ['valid'=>false,'reason'=>'used'];
        }

        file_put_contents($hash_file, '\0');
        return ['valid'=>true];
    }

    public static function GetPayload($token) {
        if (TokenManager::Validate($token)) {
            $token = Crypto::Decrypt($token);
            $token = json_decode($token, true);

            if (isset($token['p']))
                return json_decode($token['p']);
            else
                return null;
        }
        return false;
    }

    private static function _beginClearHash() {
        $hashes = glob(TokenManager::$hash_folder . "/*.hash");
        foreach ($hashes as $hash) {
            unlink($hash);
        }
    }
}
?>