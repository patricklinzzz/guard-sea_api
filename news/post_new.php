<?php
// post.php (最終雲端安全版)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php"); 

header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- 後端欄位驗證 ---
        $errors = [];
        if (empty(trim($_POST['title']))) $errors[] = "消息標題為必填欄位。";
        if (empty($_POST['category_id'])) $errors[] = "必須選擇一個消息分類。";
        if (!isset($_POST['publish_date'])) $errors[] = "缺少發布日期欄位。";
        if (!isset($_POST['content'])) $errors[] = "缺少內容欄位。";
        if (!isset($_POST['status'])) $errors[] = "缺少狀態欄位。";
       
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors), 422);
        }
        
        // --- 處理封面圖片上傳 start---
        $image_url_for_db = handle_cover_image_upload(
            'cover_image',
            'new/', //這邊寫你們自己的封面圖資料夾名稱 會存在uploads裡面的下一層資料夾的自訂名稱
            'news_'//這裡是自訂義到時候圖片想叫開頭什麼名稱
        );

            // --- 處理封面圖片上傳 end---
        
        // --- 主要業務邏輯 (資料庫操作) ---
        $mysqli->begin_transaction();
        
        $sql = "INSERT INTO news (title, category_id, publish_date, content, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        $title = trim($_POST["title"]);
        $category_id = intval($_POST["category_id"]);
        $publish_date = $_POST["publish_date"];
        $content = $_POST["content"]; 
        $image_url = $image_url_for_db;
        $status = intval($_POST["status"]);
        
        $stmt->bind_param("sisssi", $title, $category_id, $publish_date, $content, $image_url, $status);
        $stmt->execute();
        
        $mysqli->commit();

        $response = new stdClass(); 
        $response->success = true;
        $response->message = "新增消息成功！";
        $response->new_id = $stmt->insert_id;
        echo json_encode($response);

    } else {
        throw new Exception("僅允許 POST 請求。", 403);
    }

} catch (Exception $e) {
    if (isset($mysqli) && $mysqli->thread_id) {
        $mysqli->rollback();
    }
    
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);

    $errorResponse = new stdClass();
    $errorResponse->success = false;
    $errorResponse->message = $e->getMessage();
    echo json_encode($errorResponse);

} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>