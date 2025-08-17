<?php
// File: coupons_patch.php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/conn.php';

header("Content-Type: application/json; charset=UTF-8");

// ===== 可開啟偵錯（上線請關閉） =====
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// ===== 處理 CORS 預檢 =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 支援 Method Override（環境不吃 PATCH 時使用） =====
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
  $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']); // e.g. PATCH
}

// ===== 僅允許 PATCH =====
if ($method !== 'PATCH') {
  http_response_code(405);
  echo json_encode((object)['error' => '方法不被允許（只接受 PATCH）'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 允許更新的欄位（白名單） =====
$PATCHABLE = [
  'value',
  'title',
  'description',
  'coupon_code_prefix',
  'min_order_amount',
  'valid_days'
];

// ===== 讀取 id（支援 ?id= 與 ?coupon_id=） =====
$couponId = 0;
if (isset($_GET['id'])) {
  $couponId = intval($_GET['id']);
} elseif (isset($_GET['coupon_id'])) {
  $couponId = intval($_GET['coupon_id']);
}

if ($couponId <= 0) {
  http_response_code(400);
  echo json_encode((object)['error' => '缺少或不合法的 id（請用 ?id=123 或 ?coupon_id=123）'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 解析 JSON Body =====
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode((object)['error' => '請提供 JSON 格式的資料'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 組 UPDATE 語句（直接 query 版本） =====
$setParts = [];
foreach ($PATCHABLE as $field) {
  if (array_key_exists($field, $data)) {
    if (is_null($data[$field])) {
      $setParts[] = "$field = NULL";
    } else {
      // SQL Injection 基本防護：escape + 數字不加引號
      $val = $data[$field];

      // 明確把數字型欄位轉 int（更穩）
      if (in_array($field, ['quiz_id', 'value', 'min_order_amount', 'valid_days'], true)) {
        if ($val === '' || $val === false) {
          // 空字串/false 視為 NULL
          $setParts[] = "$field = NULL";
        } else {
          $num = is_numeric($val) ? (int)$val : null;
          if ($num === null) {
            http_response_code(400);
            echo json_encode((object)['error' => "欄位 $field 必須是數字"], JSON_UNESCAPED_UNICODE);
            if (isset($mysqli) && $mysqli) { $mysqli->close(); }
            exit();
          }
          $setParts[] = "$field = $num";
        }
      } else {
        // 字串欄位
        $esc = $mysqli->real_escape_string((string)$val);
        $setParts[] = "$field = '$esc'";
      }
    }
  }
}

if (empty($setParts)) {
  http_response_code(400);
  echo json_encode((object)['error' => '沒有可更新的欄位'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 確認資料是否存在 =====
$check = $mysqli->query("SELECT 1 FROM coupons WHERE coupon_id = $couponId");
if (!$check || $check->num_rows === 0) {
  http_response_code(404);
  echo json_encode((object)['error' => '找不到此 coupon'], JSON_UNESCAPED_UNICODE);
  if ($check) { $check->close(); }
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}
$check->close();

// ===== 執行 UPDATE =====
$sql = "UPDATE coupons SET " . implode(', ', $setParts) . " WHERE coupon_id = $couponId";
if (!$mysqli->query($sql)) {
  http_response_code(500);
  echo json_encode((object)['error' => '更新失敗', 'detail' => $mysqli->error], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) { $mysqli->close(); }
  exit();
}

// ===== 回傳更新後的最新資料 =====
$res = $mysqli->query("SELECT coupon_id, quiz_id, type, value, title, description, coupon_code_prefix, min_order_amount, valid_days FROM coupons WHERE coupon_id = $couponId");
$row = $res ? $res->fetch_assoc() : null;
if ($res) { $res->close(); }

echo json_encode((object)[
  'message' => '更新成功',
  'coupon'  => $row
], JSON_UNESCAPED_UNICODE);

if (isset($mysqli) && $mysqli) { $mysqli->close(); }
