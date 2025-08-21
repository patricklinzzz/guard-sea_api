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

    // SQL 查詢，只從 activity_registrations 表格中選取欄位
    // 這段語句沒有 JOIN，也不選取 'name' 和 'email'
    $sql = "SELECT r.activity_registration_id, r.member_id,
                    r.phone, r.contact_person, r.contact_phone, r.notes, r.registration_date
            FROM activity_registrations r
            WHERE r.activity_id = ?
            ORDER BY r.registration_date ASC";

    // 使用預處理語句，防止 SQL 注入
    $stmt = $mysqli->prepare($sql);
    
    // 檢查預處理是否成功
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "SQL 語法錯誤: " . $mysqli->error], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // 檢查是否有查詢結果
    if ($result->num_rows > 0) {
        $registrations = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            "status" => "success",
            "data" => $registrations
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 如果沒有找到任何報名紀錄
        echo json_encode([
            "status" => "success",
            "message" => "該活動目前沒有任何報名紀錄。",
            "data" => []
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt->close();
    $mysqli->close();
    exit;
}

// 處理非 GET 請求
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
?>