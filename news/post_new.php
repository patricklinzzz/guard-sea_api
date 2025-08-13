<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

// 在檔案頂部統一宣告 API 的回應類型為 JSON。
header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- 後端欄位驗證 - 檢查所有必要欄位 ---
        $errors = [];
        if (empty(trim($_POST['title']))) $errors[] = "消息標題為必填欄位。";
        if (empty($_POST['category_id'])) $errors[] = "必須選擇一個消息分類。";
        if (!isset($_POST['publish_date'])) $errors[] = "缺少發布日期欄位。";
        if (!isset($_POST['content'])) $errors[] = "缺少內容欄位。";
        if (!isset($_POST['status'])) $errors[] = "缺少狀態欄位。";
        if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "必須上傳一張封面圖片。";
        }
       
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors), 422);
        }
        // --- 後端欄位驗證-結束 ---


        // ### 【封面圖檔案上傳核心邏輯】 - 開始 ##############################################
        
        // --- 改變數(對應後台自訂的)和圖片存放資料夾定義 還有圖片開頭名start ---
        $file_input_name = 'cover_image';
        $upload_subfolder = 'new/';
        $filename_prefix = 'news_';
         // --- 改變數(對應後台自訂的)和圖片存放資料夾定義 還有圖片開頭名end ---

         //以下就不用改了 是固定邏輯
        
        // 1. 定義從「API 專案根目錄」到上傳資料夾的路徑
        //    這個路徑將被用來拼接成「網頁可訪問路徑」和「伺服器儲存路徑」
        $relative_upload_path = '/uploads/' . $upload_subfolder;

        // 2. 使用 __DIR__ 建立儲存檔案用的「伺服器絕對路徑」
        //    __DIR__ -> .../guard-sea-api/news  (假設此檔案在 news 資料夾內)
        //    /../    -> .../guard-sea-api/
        //    最終拼接出 -> .../guard-sea-api/uploads/new/
        $absolute_save_dir = __DIR__ . '/..' . $relative_upload_path;
        
        // 檢查並建立目標資料夾
        if (!is_dir($absolute_save_dir)) {
            mkdir($absolute_save_dir, 0777, true);
        }

        // --- 處理檔名 ---
        $file = $_FILES[$file_input_name];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = $filename_prefix . uniqid() . '.' . $file_extension;
        
        // 3. 準備「存入資料庫」的「網頁可訪問路徑」
        //    這個路徑必須包含 API 的專案目錄，例如 /guard-sea-api
        //    最穩健的作法是從您的 VITE_API_BASE 環境變數反向推導出這個路徑
        //    我們先用一個變數來定義它，方便未來修改
        $api_project_folder = $_POST['api_base_path']; 
        $image_url_for_db = $api_project_folder . $relative_upload_path . $unique_filename;

        // 4. 準備「move_uploaded_file」函式使用的「伺服器絕對路徑」
        $full_server_path_to_save = $absolute_save_dir . $unique_filename;

   // ### 【封面圖檔案上傳核心邏輯】 - 結束 ####################################################
      
        // --- 主要業務邏輯 (資料庫操作) ---
        $mysqli->begin_transaction();

        if (move_uploaded_file($file['tmp_name'], $full_server_path_to_save)) {
            
            $response = new stdClass(); 
            $sql = "INSERT INTO news (title, category_id, publish_date, content, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);

            $title = trim($_POST["title"]);
            $category_id = intval($_POST["category_id"]);
            $publish_date = $_POST["publish_date"];
            $content = $_POST["content"]; 
            $image_url = $image_url_for_db; // <-- 使用我們拼接好的、正確的網頁路徑
            $status = intval($_POST["status"]);
            
            $stmt->bind_param("sisssi", $title, $category_id, $publish_date, $content, $image_url, $status);
            $stmt->execute();
            
            $mysqli->commit();

            $response->success = true;
            $response->message = "新增消息成功！";
            $response->new_id = $stmt->insert_id;
            echo json_encode($response);

        } else {
            $mysqli->rollback();
            throw new Exception("伺服器無法儲存上傳的檔案。請檢查 " . $absolute_save_dir . " 的寫入權限。");
        }
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