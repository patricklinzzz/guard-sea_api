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

        if (!isset($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：items 或資料格式不正確"]);
            exit();
        }
        
        $items_to_sync = $data['items'];
        $results = [];

        foreach ($items_to_sync as $item) {
            if (!isset($item['product_id'], $item['quantity'], $item['size'], $item['color'])) {
                $results[] = ["error" => "商品資料格式不正確", "product_id" => $item['product_id'] ?? null];
                continue;
            }

            $product_id = mysqli_real_escape_string($mysqli, $item['product_id']);
            $quantity = (int)$item['quantity'];
            $size = mysqli_real_escape_string($mysqli, $item['size']);
            $color = mysqli_real_escape_string($mysqli, $item['color']);

            if ($quantity <= 0) {
                $results[] = ["error" => "商品數量必須大於 0", "product_id" => $product_id];
                continue;
            }

            $check_sql = "SELECT cart_item_id, quantity FROM cart_items WHERE member_id = '$member_id' AND product_id = '$product_id' AND size = '$size' AND color_code = '$color'";
            $check_result = $mysqli->query($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                $row = $check_result->fetch_assoc();
                $existing_quantity = (int)$row['quantity'];
                $new_quantity = $existing_quantity + $quantity;
                $cart_item_id = $row['cart_item_id'];

                $update_sql = "UPDATE cart_items SET quantity = '$new_quantity' WHERE cart_item_id = '$cart_item_id' AND member_id = '$member_id'";
                $mysqli->query($update_sql);

                $results[] = ["message" => "商品數量更新成功", "cart_item_id" => $cart_item_id, "product_id" => $product_id];
            } else {
                $insert_sql = "INSERT INTO cart_items (member_id, product_id, quantity, size, color_code) VALUES ('$member_id', '$product_id', '$quantity', '$size', '$color')";
                $mysqli->query($insert_sql);
                
                $results[] = ["message" => "商品新增成功", "cart_item_id" => $mysqli->insert_id, "product_id" => $product_id];
            }
        }
        
        http_response_code(200);
        echo json_encode(["message" => "購物車同步完成", "results" => $results]);
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