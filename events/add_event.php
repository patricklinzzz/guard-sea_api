<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    try {
        $data = $_POST;

        // 檢查必填欄位
        $required_fields = ['category_id', 'title', 'preface', 'description', 'presenter', 'start_date', 'end_date', 'location', 'quota', 'registration_close_date', 'status'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "資料不完整，缺少必填欄位: " . $field], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }

        // 處理圖片上傳
        $image_url = handle_cover_image_upload('image_file', 'activities/', 'act_');

        // 使用 mysqli_real_escape_string 來處理所有變數，以防止 SQL 注入
        $category_id = mysqli_real_escape_string($mysqli, $data['category_id']);
        $title = mysqli_real_escape_string($mysqli, $data['title']);
        $preface = mysqli_real_escape_string($mysqli, $data['preface']);
        $description = mysqli_real_escape_string($mysqli, $data['description']);
        $presenter = mysqli_real_escape_string($mysqli, $data['presenter']);
        $start_date = mysqli_real_escape_string($mysqli, $data['start_date']);
        $end_date = mysqli_real_escape_string($mysqli, $data['end_date']);
        $location = mysqli_real_escape_string($mysqli, $data['location']);
        $quota = mysqli_real_escape_string($mysqli, $data['quota']);
        $registration_close_date = mysqli_real_escape_string($mysqli, $data['registration_close_date']);
        $status = mysqli_real_escape_string($mysqli, $data['status']);

        // 可選欄位
        $map_url = isset($data['map_url']) ? mysqli_real_escape_string($mysqli, $data['map_url']) : '';
        $notes = isset($data['notes']) ? mysqli_real_escape_string($mysqli, $data['notes']) : '';
        $current_participants = 0; // 靜態值，不需要從前端傳入

        // SQL INSERT 語法 (使用字串拼接)
        $sql = "INSERT INTO activities (
            category_id, 
            title, 
            preface, 
            description, 
            presenter, 
            start_date, 
            end_date, 
            location, 
            quota, 
            current_participants, 
            image_url, 
            registration_close_date, 
            map_url, 
            status, 
            notes
        ) VALUES (
            '$category_id', 
            '$title', 
            '$preface', 
            '$description', 
            '$presenter', 
            '$start_date', 
            '$end_date', 
            '$location', 
            '$quota', 
            '$current_participants', 
            '$image_url', 
            '$registration_close_date', 
            '$map_url', 
            '$status', 
            '$notes'
        )";

        // 執行語法並檢查結果
        $result = $mysqli->query($sql);

        if ($result) {
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => '活動新增成功！', 'activity_id' => $mysqli->insert_id], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception("新增活動失敗: " . $mysqli->error);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "伺服器發生錯誤",
            "details" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } finally {
        if (isset($mysqli)) {
            $mysqli->close();
        }
    }
    exit();
}

http_response_code(403);
echo json_encode(["status" => "error", "message" => "拒絕存取"], JSON_UNESCAPED_UNICODE);
?>