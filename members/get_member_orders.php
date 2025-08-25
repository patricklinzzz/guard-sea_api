<?php
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

    $orders_sql = "SELECT 
                    o.order_id as id, 
                    o.order_date as date, 
                    o.status, 
                    o.subtotal_amount,
                    o.discount_amount,
                    o.shipping_fee,
                    o.final_amount as total,
                    '7天內' as shipping_estimate
                FROM orders AS o
                WHERE o.member_id = $member_id
                ORDER BY o.order_date DESC";
    
    $result = $mysqli->query($orders_sql);
    
    if ($result === false) {
        throw new Exception("訂單主體查詢失敗: " . $mysqli->error);
    }

    $orders = [];
    while ($order = $result->fetch_assoc()) {
        $orders[] = $order;
    }
    
    if (!empty($orders)) {
        $order_ids = array_column($orders, 'id');
        
        $safe_order_ids = array_map('intval', $order_ids);
        $ids_string = implode(',', $safe_order_ids);
        
        if (!empty($ids_string)) {
            $items_sql = "SELECT 
                            oi.order_id, 
                            p.name, 
                            p.main_image_url as image, 
                            oi.color_code as color,
                            oi.size, 
                            oi.quantity, 
                            oi.price_at_purchase as price
                        FROM order_items AS oi
                        JOIN products AS p ON oi.product_id = p.product_id
                        WHERE oi.order_id IN ($ids_string)";
                        
            $items_result = $mysqli->query($items_sql);

            if ($items_result === false) {
                throw new Exception("訂單商品查詢失敗: " . $mysqli->error);
            }
            
            $items_by_order = [];
            while ($item = $items_result->fetch_assoc()) {
                $items_by_order[$item['order_id']][] = $item;
            }

            foreach ($orders as &$order) { 
                $order_id = $order['id'];
                $order['items'] = $items_by_order[$order_id] ?? [];
                $order['summary'] = [
                    'subtotal'   => (float)$order['subtotal_amount'],
                    'discount'   => (float)$order['discount_amount'],
                    'shipping_fee' => (float)$order['shipping_fee'],
                    'total'      => (float)$order['total']
                ];
                unset($order['subtotal_amount'], $order['discount_amount'], $order['shipping_fee']);
            }
        }
    }
    
    echo json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
} finally {
    if (isset($mysqli)) $mysqli->close();
}
?>