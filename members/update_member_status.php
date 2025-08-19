<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
  header("Content-Type: application/json; charset=UTF-8");

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $memberId = (int)($data['id']) ?? null;
  $newStatus = $data['status'] ?? null;

  if ($memberId === null || $newStatus === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "缺少 Admin ID 或狀態。"]);
    exit();
  }

  try {
    $memberId = $mysqli->real_escape_string($memberId);
    $newStatus = (int)$newStatus;
    $sql = "UPDATE members SET status = " . $newStatus . " WHERE member_id = '" . $memberId . "'";
    $result = $mysqli->query($sql);

    if ($result === false) {
      http_response_code(500);
      echo json_encode(["success" => false, "error" => "執行更新失敗: " . $mysqli->error]);
      $mysqli->close();
      exit();
    }

    if ($mysqli->affected_rows > 0) {
      echo json_encode(["success" => true, "message" => "會員狀態更新成功。"]);
    } else {
      echo json_encode(["success" => false, "error" => "未找到該會員或狀態未改變。"]);
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
