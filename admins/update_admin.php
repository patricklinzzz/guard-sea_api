<?php
require_once('../common/cors.php');
require_once('../common/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
  header("Content-Type: application/json;charset=UTF-8");

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $id = $data['administrator_id'] ?? null;
  $username = $data['username'] ?? null;
  $email = $data['email'] ?? null;
  $fullname = $data['fullname'] ?? null;

  if (empty($username) || empty($email) || empty($id) || empty($fullname)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "所有欄位皆為必填。"]);
    exit();
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Email 格式不正確。"]);
    exit();
  }

  try {
    $id = $mysqli->real_escape_string($id);
    $username = $mysqli->real_escape_string($username);
    $email = $mysqli->real_escape_string($email);
    $fullname = $mysqli->real_escape_string($fullname);

    $sql = "UPDATE administrators SET username ='" . $username . "',email = '" . $email . "',fullname='" . $fullname . "' WHERE administrator_id = '" . $id . "' ";
    $result = $mysqli->query($sql);

    // 檢查查詢是否成功執行
    if ($result === false) {
      http_response_code(500);
      echo json_encode(["success" => false, "error" => "執行更新失敗: " . $mysqli->error]);
      $mysqli->close();
      exit();
    }

    // 檢查是否有行受影響
    if ($mysqli->affected_rows > 0) {
      echo json_encode(["success" => true, "message" => "管理員狀態更新成功。"]);
    } else {
      echo json_encode(["success" => false, "error" => "未找到該管理員或狀態未改變。"]);
    }

    $mysqli->close();
    exit();
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "伺服器錯誤: " . $e->getMessage()]);
    exit();
  }
} else {
  http_response_code(405);
  header("Allow: PATCH");
  echo json_encode(["success" => false, "error" => "僅允許 PATCH 請求。"]);
  exit();
}
