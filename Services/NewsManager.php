<?php
class NewsManager {
    public static $error;
    public static $config;
    public static $purifier;

    public static function Init() {
        NewsManager::$config = HTMLPurifier_Config::createDefault();
        NewsManager::$purifier = new HTMLPurifier(NewsManager::$config);
    }

    public static function List($token) {
        try {
            $payload = TokenManager::GetPayload($token);
            $admin_id = $payload[0];

            $news_result = DB::query("SELECT id, title, content FROM news WHERE `by` = %s ORDER BY `date` DESC", $admin_id);

            return $news_result;
        } catch (Exception $ex) {
            NewsManager::$error = $ex;
            return false;
        }
    }

    public static function Create($token, $title, $content) {
        try {
            $admin_id = TokenManager::GetPayload($token)[0];
            $news_id = Crypto::GenerateID();

            DB::insert("news", [
                'id'=>$news_id,
                'title'=>$title,
                'content'=> NewsManager::$purifier->purify($content),
                'date' => DB::sqlEval("NOW()"),
                'by' => $admin_id
            ]);

            return ['id'=>$news_id];
        } catch (Exception $ex) {
            NewsManager::$error = $ex;
            return false;
        }
    }

    public static function Edit($token, $id, $title, $content) {
        try {
            $admin_id = TokenManager::GetPayload($token)[0];

            DB::update("news", [
                'title' => $title,
                'content' => NewsManager::$purifier->purify($content),
                'edited' => DB::sqlEval("NOW()"),
                'by' => $admin_id
            ], 'id = %s', $id);

            return true;

        } catch (Exception $ex) {
            NewsManager::$error = $ex;
            return false;
        }
    }

    public static function DeleteOne($id) {
        try {
            DB::delete("news", "id = %s", $id);
            
            return true;
        } catch (Exception $ex) {
            NewsManager::$error = $ex;
            return false;
        }
    }
}

?>