<?php
// delete.php (最終雲端安全版)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../common/cors.php");
require_once("../common/conn.php");

// 補上處理預檢 (Preflight) 請求的核心邏輯
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

try {
    if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
        throw new Exception("僅允許 DELETE 請求。", 405);
    }

    if (empty($data['news_id'])) {
        throw new Exception("刪除失敗: 缺少必要的消息 ID。", 422);
    }

    $news_id = intval($data['news_id']);

    $mysqli->begin_transaction();

    // --- 檔案處理邏輯已根據您的決定移除 ---

    // 只刪除資料庫中的紀錄
    $sql_delete = "DELETE FROM news WHERE news_id = ?";
    $stmt_delete = $mysqli->prepare($sql_delete);
    $stmt_delete->bind_param("i", $news_id);
    $stmt_delete->execute();

    if ($stmt_delete->affected_rows === 0) {
        throw new Exception("刪除失敗: 找不到對應的消息，或它已被刪除。", 404);
    }
    $stmt_delete->close();

    $mysqli->commit();

    $response = new stdClass();
    $response->success = true;
    $response->message = "消息已成功刪除。";
    echo json_encode($response);

} catch (Exception $e) {
    if (isset($mysqli) && $mysqli->thread_id) {
        $mysqli->rollback();
    }
    
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);

    $errorResponse = new stdClass();
    $errorResponse->success = false;
    $errorResponse->message = "刪除失敗: " . $e->getMessage();
    echo json_encode($errorResponse);

} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>