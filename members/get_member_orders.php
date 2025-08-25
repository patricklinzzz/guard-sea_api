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
        $member_id = $_SESSION['member_id'];

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
                    WHERE o.member_id = ?
                    ORDER BY o.order_date DESC"; 
        $stmt = $mysqli->prepare($orders_sql);
        $stmt->bind_param("i", $member_id); 
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($order = $result->fetch_assoc()) {
            $orders[] = $order;
        }
        
        if (!empty($orders)) {
            $order_ids = array_column($orders, 'id');
            
            $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            $types = str_repeat('s', count($order_ids)); 
            
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
                        WHERE oi.order_id IN ($placeholders)";
                        
            $items_stmt = $mysqli->prepare($items_sql);
            $items_stmt->bind_param($types, ...$order_ids);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
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
        
        echo json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500); 
        echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
    } finally {
        if (isset($mysqli)) $mysqli->close();
    }
    ?>