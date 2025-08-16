<?php
// path.php (最終雲端安全版)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php"); 

header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (empty($_POST['news_id'])) {
            throw new Exception("更新失敗: 缺少必要的消息 ID。", 422);
        }

        $mysqli->begin_transaction();

        $image_url_for_db = null;
        
        // 檢查使用者是否上傳了新的封面圖
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            // 使用新的、乾淨的函式呼叫來處理新上傳的圖片
            $image_url_for_db = handle_cover_image_upload(
                'cover_image',
                'new/',
                'news_'
            );
        }

        // --- 動態建立 UPDATE SQL ---
        $sql_parts = [];
        $params = [];
        $types = "";
        
        if (isset($_POST['title'])) { $sql_parts[] = "title = ?"; $params[] = trim($_POST["title"]); $types .= "s"; }
        if (isset($_POST['category_id'])) { $sql_parts[] = "category_id = ?"; $params[] = intval($_POST["category_id"]); $types .= "i"; }
        if (isset($_POST['publish_date'])) { $sql_parts[] = "publish_date = ?"; $params[] = $_POST["publish_date"]; $types .= "s"; }
        if (isset($_POST['content'])) { $sql_parts[] = "content = ?"; $params[] = $_POST["content"]; $types .= "s"; }
        if (isset($_POST['status'])) { $sql_parts[] = "status = ?"; $params[] = intval($_POST["status"]); $types .= "i"; }
        if ($image_url_for_db !== null) { $sql_parts[] = "image_url = ?"; $params[] = $image_url_for_db; $types .= "s"; }

        if (empty($sql_parts)) {
            $mysqli->commit();
            echo json_encode(["success" => true, "message" => "沒有偵測到任何變更。"]);
            exit;
        }

        // --- 組合 SQL 並執行 ---
        $sql = "UPDATE news SET " . implode(", ", $sql_parts) . " WHERE news_id = ?";
        $params[] = intval($_POST['news_id']);
        $types .= "i";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $mysqli->commit();

        // --- 刪除舊檔案的邏輯已根據您的決定移除 ---
        
        // --- 準備回傳給前端的資料 ---
        $response = new stdClass(); 
        $response->success = true;
        $response->message = "更新消息成功！";
        
        $updated_data = ['news_id' => intval($_POST['news_id'])];
        if (isset($_POST['title'])) $updated_data['title'] = trim($_POST['title']);
        if (isset($_POST['category_id'])) $updated_data['category_id'] = intval($_POST['category_id']);
        if (isset($_POST['publish_date'])) $updated_data['publish_date'] = $_POST['publish_date'];
        if (isset($_POST['content'])) $updated_data['content'] = $_POST['content'];
        if (isset($_POST['status'])) $updated_data['status'] = intval($_POST['status']);
        if ($image_url_for_db !== null) $updated_data['image_url'] = $image_url_for_db;

        $response->updated_data = $updated_data;
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
    $errorResponse->message = "更新失敗: " . $e->getMessage();
    echo json_encode($errorResponse);

} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>