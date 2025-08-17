<?php
// 錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

// 檢查請求方法是否為 POST
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit();
}

// 獲取並解析 JSON 格式的請求主體
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 檢查資料是否完整
if (!isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "缺少必要的參數 (id 或 status)"], JSON_UNESCAPED_UNICODE);
    exit();
}

// 執行資料庫更新
try {
    $event_id = (int)$data['id'];
    $status = $data['status'];

    // 使用預處理語句來安全地更新資料
    // 主要鍵欄位已從 'id' 修正為 'activity_id'
    $sql = "UPDATE activities SET status = ? WHERE activity_id = ?"; 
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("預處理語句失敗: " . $mysqli->error);
    }

    $stmt->bind_param("si", $status, $event_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "活動狀態更新成功！"], JSON_UNESCAPED_UNICODE);
        } else {
            // 如果 affected_rows 為 0，表示 ID 找不到或新狀態與舊狀態相同
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "找不到該活動或狀態已是最新"], JSON_UNESCAPED_UNICODE);
        }
    } else {
        throw new Exception("執行更新失敗: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "伺服器錯誤", "details" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($mysqli)) $mysqli->close();
}
?>