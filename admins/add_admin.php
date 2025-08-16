<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header("Content-Type:application/json;charset=UTF-8");

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $username = $data['username'] ?? null;
  $email = $data['email'] ?? null;
  $password = $data['password'] ?? null;
  $fullname = $data['fullname'] ?? null;

  if (empty($username) || empty($email) || empty($password) || empty($fullname)) {
    http_response_code(400); 
    echo json_encode(["success" => false, "error" => "所有欄位皆為必填。"]);
    exit();
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Email 格式不正確。"]);
    exit();
  }
  if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "密碼至少需8個字元。"]);
    exit();
  }
  if (!preg_match('/\d/', $password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "密碼需包含至少1個數字。"]);
    exit();
  }
  if (!preg_match('/[-!@#$%^&*()_+=[\]{};\':"\\|,.<>?\/]/', $password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "密碼需包含至少1個特殊符號。"]);
    exit();
  }

  try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $username = $mysqli->real_escape_string($username);
    $password = $mysqli->real_escape_string($hashed_password);
    $email = $mysqli->real_escape_string($email);
    $fullname = $mysqli->real_escape_string($fullname);

    $sql = "INSERT INTO administrators (username,password,email,fullname) VALUE ('" . $username . "','" . $hashed_password . "','" . $email . "','" . $fullname . "')";
    $result = $mysqli->query($sql);
    if ($result === false) {
      http_response_code(500);
      echo json_encode(["success" => false, "error" => "執行新增失敗: " . $mysqli->error]);
      $mysqli->close();
      exit();
    }
    if ($mysqli->affected_rows > 0) {
      echo json_encode(["success" => true, "message" => "管理員新增成功！"]);
    } else {
      echo json_encode(["success" => false, "error" => "新增管理員失敗，沒有資料被插入。"]);
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
  header("Allow: POST, OPTIONS");
  echo json_encode(["success" => false, "error" => "僅允許 POST 和 OPTIONS 請求。"]);
  exit();
}
