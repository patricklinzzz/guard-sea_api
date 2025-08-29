<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['username']) || empty($data['name']) || empty($data['gender']) || empty($data['email']) || empty($data['password'])) {
  echo json_encode(['success' => false, 'error' => '請填寫所有必填欄位。']);
  exit;
}

$username = $mysqli->real_escape_string($data['username']);
$name = $mysqli->real_escape_string($data['name']);
$gender = $mysqli->real_escape_string($data['gender']);
$email = $mysqli->real_escape_string($data['email']);
$password = $data['password'];

$sql_check = "SELECT username, email FROM members WHERE username = '$username' OR email = '$email'";
$result_check = $mysqli->query($sql_check);

if ($result_check->num_rows > 0) {
  $row = $result_check->fetch_assoc();
  if ($row['username'] === $username) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '此帳號已被使用。']);
  } elseif ($row['email'] === $email) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '此電子郵件已被註冊。']);
  }
  $result_check->free();
  $mysqli->close();
  exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql_insert = "INSERT INTO members (username, fullname, gender, email, password) VALUES ('$username', '$name', '$gender', '$email', '$password_hash')";

// if ($mysqli->query($sql_insert) === TRUE) {
//   echo json_encode(['success' => true, 'message' => '註冊成功！']);
// } else {
//   error_log("註冊失敗: " . $mysqli->error);
//   echo json_encode(['success' => false, 'error' => '註冊失敗，請稍後再試。']);
// }
if ($mysqli->query($sql_insert) === TRUE) {
  $memberId = $mysqli->insert_id;

  // 一次發這兩張（依你的 coupons 表）
  $COUPON_IDS = [2, 1]; // 2=welcome, 1=first_purchase

  $issued = [];

  foreach ($COUPON_IDS as $cid) {
    // 已經發過就跳過（避免重複）
    $ck = $mysqli->query("SELECT 1 FROM member_coupons WHERE member_id = $memberId AND coupon_id = $cid LIMIT 1");
    $exists = ($ck && $ck->num_rows > 0);
    if ($ck) $ck->free();
    if ($exists) continue;

    // 讀 prefix 與有效天數（NULL=無期限）
    $prefix = 'WELCOME';
    $valid_days = null; // 預設無期限
    $cfg = $mysqli->query("SELECT coupon_code_prefix, valid_days FROM coupons WHERE coupon_id = $cid LIMIT 1");
    if ($cfg && $cfg->num_rows) {
      $row = $cfg->fetch_assoc();
      if (!empty($row['coupon_code_prefix'])) $prefix = $row['coupon_code_prefix'];
      if ($row['valid_days'] !== null && intval($row['valid_days']) > 0) $valid_days = intval($row['valid_days']);
      $cfg->free();
    }
    $prefix = strtoupper(trim($prefix));

    // 產券碼：PREFIX-XXXX（4 碼十六進位，大寫），確保唯一
    do {
      $rand    = strtoupper(bin2hex(random_bytes(2))); // 4 hex
      $code    = $prefix . '-' . $rand;
      $escCode = $mysqli->real_escape_string($code);
      $dup     = $mysqli->query("SELECT 1 FROM member_coupons WHERE coupon_code = '$escCode' LIMIT 1");
      $hasDup  = ($dup && $dup->num_rows > 0);
      if ($dup) $dup->free();
    } while ($hasDup);

    // 決定到期欄位（無期限→NULL；有天數→+N 天）
    $expSql = is_null($valid_days) ? "NULL" : "DATE(DATE_ADD(NOW(), INTERVAL $valid_days DAY))";

    // 寫入
    $sqlCoupon = "
      INSERT INTO member_coupons
        (member_id, coupon_id, coupon_code, start_date, expiration_date, status)
      VALUES
        ($memberId, $cid, '$escCode', NOW(), $expSql, 1)
    ";
    if ($mysqli->query($sqlCoupon)) {
      $issued[] = [
        'coupon_id'       => $cid,
        'coupon_code'     => $code,
        'start_date'      => date('Y-m-d H:i:s'),
        'expiration_date' => is_null($valid_days) ? null : date('Y-m-d', strtotime("+$valid_days days")),
        'status'          => 1
      ];
    } else {
      error_log("發券失敗 coupon_id=$cid: " . $mysqli->error);
    }
  }

  echo json_encode([
    'success'   => true,
    'message'   => '註冊成功！已發優惠券。',
    'member_id' => $memberId,
    'coupons'   => $issued  // 會是一個陣列，含兩張券的資料
  ], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit;

} else {
  error_log("註冊失敗: " . $mysqli->error);
  echo json_encode(['success' => false, 'error' => '註冊失敗，請稍後再試。'], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit;
}
