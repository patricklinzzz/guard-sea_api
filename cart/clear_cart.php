<?php
session_start(); 
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        if (!isset($_SESSION['member_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $member_id = $_SESSION['member_id'];
        
        $clear_sql = "DELETE FROM cart_items WHERE member_id = '$member_id'";
        $mysqli->query($clear_sql);

        if ($mysqli->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["message" => "購物車已清空"]);
        } else {
            http_response_code(200);
            echo json_encode(["message" => "購物車已經是空的"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "伺服器發生錯誤",
            "message" => $e->getMessage()
        ]);
    } finally {
        if (isset($mysqli)) {
            $mysqli->close();
        }
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "僅允許 POST 請求"]);
?>