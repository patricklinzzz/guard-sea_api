<?php
//session_start();
require_once('../common/conn.php');
require_once('../common/cors.php');
header("Content-Type: application/json; charset=UTF-8");
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['username']) || empty($data['password'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => '請輸入帳號和密碼。']);
  exit;
}

$username = $mysqli->real_escape_string($data['username']);
$password = $data['password'];

$sql = "SELECT * FROM members WHERE username = '$username'";
$result = $mysqli->query($sql);

if ($result->num_rows === 0) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => '帳號或密碼不正確。']);
  exit;
}

$member = $result->fetch_assoc();
$result->free();

if (!password_verify($password, $member['password'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => '帳號或密碼不正確。']);
  exit;
}
if ($member['status'] == 0) {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "您的帳號已被停權"]);
  exit();
}

$_SESSION['member_id'] = $member['member_id'];
$_SESSION['fullname'] = $member['fullname'];

unset($member['password']);

http_response_code(200);
echo json_encode([
  'success' => true,
  'message' => '登入成功！',
  'member' => $member
]);

$mysqli->close();
