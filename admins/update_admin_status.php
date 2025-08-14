<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header("Content-Type: multipart/form-data; charset=UTF-8");

  $adminId = $_POST['administrator_id'] ?? null;
  $newStatus = $_POST['status'] ?? null;

  if ($adminId === null || $newStatus === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "缺少 Admin ID 或狀態。"]);
    exit();
  }

  try {
    $adminId = $mysqli->real_escape_string(($adminId));
    $newStatus = (int)$newStatus;
    $sql = "UPDATE administrators SET status = " . $newStatus . " WHERE administrator_id= '" . $adminId . "'";
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

    $mysqli->close(); // 關閉資料庫連線
    exit();
  } catch (Exception $e) { // 捕獲一般例外，雖然 PDOException 不會在這裡被捕獲
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "伺服器錯誤: " . $e->getMessage()]);
    // $mysqli->close(); // 如果是致命錯誤，這裡可能執行不到
    exit();
  }
} else {
  // 處理非 POST 請求
  http_response_code(405); // Method Not Allowed
  header("Allow: POST"); // 告訴客戶端只允許 POST
  echo json_encode(["success" => false, "error" => "僅允許 POST 請求。"]);
  exit();
}
