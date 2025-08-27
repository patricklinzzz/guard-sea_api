<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
session_start(); // 一定要在任何輸出前
header("Content-Type: application/json; charset=UTF-8");

// CORS 預檢
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  header("Access-Control-Allow-Methods: POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  http_response_code(204);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// 只允許 POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => '方法不被允許（只接受 POST）'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// 讀 JSON（若不是合法 JSON，就當成空陣列；讓 Session 也能單獨運作）
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (!is_array($data)) $data = [];

// 取得 member_id：優先用 Session，其次才看 body
$member_id = 0;
if (isset($_SESSION['member_id'])) {
  $member_id = intval($_SESSION['member_id']);
} elseif (isset($data['member_id'])) {
  $member_id = intval($data['member_id']); // 方便用 Postman 測或後台直發
}

if ($member_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'尚未登入或缺少有效的 member_id'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// coupon 一律只發 3（THANKYOU60）
$ENFORCED_COUPON_ID = 3;
if (isset($data['coupon_id']) && intval($data['coupon_id']) !== $ENFORCED_COUPON_ID) {
  echo json_encode(['success'=>false,'error'=>'此 API 只允許發 coupon_id = 3'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}
$coupon_id = $ENFORCED_COUPON_ID;

// 檢查會員存在
$chkM = $mysqli->query("SELECT 1 FROM members WHERE member_id = $member_id LIMIT 1");
if (!$chkM || !$chkM->num_rows) {
  if ($chkM) $chkM->free();
  echo json_encode(['success'=>false,'error'=>'查無此會員。'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}
$chkM->free();

// 讀 prefix 與 valid_days（NULL/<=0 視為無期限）
$prefix = 'EVENT';
$valid_days = null;
$cfg = $mysqli->query("SELECT coupon_code_prefix, COALESCE(valid_days, 0) AS vd FROM coupons WHERE coupon_id = $coupon_id LIMIT 1");
if ($cfg && $cfg->num_rows) {
  $row = $cfg->fetch_assoc();
  if (!empty($row['coupon_code_prefix'])) $prefix = $row['coupon_code_prefix']; // 你這張應該是 THANKYOU60
  $vd = intval($row['vd']);
  if ($vd > 0) $valid_days = $vd; // 例如 88 天
  $cfg->free();
}
$prefix = strtoupper(trim($prefix));

// 產唯一券碼：PREFIX-XXXX（4 碼十六進位，大寫）
do {
  $rand_hex    = strtoupper(bin2hex(random_bytes(2))); // 4 hex
  $coupon_code = $prefix . '-' . $rand_hex;            // e.g. THANKYOU60-5F9D
  $esc_code    = $mysqli->real_escape_string($coupon_code);
  $dup         = $mysqli->query("SELECT 1 FROM member_coupons WHERE coupon_code = '$esc_code' LIMIT 1");
  $hasDup      = ($dup && $dup->num_rows > 0);
  if ($dup) $dup->free();
} while ($hasDup);

// 到期（無期限→NULL；有天數→+N天）
$expSql = is_null($valid_days) ? "NULL" : "DATE(DATE_ADD(NOW(), INTERVAL $valid_days DAY))";

// 寫入（直接發）
$sql = "
  INSERT INTO member_coupons
    (member_id, coupon_id, coupon_code, start_date, expiration_date, status)
  VALUES
    ($member_id, $coupon_id, '$esc_code', NOW(), $expSql, 1)
";
$ok = $mysqli->query($sql);

if ($ok) {
  echo json_encode([
    'success' => true,
    'message' => '發券成功（THANKYOU60）。',
    'coupon' => [
      'member_id'       => $member_id,
      'coupon_id'       => $coupon_id,
      'coupon_code'     => $coupon_code,
      'start_date'      => date('Y-m-d H:i:s'),
      'expiration_date' => is_null($valid_days) ? null : date('Y-m-d', strtotime("+$valid_days days")),
      'status'          => 0
    ]
  ], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit();
} else {
  error_log('發券失敗: ' . $mysqli->error);
  echo json_encode(['success'=>false,'error'=>'發券失敗，請稍後再試。'], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit();
}
