<?php
// require_once("../common/cors.php");
// require_once("../common/conn.php");

// header("Content-Type: application/json; charset=UTF-8");


// if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
//   header("Access-Control-Allow-Methods: POST, OPTIONS");
//   header("Access-Control-Allow-Headers: Content-Type");
//   http_response_code(204);
//   if (isset($mysqli) && $mysqli) $mysqli->close();
//   exit();
// }


// if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
//   http_response_code(405);
//   echo json_encode(['success' => false, 'error' => '方法不被允許（只接受 POST）'], JSON_UNESCAPED_UNICODE);
//   if (isset($mysqli) && $mysqli) $mysqli->close();
//   exit();
// }


// $input = file_get_contents('php://input');
// $data  = json_decode($input, true);
// if (!$data) {
//   echo json_encode(['success' => false, 'error' => '請以 JSON 傳入參數。'], JSON_UNESCAPED_UNICODE);
//   $mysqli->close();
//   exit();
// }


// if (empty($data['member_id'])) {
//   echo json_encode(['success' => false, 'error' => 'member_id 為必填。'], JSON_UNESCAPED_UNICODE);
//   $mysqli->close();
//   exit();
// }
// $member_id = intval($data['member_id']);
// $coupon_id = isset($data['coupon_id']) ? intval($data['coupon_id']) : 1;


// $chkM = $mysqli->query("SELECT 1 FROM members WHERE id = $member_id OR member_id = $member_id LIMIT 1");
// if (!$chkM || !$chkM->num_rows) {
//   if ($chkM) $chkM->free();
//   echo json_encode(['success' => false, 'error' => '查無此會員。'], JSON_UNESCAPED_UNICODE);
//   $mysqli->close();
//   exit();
// }
// $chkM->free();


// $sql_check = "
//   SELECT 1
//   FROM member_coupons
//   WHERE member_id = $member_id
//     AND coupon_id = $coupon_id
//     AND (expiration_date IS NULL OR expiration_date >= CURDATE())
//   LIMIT 1
// ";
// $res = $mysqli->query($sql_check);
// if ($res && $res->num_rows > 0) {
//   $res->free();
//   echo json_encode(['success' => true, 'already_issued' => true, 'message' => '已有未到期（含無期限）的優惠券，未重複發放。'], JSON_UNESCAPED_UNICODE);
//   $mysqli->close();
//   exit();
// }
// if ($res) $res->free();


// $prefix = 'WELCOME';
// $cfg = $mysqli->query("SELECT coupon_code_prefix FROM coupons WHERE coupon_id = $coupon_id LIMIT 1");
// if ($cfg && $cfg->num_rows) {
//   $row = $cfg->fetch_assoc();
//   if (!empty($row['coupon_code_prefix'])) $prefix = $row['coupon_code_prefix'];
//   $cfg->free();
// }
// $prefix = strtoupper(trim($prefix));

// do {
//   $rand_hex    = strtoupper(bin2hex(random_bytes(2)));
//   $coupon_code = $prefix . '-' . $rand_hex;
//   $dup         = $mysqli->query("SELECT 1 FROM member_coupons WHERE coupon_code = '$esc_code' LIMIT 1");
//   $hasDup      = ($dup && $dup->num_rows > 0);
//   if ($dup) $dup->free();
// } while ($hasDup);


// $sql_insert = "
//   INSERT INTO member_coupons
//     (member_id, coupon_id, coupon_code, start_date, expiration_date, status)
//   VALUES
//     ($member_id, $coupon_id, '$esc_code', NOW(), NULL, 0)
// ";
// $ok = $mysqli->query($sql_insert);

// if ($ok) {
//   echo json_encode([
//     'success' => true,
//     'message' => '發券成功！',
//     'coupon' => [
//       'member_id'       => $member_id,
//       'coupon_id'       => $coupon_id,
//       'coupon_code'     => $coupon_code,
//       'start_date'      => date('Y-m-d H:i:s'),
//       'expiration_date' => null, 
//       'status'          => 0
//     ]
//   ], JSON_UNESCAPED_UNICODE);
// } else {
//   error_log('發券失敗: ' . $mysqli->error);
//   echo json_encode(['success' => false, 'error' => '發券失敗，請稍後再試。'], JSON_UNESCAPED_UNICODE);
// }

$mysqli->close(); --> -->
