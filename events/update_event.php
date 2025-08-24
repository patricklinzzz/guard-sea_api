<?php
// update_event.php

// 啟用所有錯誤報告
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") { 
        
        $mysqli->begin_transaction();

        $activity_id = isset($_POST['id']) ? intval($_POST['id']) : null;
        if (!$activity_id) {
            throw new Exception("活動ID不存在，無法更新。", 400);
        }

        // 查舊資料
        $result = $mysqli->query("SELECT category_id, image_url FROM activities WHERE activity_id = $activity_id");
        $old_category_id = null;
        $old_image_url = null;
        if ($result && $row = $result->fetch_assoc()) {
            $old_category_id = intval($row['category_id']);
            $old_image_url = $row['image_url'];
        }

        // 如果前端有送就用新的，否則保留舊的
        $category_id = isset($_POST["category_id"]) && $_POST["category_id"] !== '' 
            ? intval($_POST["category_id"]) 
            : $old_category_id;
            
        // 查舊圖片
        $result = $mysqli->query("SELECT image_url FROM activities WHERE activity_id = $activity_id");

        $old_image_url = null;
        if ($result && $row = $result->fetch_assoc()) {
            $old_image_url = $row['image_url'];
        }

        // 呼叫新的上傳函式
        // handle_cover_image_upload 會回傳 null 如果沒有新圖片上傳
        $new_image_url = handle_cover_image_upload('image_file', 'event/', 'event_');

        $image_url_for_db = $old_image_url;
        if ($new_image_url !== null) {
            // 如果有新圖片上傳，則使用新圖片的路徑
            $image_url_for_db = $new_image_url;
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == 'true') {
            $image_url_for_db = null;
        }
        
        $location_from_post = mysqli_real_escape_string($mysqli, $_POST["location"] ?? '');
        $address_from_post = mysqli_real_escape_string($mysqli, $_POST["address"] ?? '');
        $location_final = trim($location_from_post . ' ' . $address_from_post);

        $title = mysqli_real_escape_string($mysqli, $_POST["title"] ?? '');
        $preface = mysqli_real_escape_string($mysqli, $_POST["preface"] ?? '');
        $description = mysqli_real_escape_string($mysqli, $_POST["description"] ?? '');
        $start_date = mysqli_real_escape_string($mysqli, $_POST["start_date"] ?? '');
        $end_date = mysqli_real_escape_string($mysqli, $_POST["end_date"] ?? '');
        $quota = intval($_POST["quota"] ?? 0);
        $registration_close_date = mysqli_real_escape_string($mysqli, $_POST["registration_close_date"] ?? '');
        $status = mysqli_real_escape_string($mysqli, $_POST["status"] ?? '');
        $notes = mysqli_real_escape_string($mysqli, $_POST["notes"] ?? '');
        $category_id = intval($_POST["category_id"] ?? 0);
        $presenter = mysqli_real_escape_string($mysqli, $_POST["presenter"] ?? '');

        $sql_parts = [
            "title = '$title'",
            "preface = '$preface'",
            "description = '$description'",
            "start_date = '$start_date'",
            "end_date = '$end_date'",
            "location = '$location_final'",
            "quota = $quota",
            "registration_close_date = '$registration_close_date'",
            "status = '$status'",
            "notes = '$notes'",
            "category_id = $category_id",
            "image_url = '" . mysqli_real_escape_string($mysqli, $image_url_for_db ?? '') . "'",
            "presenter = '$presenter'"
        ];

        $sql = "UPDATE activities SET " . implode(", ", $sql_parts) . " WHERE activity_id = $activity_id";

        if ($mysqli->query($sql)) {
            $mysqli->commit();
            echo json_encode([
                "status" => "success",
                "message" => "更新活動成功！",
                "image_url" => $image_url_for_db
            ]);
        } else {
            throw new Exception("資料庫更新失敗: " . $mysqli->error);
        }

    } else {
        throw new Exception("只允許 POST 請求", 405);
    }

} catch (Exception $e) {
    if (isset($mysqli) && $mysqli->thread_id) {
        $mysqli->rollback();
    }
    
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);

    echo json_encode([
        "status" => "error",
        "message" => "更新失敗: " . $e->getMessage()
    ]);

} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && $mysqli->ping()) {
        $mysqli->close();
    }
}
?>