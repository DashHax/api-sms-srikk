<?php
class AdminNewsController implements Controller {
    public function __construct()
    {
        NewsManager::Init();
    }

    public function GetRoutingTable() {
        return [
            '/admin/news/list' => 'News_List',
            '/admin/news/create' => 'News_Create',
            '/admin/news/edit' => 'News_Edit',
            '/admin/news/delete' => 'News_Delete'
        ];
    }

    public function News_List()
    {
        if (ispost()) {
            cors();
            $body = Flight::request()->data;
            try {
                
                $news = NewsManager::List($body['token']);

                if ($news !== false) {
                    Flight::json(['status' => 'success', 'result' => $news]);
                } else {
                    Flight::json(['status'=>'error', 'message'=>NewsManager::$error->getMessage()]);
                }

            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

    public function News_Create() {
        if (ispost()) {
            cors();
            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            try {
                $token = $body['token'];
                $title = $data['title'];
                $content = $data['content'];

                $create_result = NewsManager::Create($token, $title, $content);

                if ($create_result !== false) {
                    Flight::json(['status'=>'success', 'result'=>$create_result]);
                } else {
                    Flight::json(['status'=>'error', 'message'=>NewsManager::$error->getMessage()]);
                }

            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'messaga'=>$ex->getMessage()]);
            }
        }
    }

    public function News_Edit() {
        if (ispost()) {
            cors();
            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            try {
                $token = $body['token'];
                $id = $data['id'];
                $title = $data['title'];
                $content = $data['content'];

                $edit_result = NewsManager::Edit($token, $id, $title, $content);

                if ($edit_result !== false) {
                    Flight::json(['status' => 'success']);
                } else {
                    Flight::json(['status' => 'error', 'message' => NewsManager::$error->getMessage()]);
                }
            } catch (Exception $ex) {
                Flight::json(['status' => 'error', 'message' => $ex->getMessage()]);
            }
        }
    }

    public function News_Delete() {
        if (ispost()) {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            try {
                $id = $data['id'];

                $del_result = NewsManager::DeleteOne($id);

                if ($del_result !== false) {
                    Flight::json(['status'=>'success']);
                } else {
                    Flight::json(['status'=>'error', 'message'=>NewsManager::$error->getMessage()]);
                }
            } catch (Exception $ex) {
                Flight::json(['status'=>'error', 'message'=>$ex->getMessage()]);
            }
        }
    }

}
?>