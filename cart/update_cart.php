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

        if (!isset($data['cart_item_id'], $data['quantity'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：cart_item_id, quantity"]);
            exit();
        }

        $cart_item_id = mysqli_real_escape_string($mysqli, $data['cart_item_id']);
        $quantity = (int)$data['quantity'];

        $check_sql = "SELECT 1 FROM cart_items WHERE member_id = '$member_id' AND cart_item_id = '$cart_item_id'";
        $check_result = $mysqli->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            if ($quantity <= 0) {
                $delete_sql = "DELETE FROM cart_items WHERE cart_item_id = '$cart_item_id' AND member_id = '$member_id'";
                $mysqli->query($delete_sql);

                if ($mysqli->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "商品已成功從購物車中移除"]);
                } else {
                    http_response_code(200);
                    echo json_encode(["message" => "商品未變動，可能已被移除"]);
                }
            } else {
                // 如果數量大於 0，則執行更新操作
                $update_sql = "UPDATE cart_items SET quantity = '$quantity' WHERE cart_item_id = '$cart_item_id' AND member_id = '$member_id'";
                $mysqli->query($update_sql);

                if ($mysqli->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "商品數量更新成功"]);
                } else {
                    http_response_code(200);
                    echo json_encode(["message" => "商品數量未變動"]);
                }
            }
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