<?php
// 錯誤報告 (用於開發環境，上線前應關閉)
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
    // 將接收到的資料進行型別轉換並進行跳脫處理
    $event_id_safe = (int)$data['id']; // 先轉為整數，確保數值型別
    $status_safe = $mysqli->real_escape_string($data['status']); // 跳脫字串中的特殊字元

    // 直接將變數拼接到 SQL 查詢字串中
    // 主要鍵欄位已從 'id' 修正為 'activity_id'
    $sql = "UPDATE activities SET status = '" . $status_safe . "' WHERE activity_id = " . $event_id_safe; 
    
    // 執行查詢
    $result = $mysqli->query($sql);

    if ($result) {
        // 檢查是否有影響的行數，判斷更新是否成功
        if ($mysqli->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "活動狀態更新成功！"], JSON_UNESCAPED_UNICODE);
        } else {
            // 如果 affected_rows 為 0，表示 ID 找不到或新狀態與舊狀態相同
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "找不到該活動或狀態已是最新"], JSON_UNESCAPED_UNICODE);
        }
    } else {
        // 查詢執行失敗
        throw new Exception("執行更新失敗: " . $mysqli->error);
    }

} catch (Exception $e) {
    // 捕獲並處理任何異常
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "伺服器錯誤", "details" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    // 無論成功或失敗，最後關閉資料庫連線
    if (isset($mysqli)) $mysqli->close();
}
?>