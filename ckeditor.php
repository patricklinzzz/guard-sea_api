<?php
// 引入 CORS 設定，確保編輯器可以跨域上傳
require_once("./common/cors.php");

// API 的回應永遠是 JSON
header('Content-Type: application/json; charset=UTF-8');

// 檢查 CKEditor 的 'upload' 檔案欄位
if (isset($_FILES['upload']) && $_FILES['upload']['error'] == UPLOAD_ERR_OK) {
    try {
        $file = $_FILES['upload'];
        
        // --- 變數定義 ---
        $upload_dir_name = 'ckeditor';
        $filename_prefix = 'content_';

        // --- 使用 __DIR__ 建立可靠的伺服器儲存路徑 ---
        $relative_upload_path = '/' . $upload_dir_name . '/';
        $absolute_save_dir = __DIR__ . $relative_upload_path;

        if (!is_dir($absolute_save_dir)) {
            // 建立資料夾，並加上權限檢查
            if (!mkdir($absolute_save_dir, 0777, true) && !is_dir($absolute_save_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $absolute_save_dir));
            }
        }
        
        // --- 檔名處理 ---
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = $filename_prefix . uniqid() . rand(100, 999) . '.' . $file_extension;
        
        // --- 【核心】動態獲取 API 專案的公開路徑 ---
        // 1. 從 $_SERVER['REQUEST_URI'] 獲取完整的請求路徑 (e.g., /guard-sea-api/upload_ckeditor.php)
        $request_uri = $_SERVER['REQUEST_URI'];
        // 2. 使用 dirname() 函式，只取出路徑中的目錄部分 (e.g., /guard-sea-api)
        $api_project_folder = dirname($request_uri);
        
        // --- 路徑拼接 (現在是動態的) ---
        $url_for_editor = 'http://' . $_SERVER['HTTP_HOST'] . $api_project_folder . $relative_upload_path . $unique_filename;
        $full_server_path_to_save = $absolute_save_dir . $unique_filename;

        // --- 執行檔案移動 ---
        if (move_uploaded_file($file['tmp_name'], $full_server_path_to_save)) {
            // 成功時，回傳 CKEditor 指定的 JSON 格式
            echo json_encode([
                'uploaded' => 1,
                'fileName' => $unique_filename,
                'url' => $url_for_editor
            ]);
        } else {
            throw new Exception("伺服器無法儲存檔案，請檢查目標資料夾的寫入權限。");
        }

    } catch (Exception $e) {
        // *** 失敗時，回傳 CKEditor 指定的錯誤 JSON 格式 ***
        // http_response_code(500); // 可選：設定伺服器錯誤狀態碼
        echo json_encode([
            'uploaded' => 0,
            'error' => ['message' => $e->getMessage()]
        ]);
    }
} else {
    // *** 如果沒有收到 'upload' 檔案，也回傳標準的錯誤格式 ***
    $errorMessage = '無效的上傳請求。';
    if (isset($_FILES['upload']['error'])) {
        switch ($_FILES['upload']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = '上傳的檔案大小超過限制。';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = '沒有檔案被上傳。';
                break;
            default:
                $errorMessage = '發生未知的上傳錯誤。';
                break;
        }
    }
    
    // http_response_code(400); // Bad Request
    echo json_encode([
        'uploaded' => 0,
        'error' => ['message' => $errorMessage]
    ]);
}

exit();
?>