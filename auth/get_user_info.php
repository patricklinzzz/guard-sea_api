<?php
require_once '../common/cors.php';
require_once '../common/conn.php';
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION['member_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => '未登入或 Session 已過期。']);
  exit;
}

$member_id = $mysqli->real_escape_string($_SESSION['member_id']);

$sql = "SELECT member_id,fullname, gender, email, phone_number, address, birthday, avatar_url FROM members WHERE member_id = '$member_id'";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
  $member_data = $result->fetch_assoc();
  http_response_code(200); // OK
  echo json_encode([
    'success' => true,
    'message' => '會員資料獲取成功。',
    'member' => $member_data
  ]);
} else {
  http_response_code(404);
  echo json_encode(['success' => false, 'error' => '找不到會員資料。']);
}

$result->free();
$mysqli->close();
