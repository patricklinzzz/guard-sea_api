<?php
//session_start();
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

        // 修正：檢查 product_id, quantity, size, color 是否都存在
        if (!isset($data['product_id'], $data['quantity'], $data['size'], $data['color'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：product_id, quantity, size, color"]);
            exit();
        }

        $product_id = mysqli_real_escape_string($mysqli, $data['product_id']);
        $quantity = (int)$data['quantity'];
        $size = mysqli_real_escape_string($mysqli, $data['size']);
        $color = mysqli_real_escape_string($mysqli, $data['color']);

        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "商品數量必須大於 0"]);
            exit();
        }

        // 修正：在 WHERE 條件中新增 size 和 color_code，以確保找到正確的商品規格
        $check_sql = "SELECT cart_item_id, quantity FROM cart_items WHERE member_id = '$member_id' AND product_id = '$product_id' AND size = '$size' AND color_code = '$color'";
        $check_result = $mysqli->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $existing_quantity = (int)$row['quantity'];
            $new_quantity = $existing_quantity + $quantity;
            $cart_item_id = $row['cart_item_id'];

            $update_sql = "UPDATE cart_items SET quantity = '$new_quantity' WHERE cart_item_id = '$cart_item_id' AND member_id = '$member_id'";
            $mysqli->query($update_sql);

            if ($mysqli->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(["message" => "商品數量更新成功", "cart_item_id" => $cart_item_id]);
            } else {
                http_response_code(200);
                echo json_encode(["message" => "商品數量未變動", "cart_item_id" => $cart_item_id]);
            }
        } else {
            // 修正：在 INSERT 語句中新增 size 和 color_code 欄位
            $insert_sql = "INSERT INTO cart_items (member_id, product_id, quantity, size, color_code) VALUES ('$member_id', '$product_id', '$quantity', '$size', '$color')";
            $mysqli->query($insert_sql);
            
            if ($mysqli->insert_id) {
                http_response_code(201);
                echo json_encode(["message" => "商品新增成功", "cart_item_id" => $mysqli->insert_id]);
            } else {
                throw new mysqli_sql_exception("新增商品失敗: " . $mysqli->error);
            }
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