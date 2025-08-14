<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  header("Content-Type: application/json; charset=UTF-8");

  $response = new stdClass();

  $sql = "SELECT 
            c.coupon_id,
            c.quiz_id,
            c.type,
            c.value,
            c.title,
            c.description,
            c.coupon_code_prefix,
            c.min_order_amount,
            c.valid_days
          FROM coupons c
          ORDER BY c.coupon_id DESC";

  $stmt = $mysqli->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  $response->coupons = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode($response, JSON_UNESCAPED_UNICODE);

  $stmt->close();
  $mysqli->close();
  exit();
}

http_response_code(403);
header("Content-Type: application/json; charset=UTF-8");
echo json_encode((object)['error' => '拒絕存取'], JSON_UNESCAPED_UNICODE);