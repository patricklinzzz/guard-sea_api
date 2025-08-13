<?php
// 這個檔案裡只放可重用的函式

/**
 * 處理單一檔案上傳，並回傳存入資料庫的路徑。
 *
 * @param string $file_input_name 前端 FormData 中的檔案欄位名稱 (e.g., 'cover_image')
 * @param string $api_base_path   前端傳來的 API 基礎路徑 (e.g., '/guard-sea-api')
 * @param string $subfolder       要存入的子資料夾 (e.g., 'new/')
 * @param string $prefix          檔名前綴 (e.g., 'news_')
 * @return string                 成功後回傳網頁可訪問的圖片路徑
 * @throws Exception              發生錯誤時拋出例外
 */
function handle_cover_image_upload($file_input_name, $api_base_path, $subfolder, $prefix) {
    
    // 1. 驗證檔案是否存在
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("必須上傳指定的圖片檔案 ('" . $file_input_name . "')。");
    }

    // 2. 計算路徑
    $relative_upload_path = '/uploads/' . $subfolder;
    $absolute_save_dir = __DIR__ . $relative_upload_path; 
    if (!is_dir($absolute_save_dir)) {
        mkdir($absolute_save_dir, 0777, true);
    }

    // 3. 處理檔名
    $file = $_FILES[$file_input_name];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = $prefix . uniqid() . '.' . $file_extension;

    // 4. 準備最終路徑
    $image_url_for_db = $api_base_path . $relative_upload_path . $unique_filename;
    $full_server_path_to_save = $absolute_save_dir . $unique_filename;

    // 5. 移動檔案
    if (move_uploaded_file($file['tmp_name'], $full_server_path_to_save)) {
        // 成功，回傳要存入資料庫的路徑
        return $image_url_for_db;
    } else {
        // 失敗，拋出例外
        throw new Exception("伺服器無法儲存上傳的檔案。請檢查 " . $absolute_save_dir . " 的寫入權限。");
    }
}
?>