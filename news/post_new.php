<?php
// 開啟除錯模式，方便您在開發時查看詳細錯誤，部署到正式環境時建議註解掉
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 依賴引入 ---
require_once("../common/cors.php");
require_once("../common/conn.php");
// *** 【核心修改 1】引入我們抽離出來的上傳函式檔案 ***
require_once("../coverimage.php"); 

// 在檔案頂部統一宣告 API 的回應類型為 JSON。
header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- 後端欄位驗證 (這部分保持不變，非常重要) ---
        $errors = [];
        if (empty(trim($_POST['title']))) $errors[] = "消息標題為必填欄位。";
        if (empty($_POST['category_id'])) $errors[] = "必須選擇一個消息分類。";
        if (!isset($_POST['publish_date'])) $errors[] = "缺少發布日期欄位。";
        if (!isset($_POST['content'])) $errors[] = "缺少內容欄位。";
        if (!isset($_POST['status'])) $errors[] = "缺少狀態欄位。";
        // 注意：檔案是否存在的基礎驗證，已經被移到 coverimage.php 函式內部了
        if (empty($_POST['api_base_path'])) $errors[] = "缺少 API 基礎路徑參數。";
       
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors), 422);
        }
        // --- 後端欄位驗證-結束 ---


        // 呼叫封面圖上傳的api-coverimage.php外部函式來處理檔案上傳start#############################

        
        // 以前那一大段檔案處理邏輯，現在只需要下面這一行！
        // 我們把所有需要的參數都傳給 handle_cover_image_upload 函式，
        // 它會回傳一個處理好的、可以直接存入資料庫的圖片路徑。
        // 如果上傳過程中出錯，函式內部會直接拋出 Exception，中斷執行。
        $image_url_for_db = handle_cover_image_upload(
            'cover_image',           // 1. 前端的檔案欄位 key
            $_POST['api_base_path'], // 2. 前端傳來的 API 根路徑
            'new/',                  // 3. 要儲存的子資料夾
            'news_'                  // 4. 檔名前綴
        );
        
       // 呼叫封面圖上傳的api-coverimage.php外部函式來處理檔案上傳start#################################

      
        // --- 主要業務邏輯 (資料庫操作) ---
        $mysqli->begin_transaction();
        
        // 因為 handle_cover_image_upload 成功才會繼續，失敗會拋錯，
        // 所以我們不再需要 if(move_uploaded_file(...)) 這個判斷了。
        
        $response = new stdClass(); 
        $sql = "INSERT INTO news (title, category_id, publish_date, content, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        $title = trim($_POST["title"]);
        $category_id = intval($_POST["category_id"]);
        $publish_date = $_POST["publish_date"];
        $content = $_POST["content"]; 
        $image_url = $image_url_for_db; // <-- 直接使用函式回傳的、乾淨的路徑
        $status = intval($_POST["status"]);
        
        $stmt->bind_param("sisssi", $title, $category_id, $publish_date, $content, $image_url, $status);
        $stmt->execute();
        
        $mysqli->commit();

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