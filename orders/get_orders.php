<?php
//session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    try {
        if (!isset($_SESSION['isAuthenticated']) || $_SESSION['isAuthenticated'] !== true) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $sql = "SELECT 
                    o.order_id, o.order_date, o.status, o.final_amount, o.notes, 
                    m.fullname AS member_name
                FROM orders AS o
                JOIN members AS m ON o.member_id = m.member_id
                ORDER BY o.order_date DESC";
                
        $result = $mysqli->query($sql);
        if ($result === false) {
            throw new Exception("訂單查詢失敗: " . $mysqli->error);
        }
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        echo json_encode(["orders" => $orders]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
    } finally {
        if (isset($mysqli)) $mysqli->close();
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "僅允許 GET 請求"]);
?>