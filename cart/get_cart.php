<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    try {
        if (!isset($_SESSION['member_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $member_id = $_SESSION['member_id'];

        $sql = "SELECT
                    ci.cart_item_id,
                    ci.member_id,
                    ci.product_id,
                    ci.quantity,
                    ci.size,
                    ci.color_code,
                    p.name,
                    p.price,
                    p.main_image_url
                FROM cart_items AS ci
                JOIN products AS p ON ci.product_id = p.product_id
                WHERE ci.member_id = '$member_id'";

        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new mysqli_sql_exception("購物車查詢失敗: " . $mysqli->error);
        }

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $item = [
                'id' => (int)$row['product_id'],
                'cart_item_id' => (int)$row['cart_item_id'],
                'quantity' => (int)$row['quantity'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'image' => $row['main_image_url'],
                'size' => $row['size'],
                'color' => $row['color_code']
            ];
            $items[] = $item;
        }

        echo json_encode(["items" => $items]);

        $result->free();

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
echo json_encode(["error" => "僅允許 GET 請求"]);
?>