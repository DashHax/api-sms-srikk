<?php
class Crypto {
    private static $SALT = "srikk_sms_salt";
    private static $PASS = "srikk_sms_crypto_password";

    public static function Hash($value) {
        return hash_hmac("sha512", $value, Crypto::$SALT);
    }

    public static function GenerateID() {
        return bin2hex(random_bytes(10));
    }

    public static function Encrypt($plain_text) {
        $_key = hash('sha256', Crypto::$PASS, true);
        $_iv = openssl_random_pseudo_bytes(16);

        $enc =  openssl_encrypt($plain_text, "AES-256-CBC", $_key, OPENSSL_RAW_DATA, $_iv);
        $hash = hash_hmac('sha256', $enc . $_iv, $_key, true);

        return bin2hex($_iv . $hash . $enc);
    }

    public static function Decrypt($encrypted_text) {
        $encrypted_text = hex2bin($encrypted_text);
        $_iv = substr($encrypted_text, 0, 16);
        $_hash = substr($encrypted_text, 16, 32);
        $_enc = substr($encrypted_text, 48);

        $_key = hash('sha256', Crypto::$PASS, true);

        if (!hash_equals(hash_hmac('sha256', $_enc . $_iv, $_key, true), $_hash)) return false;

        return openssl_decrypt($_enc, 'AES-256-CBC', $_key, OPENSSL_RAW_DATA, $_iv);
    }
}

/**
function encrypt($plaintext, $password) {
    $method = "AES-256-CBC";
    $key = hash('sha256', $password, true);
    $iv = openssl_random_pseudo_bytes(16);

    $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
    $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

    return $iv . $hash . $ciphertext;
}

function decrypt($ivHashCiphertext, $password) {
    $method = "AES-256-CBC";
    $iv = substr($ivHashCiphertext, 0, 16);
    $hash = substr($ivHashCiphertext, 16, 32);
    $ciphertext = substr($ivHashCiphertext, 48);
    $key = hash('sha256', $password, true);

    if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;

    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}
 */

?>