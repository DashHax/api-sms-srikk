<?php
class StudentManager {
    public static $error = null;

    public static function NewStudent($name, $owner, $edit = false, $id = null) {
        $ownerId = null;
        $ownerName = null;
        $ownerType = "none";

        try {
            if ($owner !== "none") {
                $search_query = DB::query("SELECT id, fullname FROM users WHERE username = %s AND usertype != 'admin'", $owner);
                if (count($search_query) > 0) {
                    $ownerId = $search_query[0]['id'];
                    $ownerName = $search_query[0]['fullname'];
                    $ownerType = "parential";
                } else {
                    $ownerId = $owner;
                    $ownerName = $owner;
                    $ownerType = "group";
                }
            }

            $student_id = Crypto::GenerateID();

            if ($edit == false) {
                DB::insert("child", [
                    'id' => $student_id,
                    'name' => $name,
                    'parentid' => $ownerId,
                    'ownership' => $ownerType,
                    'date' => DB::sqlEval("NOW()")
                ]);

                return [
                    'id' => $student_id,
                    'owner' => $ownerName,
                    'ownership' => $ownerType
                ];
            } else {
                DB::update("child", [
                    'name'=>$name,
                    'parentid'=>$ownerId,
                    'ownership'=>$ownerType
                ], "id = %s", $id);

                return [
                    'owner' => $ownerName,
                    'ownership' => $ownerType
                ];
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public static function DeleteStudent($id) {
        try {
            DB::delete("child", "id = %s", $id);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public static function SetOwnerStudent($ids, $owner)
    {
        $ownerId = null;
        $ownerName = null;
        $ownerType = "none";

        try {
            if ($owner !== "none") {
                $search_query = DB::query("SELECT id, fullname FROM users WHERE username = %s AND usertype != 'admin'", $owner);
                if (count($search_query) > 0) {
                    $ownerId = $search_query[0]['id'];
                    $ownerName = $search_query[0]['fullname'];
                    $ownerType = "parential";
                } else {
                    $ownerId = $owner;
                    $ownerName = $owner;
                    $ownerType = "group";
                }
            }

            $idCondition = new WhereClause("or");
            foreach ($ids as $key => $id) {
                $idCondition->add("id = %s", $id);
            }

            DB::update("child", ['parentid'=> $ownerId, 'ownership'=>$ownerType], '%l', $idCondition);

            return [
                'owner'=>$ownerName,
                'type'=>$ownerType
            ];
             
        } catch (Exception $ex) {
            StudentManager::$error = $ex;
            return false;
        }
    }
}

?>