<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
session_start();
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
  echo json_encode(['status' => 'error', 'message' => '方法不被允許（只接受 POST）'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// 讀 JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!is_array($data)) $data = [];

// 取得 member_id：優先 Session，其次 body
$member_id = 0;
if (isset($_SESSION['member_id'])) {
  $member_id = intval($_SESSION['member_id']);
} elseif (isset($data['member_id'])) {
  $member_id = intval($data['member_id']);
}

if ($member_id <= 0) {
  echo json_encode(['status' => 'error', 'message' => '報名失敗: 尚未登入或缺少有效的 member_id'], JSON_UNESCAPED_UNICODE);
  if (isset($mysqli) && $mysqli) $mysqli->close();
  exit();
}

// === 報名流程 ===

// 取活動 ID
$activity_id = intval($data['activity_id'] ?? 0);
$contact_person = $mysqli->real_escape_string($data['contact_person'] ?? '');
$phone = $mysqli->real_escape_string($data['phone'] ?? '');
$contact_phone = $mysqli->real_escape_string($data['contact_phone'] ?? '');
$notes = $mysqli->real_escape_string($data['notes'] ?? '');

// 簡單檢查
if ($activity_id <= 0 || $phone === '') {
  echo json_encode(['status' => 'error', 'message' => '缺少必要的報名資訊'], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit();
}

// 檢查會員是否已經報名過此活動
$check_sql = "SELECT COUNT(*) AS count FROM activity_registrations WHERE member_id = $member_id AND activity_id = $activity_id";
$check_result = $mysqli->query($check_sql);
if ($check_result) {
  $row = $check_result->fetch_assoc();
  if ($row['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => '您已經報名過此活動，無需重複報名！'], JSON_UNESCAPED_UNICODE);
    $mysqli->close();
    exit();
  }
} else {
  // 處理查詢失敗的情況
  echo json_encode(['status' => 'error', 'message' => '查詢報名紀錄時發生錯誤: ' . $mysqli->error], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit();
}

// 插入資料庫
$sql = "
  INSERT INTO activity_registrations
    (member_id, activity_id, registration_date, notes, phone, contact_person, contact_phone)
  VALUES
    ($member_id, $activity_id, NOW(), '$notes', '$phone', '$contact_person', '$contact_phone')
";

if ($mysqli->query($sql)) {
  echo json_encode(['status' => 'success', 'message' => '報名成功！'], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['status' => 'error', 'message' => '資料庫錯誤: ' . $mysqli->error], JSON_UNESCAPED_UNICODE);
}

$mysqli->close();
