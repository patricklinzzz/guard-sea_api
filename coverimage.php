<?php
// coverimage.php (最終雲端安全版)

/**
 * 處理單一檔案上傳，並回傳存入資料庫的「相對路徑」。
 * 這個函式變得更獨立、更安全，不再需要前端告訴它路徑。
 *
 * @param string $file_key      前端 <input type="file"> 的 name 屬性 (例如 'cover_image')
 * @param string $sub_folder    要儲存在 /uploads/ 下的子資料夾名稱 (例如 'new/')
 * @param string $prefix        新檔名的前綴 (例如 'news_')
 * @return string|null          成功時回傳「相對路徑」(例如 /uploads/new/news_xxxx.jpg)，如果檔案非必填且未上傳，則回傳 null。
 * @throws Exception            處理過程中發生任何錯誤時拋出。
 */
function handle_cover_image_upload($file_key, $sub_folder, $prefix) {
    
    // 1. 檢查檔案是否存在及上傳狀態
    // 如果檔案欄位不存在，或有錯誤但不是「未選擇檔案」的錯誤，就拋出例外
    if (!isset($_FILES[$file_key])) {
        // 如果連檔案欄位都沒有，可能是一個非預期的請求
        return null;
    }

    if (isset($_FILES[$file_key]['error'])) {
        // 如果使用者根本沒上傳檔案 (例如在編輯頁面，不想更換圖片)，就直接回傳 null
        if ($_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        // 如果有其他上傳錯誤
        if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
             throw new Exception("檔案上傳失敗，錯誤碼：" . $_FILES[$file_key]['error'], 400);
        }
    }
    
    $file = $_FILES[$file_key];

    // 2. 安全性檢查
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("不支援的檔案類型。僅允許 JPG, PNG, GIF, WEBP。", 415);
    }

    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_size) {
        throw new Exception("檔案大小超過 5MB 上限。", 413);
    }

    // 3. 計算正確的伺服器儲存路徑
    // 使用 dirname(__DIR__) 來獲取專案根目錄 (假設 coverimage.php 在 /api/ 或 /common/ 目錄下)
   $project_root = __DIR__;
    $upload_dir = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $sub_folder;

    // 如果目標資料夾不存在，嘗試用更安全的權限 0775 建立它
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
            throw new Exception("無法建立儲存目錄，請檢查伺服器權限。", 500);
        }
    }

    // 4. 處理檔名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = $prefix . uniqid() . '.' . strtolower($extension);
    $destination_path = $upload_dir . $unique_filename;

    // 5. 移動檔案
    if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
        throw new Exception("移動上傳檔案時發生錯誤。", 500);
    }

    // 6. 永遠只回傳「相對路徑」給資料庫
    $relative_path_for_db = '/uploads/' . $sub_folder . $unique_filename;
    
    return $relative_path_for_db;
}
?>