<?php
class AdminStudentController implements Controller {    
    public function GetRoutingTable() {
        return [
            '/admin/students/list' => 'List_Students',
            '/admin/students/new' => 'New_Students',
            '/admin/students/delete' => 'Delete_Students',
            '/admin/students/edit' => 'Edit_Students',
            '/admin/students/ownership' => 'Owns_Students'
        ];
    }

    public function List_Students() {
        if (Flight::request()->method == "POST") {
            cors();
            $body = Flight::request()->data;
            $hasData = isset($body['request']);
            $data = $hasData ? json_decode($body['request'], true) : null;
            $searchTerm = $hasData && isset($data['searchTerm']) ? $data['searchTerm'] : null;

            $student_list = [];

            $students = !$hasData ? DB::query("SELECT * FROM child ORDER BY date DESC") : DB::query("SELECT * FROM child WHERE name LIKE %ss ORDER BY date DESC", $searchTerm);
            foreach ($students as $key => $value) {
                $owner_name = DB::queryOneField('fullname', "SELECT * FROM users WHERE id = %s", $value['parentid']);
                $owner_name = isset($owner_name) ? $owner_name : $value['parentid'];
                
                $student_list[] = [
                    'id'=>$value['id'],
                    'name'=>$value['name'],
                    'parent'=>$owner_name,
                    'ownership'=>$value['ownership']
                ];
            }

            Flight::json(['status'=>'success', 'result'=>$student_list]);
        }
    }

    public function New_Students() {
        if (Flight::request()->method == "POST") {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            $save_result = StudentManager::NewStudent($data['name'], $data['owner']);

            if ($save_result) {
                Flight::json(['status'=>'success', 'result'=>$save_result]);
            } else {
                Flight::json(['status'=>'error']);
            }
        }
    }

    public function Delete_Students() {
        if (Flight::request()->method == "POST") {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            $multiple = isset($data['multiple']) ? $data['multiple'] : false;

            if (!$multiple) {
                $delete_result = StudentManager::DeleteStudent($data['target']);

                if ($delete_result) {
                    Flight::json(['status'=>'success']);
                } else {
                    Flight::json(['status'=>'error']);
                }
            } else {
                $ids = $data['target'];
                $error = 0;

                foreach ($ids as $key => $id) {
                    if (!StudentManager::DeleteStudent($id)) {
                        $error += 1;
                    }    
                }

                if ($error == 0) {
                    Flight::json(['status'=>'success']);
                } else {
                    Flight::json(['status'=>'error']);
                }
            }
        }
    }

    public function Edit_Students() {
        if (Flight::request()->method == "POST") {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);

            $edit_result = StudentManager::NewStudent($data['name'], $data['owner'], true, $data['id']);

            if ($edit_result) {
                Flight::json(['status' => 'success', 'result' => $edit_result]);
            } else {
                Flight::json(['status' => 'error']);
            }
        }
    }

    public function Owns_Students() {
        if (Flight::request()->method == "POST") {
            cors();

            $body = Flight::request()->data;
            $data = json_decode($body['request'], true);
            $ownage_result = StudentManager::SetOwnerStudent($data['targets'], $data['owner']);

            if ($ownage_result) {
                Flight::json(['status'=>'success', 'result'=>$ownage_result]);
            } else {
                Flight::json(['status'=>'error', 'error'=>StudentManager::$error->getMessage()]);
            }
        }
    }
}
?>