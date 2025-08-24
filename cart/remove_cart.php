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

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['cart_item_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：cart_item_id"]);
            exit();
        }

        $cart_item_id = mysqli_real_escape_string($mysqli, $data['cart_item_id']);

        $remove_sql = "DELETE FROM cart_items WHERE cart_item_id = '$cart_item_id' AND member_id = '$member_id'";
        $mysqli->query($remove_sql);
        
        if ($mysqli->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["message" => "商品已成功從購物車中移除"]);
        } else {
            http_response_code(403);
            echo json_encode(["error" => "拒絕存取：該購物車項目不存在或不屬於此會員"]);
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