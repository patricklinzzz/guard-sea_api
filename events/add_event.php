<?php
// 設定 CORS 標頭
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

// 引入你的資料庫連線檔案
require_once __DIR__ . '/../common/conn.php'; 

$response = [
    'status' => 'error',
    'message' => '新增活動失敗'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 從 $_POST 全域變數中獲取資料 (用於處理 FormData)
    $title = $_POST['title'] ?? null;
    $preface = $_POST['preface'] ?? null;
    $description = $_POST['description'] ?? null;
    
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $registration_close_date = $_POST['registration_close_date'] ?? null;

    $location = $_POST['location'] ?? null;
    $quota = $_POST['quota'] ?? 0;
    $notes = $_POST['notes'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $status = $_POST['status'] ?? '報名中';
    
    // 檢查必填欄位
    if (empty($title) || empty($category_id) || empty($start_date) || empty($end_date) || empty($registration_close_date)) {
        $response['message'] = '缺少必要欄位';
        echo json_encode($response);
        exit;
    }

    // 處理圖片上傳
    $imageUrl = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/'; // 上傳目錄
        $uploadFile = $uploadDir . basename($_FILES['image_file']['name']);
        
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadFile)) {
            // 關鍵：只回傳相對路徑
            $imageUrl = 'images/' . basename($_FILES['image_file']['name']);
        } else {
            $response['message'] = '圖片上傳失敗';
            echo json_encode($response);
            exit;
        }
    }

    try {
        // 準備 SQL 插入語句
        $sql = "INSERT INTO activities (
            title, category_id, preface, description, start_date, 
            end_date, location, quota, registration_close_date, notes, status, image_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        
        $stmt->bind_param(
            "sissssssssss", 
            $title, $category_id, $preface, $description, $start_date, 
            $end_date, $location, $quota, $registration_close_date, $notes, $status, $imageUrl
        );
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = '活動新增成功';
            $response['image_url'] = $imageUrl; 
        } else {
            $response['message'] = '資料庫插入錯誤: ' . $stmt->error;
            error_log('Database Error: ' . $stmt->error);
        }

    } catch (Exception $e) {
        $response['message'] = '伺服器錯誤: ' . $e->getMessage();
        error_log('Server Error: ' . $e->getMessage());
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    $response['message'] = '不允許的請求方法';
}

echo json_encode($response);
$mysqli->close();
?>