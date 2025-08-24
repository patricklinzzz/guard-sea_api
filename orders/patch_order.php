<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    try {
        if (!isset($_SESSION['isAuthenticated']) || $_SESSION['isAuthenticated'] !== true) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['order_id'], $data['notes'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：order_id, notes"]);
            exit();
        }

        $order_id = mysqli_real_escape_string($mysqli, $data['order_id']);
        $notes = mysqli_real_escape_string($mysqli, $data['notes']);

        $sql = "UPDATE orders SET notes = '$notes' WHERE order_id = '$order_id'";
        $mysqli->query($sql);

        if ($mysqli->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["message" => "訂單備註更新成功"]);
        } else {
            http_response_code(200);
            echo json_encode(["message" => "訂單未找到或備註未變動"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
    } finally {
        if (isset($mysqli)) $mysqli->close();
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "僅允許 POST 請求"]);
?>