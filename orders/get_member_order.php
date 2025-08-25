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
        $member_id = mysqli_real_escape_string($mysqli, $_SESSION['member_id']);
        
        
        if (!isset($_GET['order_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少訂單ID"]);
            exit();
        }
        $order_id = mysqli_real_escape_string($mysqli, $_GET['order_id']);

        $order_sql = "SELECT 
                        o.order_id, o.order_date, o.status, o.subtotal_amount, o.shipping_fee,
                        o.discount_amount, o.final_amount, o.payment_method, o.receiver_name,
                        o.receiver_phone, o.receiver_address, o.contact_phone, o.notes,
                        o.payment_status, o.transaction_id,
                        m.fullname AS member_name, 
                        m.email AS member_email,
                        m.phone_number AS member_phone,
                        m.address AS member_address
                    FROM orders AS o
                    JOIN members AS m ON o.member_id = m.member_id
                    WHERE o.order_id = '$order_id' AND o.member_id = '$member_id'";
        
        $order_result = $mysqli->query($order_sql);

        if ($order_result === false) {
            throw new Exception("訂單查詢失敗: " . $mysqli->error);
        }

        if ($order_result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["error" => "找不到該訂單或您無權查看此訂單"]);
            exit();
        }

        $order = $order_result->fetch_assoc();

        $items_sql = "SELECT 
                        oi.order_item_id, oi.product_id, oi.quantity, oi.price_at_purchase,
                        oi.size, oi.color_code, p.name AS product_name, p.main_image_url
                        FROM order_items AS oi
                        JOIN products AS p ON oi.product_id = p.product_id
                        WHERE oi.order_id = '$order_id'";
        $items_result = $mysqli->query($items_sql);

        if ($items_result === false) {
            throw new Exception("訂單商品明細查詢失敗: " . $mysqli->error);
        }

        $order_items = [];
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }

        $order['order_items'] = $order_items;

        echo json_encode(["order" => $order]);
        
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