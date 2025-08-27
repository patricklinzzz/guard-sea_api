<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "僅允許 GET 請求"]);
    exit();
}

try {
    if (!isset($_SESSION['member_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "未授權，請先登入"]);
        exit();
    }

    $member_id = intval($_SESSION['member_id']);

    $sql = "SELECT 
                ar.activity_registration_id AS reg_id,
                e.activity_id,
                e.title,
                e.image_url AS image,
                e.start_date,
                e.end_date,
                e.location
            FROM activity_registrations AS ar
            JOIN activities AS e ON ar.activity_id = e.activity_id
            WHERE ar.member_id = $member_id
            ORDER BY ar.registration_date DESC";

    $result = $mysqli->query($sql);

    if ($result === false) {
        throw new Exception("查詢失敗: " . $mysqli->error);
    }

    $events = [];
    $today = date("Y-m-d H:i:s");

    while ($row = $result->fetch_assoc()) {
        // 日期格式處理
        $start = date("Y/m/d H:i", strtotime($row['start_date']));
        $end   = date("Y/m/d H:i", strtotime($row['end_date']));
        $event_date = ($start === $end) ? $start : $start . " ~ " . $end;

        // 狀態判斷
        $status = ($row['end_date'] < $today) ? "已完成" : "已報名";

        // 圖片直接用資料庫欄位，若沒圖片回傳 null
        $image_url = $row['image'] ?: null;

        $events[] = [
            "id"          => (int)$row['reg_id'],
            "activity_id" => (int)$row['activity_id'],
            "name"        => $row['title'],
            "image"       => $image_url,
            "date"        => $event_date,
            "location"    => $row['location'],
            "status"      => $status
        ];
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error"   => "伺服器發生錯誤",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($mysqli)) $mysqli->close();
}
?>