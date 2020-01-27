<?php
class Settings {
    private $settings_pair = [
        'db'=>__DIR__ . "/database.json",
        'cart'=>__DIR__."/cart.json",
        'sys'=>__DIR__."/system.json"
    ];

    private $setts;

    public function __construct()
    {
        foreach ($this->settings_pair as $obj=>$file) {
            $this->setts[$obj] = json_decode(file_get_contents($file), true);
        }
    }

    public function getDB($key) {
        return $this->get("db", $key);
    }

    public function setDB($key, $value) {
        $this->set("db", $key, $value);
    }

    public function getCart($key) {
        return $this->get("cart", $key);
    }

    public function setCart($key, $value) {
        $this->set("cart", $key, $value);
    }

    public function get($body, $key)
    {
        if (isset($this->setts[$body])) {
            return $this->setts[$body][$key];
        } else {
            return null;
        }
    }

    public function set($body, $key, $value) {
        $this->setts[$body][$key] = $value;
    }
}

?>