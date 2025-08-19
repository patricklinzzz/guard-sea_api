<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

// CORS 預檢
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// 僅允許 GET
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => '方法不被允許（只接受 GET）'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// 參數（可選）
$limit  = isset($_GET['limit'])  ? max(1, intval($_GET['limit']))  : 50;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = '1';
if ($q !== '') {
  $kw = $mysqli->real_escape_string($q);
  $like = "'%$kw%'";
  $where = "(c.title LIKE $like OR c.description LIKE $like OR c.coupon_code_prefix LIKE $like)";
}

$sql = "
  SELECT
    c.coupon_id,
    c.quiz_id,
    c.valid_days,
    c.`type`,
    c.trigger_event,
    c.`value`,
    c.title,
    c.description,
    c.coupon_code_prefix,
    c.min_order_amount
    
  FROM coupons AS c
  WHERE $where
  ORDER BY c.coupon_id DESC
  LIMIT $limit OFFSET $offset
";


$result = $mysqli->query($sql);
if (!$result) {
  http_response_code(500);
  echo json_encode(['error' => '查詢失敗', 'detail' => $mysqli->error], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit();
}

// 組 items
$items = [];
while ($row = $result->fetch_assoc()) { $items[] = $row; }
$result->free();

// 取得總筆數（給前端做分頁用）
$totalRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM coupons AS c WHERE $where");
$total = $totalRes ? intval(($totalRes->fetch_assoc())['cnt']) : count($items);
if ($totalRes) $totalRes->free();

// 回傳
echo json_encode(['items' => $items, 'total' => $total], JSON_UNESCAPED_UNICODE);

$mysqli->close();