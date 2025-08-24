<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
header("Content-Type: application/json; charset=UTF-8");

// 只允許 GET 請求
if ($_SERVER['REQUEST_METHOD'] === "GET") {

    // 從 URL 參數中獲取 activity_id
    $activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

    // 驗證 activity_id 是否有效
    if ($activity_id <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "缺少或錯誤的 activity_id"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 將變數進行跳脫處理，避免部分 SQL 注入問題
    $activity_id_safe = $mysqli->real_escape_string($activity_id);
    
    // SQL 查詢，直接將變數拼接到查詢字串中
    // 欄位 'name' 和 'email' 沒有被選取
    $sql = "SELECT r.activity_registration_id, r.member_id, 
                    r.phone, r.contact_person, r.contact_phone, r.notes, r.registration_date
            FROM activity_registrations r
            WHERE r.activity_id = " . $activity_id_safe . "
            ORDER BY r.registration_date ASC";

    // 執行查詢
    $result = $mysqli->query($sql);

    // 檢查是否有查詢結果
    if ($result && $result->num_rows > 0) {
        $registrations = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            "status" => "success",
            "data" => $registrations
        ], JSON_UNESCAPED_UNICODE);
    } else if ($result === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "SQL 查詢錯誤: " . $mysqli->error], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // 如果沒有找到任何報名紀錄
        echo json_encode([
            "status" => "success",
            "message" => "該活動目前沒有任何報名紀錄。",
            "data" => []
        ], JSON_UNESCAPED_UNICODE);
    }

    $mysqli->close();
    exit;
}

// 處理非 GET 請求
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
?>