<?php
// 錯誤報告 (用於開發環境，上線前應關閉)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 引入資料庫連線檔案
// 假設 common/conn.php 檔案會建立 $mysqli 資料庫連線物件
// require_once __DIR__ . '/../common/conn.php'; 
// require_once __DIR__ . '/../coverimage.php'; 
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php");

// 初始化響應陣列，預設為錯誤狀態
$response = [
    'status' => 'error',
    'message' => '新增活動失敗'
];

// 檢查請求方法是否為 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 從 $_POST 全域變數中獲取資料 (用於處理 FormData)
    // 使用 null coalescing operator (??) 提供預設值
    
    $title = isset($_POST['title']) ? $_POST['title'] : null;
    $preface = isset($_POST['preface']) ? $_POST['preface'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $registration_close_date = isset($_POST['registration_close_date']) ? $_POST['registration_close_date'] : null;
    $presenter = isset($_POST['presenter']) ? $_POST['presenter'] : null;
    $location = isset($_POST['location']) ? $_POST['location'] : null;
    $quota = isset($_POST['quota']) ? $_POST['quota'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    
    // 檢查必填欄位
    if (empty($title) || empty($category_id) || empty($start_date) || empty($end_date) || empty($registration_close_date)) {
        $response['message'] = '缺少必要欄位';
        echo json_encode($response);
        exit();
    }

    // 處理圖片上傳
    $imageUrl = null; // 初始化圖片 URL
    // 檢查是否有圖片檔案上傳，並且上傳沒有錯誤
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/'; // 設定圖片上傳的目標目錄
        // 確保上傳目錄存在且可寫入
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        // 取得檔案名稱，並確保它不包含路徑資訊，只保留檔名
        $fileName = basename($_FILES['image_file']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        $imageUrl = handle_cover_image_upload('image_file', 'event/', 'event_');
    }

    try {
        // 對所有要插入到 SQL 查詢中的變數進行跳脫處理
        // 這對於防止 SQL 注入非常重要，尤其是在使用字串拼接時
        $title_safe = $mysqli->real_escape_string($title);
        $preface_safe = $mysqli->real_escape_string($preface);
        $description_safe = $mysqli->real_escape_string($description);
        $start_date_safe = $mysqli->real_escape_string($start_date);
        $end_date_safe = $mysqli->real_escape_string($end_date);
        $registration_close_date_safe = $mysqli->real_escape_string($registration_close_date);
        $presenter_safe = $mysqli->real_escape_string($presenter); // 主講人欄位進行跳脫
        $location_safe = $mysqli->real_escape_string($location);
        // quota 已經轉為 int，理論上不需要 real_escape_string，但為避免潛在問題，直接使用
        $notes_safe = $mysqli->real_escape_string($notes);
        // category_id 應該轉為 int
        $category_id_safe = (int)$category_id;
        $status_safe = $mysqli->real_escape_string($status);
        $imageUrl_safe = $mysqli->real_escape_string($imageUrl ?? ''); // 確保即使為 null 也進行跳脫

        // 準備 SQL 插入語句，直接拼接變數
        // 注意：字串值需要用單引號包起來
        $sql = "INSERT INTO activities (
                    title, category_id, preface, description, start_date, 
                    end_date, location, quota, registration_close_date, notes, status, image_url, presenter
                ) VALUES (
                    '" . $title_safe . "', 
                    " . $category_id_safe . ", 
                    '" . $preface_safe . "', 
                    '" . $description_safe . "', 
                    '" . $start_date_safe . "', 
                    '" . $end_date_safe . "', 
                    '" . $location_safe . "', 
                    " . (int)$quota . ", 
                    '" . $registration_close_date_safe . "', 
                    '" . $notes_safe . "', 
                    '" . $status_safe . "', 
                    '" . $imageUrl_safe . "',
                    '" . $presenter_safe . "'
                )";
        
        // 執行 SQL 查詢
        $result = $mysqli->query($sql);
        
        if ($result) {
            // 檢查是否成功插入一條記錄
            if ($mysqli->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = '活動新增成功';
                $response['image_url'] = $imageUrl; // 返回上傳的圖片 URL
            } else {
                $response['message'] = '資料庫未插入任何記錄，可能是重複資料或其他原因。';
                error_log('Database Insert Warning: No rows affected. SQL: ' . $sql);
            }
        } else {
            // 查詢執行失敗的處理
            $response['message'] = '資料庫插入錯誤: ' . $mysqli->error;
            error_log('Database Error: ' . $mysqli->error . ' SQL: ' . $sql);
        }

    } catch (Exception $e) {
        // 捕獲並處理任何運行時異常
        $response['message'] = '伺服器錯誤: ' . $e->getMessage();
        error_log('Server Error: ' . $e->getMessage());
    }
} else {
    // 處理非 POST 請求
    header("HTTP/1.1 405 Method Not Allowed");
    $response['message'] = '不允許的請求方法';
}

echo json_encode($response);
// 關閉資料庫連線
$mysqli->close();
?>