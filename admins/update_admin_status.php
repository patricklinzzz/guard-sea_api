<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
  header("Content-Type: application/json; charset=UTF-8");

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $adminId = (int)($data['id']) ?? null;
  $newStatus = $data['status'] ?? null;

  if ($adminId === null || $newStatus === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "缺少 Admin ID 或狀態。"]);
    exit();
  }

  try {
    if ($adminId === 1) {
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "這是預設帳號不可停權。"]);
      exit();
    }

    $adminId = $mysqli->real_escape_string(($adminId));
    $newStatus = (int)$newStatus;
    $sql = "UPDATE administrators SET status = " . $newStatus . " WHERE administrator_id = '" . $adminId . "'";
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
