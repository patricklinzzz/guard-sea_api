<?php
require_once('../common/conn.php');
require_once('../common/cors.php');
session_start();//session方法認證

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  header("Content-Type:application/json;charset=UTF-8");

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "帳號或密碼為空"]);
    exit();
  }

  $username = $data['username'];
  $password = $data['password'];

  try {
    $username = $mysqli->real_escape_string($username);
    $password = $mysqli->real_escape_string($password);

    $sql = "SELECT administrator_id, username, password, email, fullname, status FROM administrators WHERE username = '{$username}'";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        if ($user['status'] == 0) {
          http_response_code(403); 
          echo json_encode(["success" => false, "message" => "您的帳號已被停權"]);
          exit();
        }
        $_SESSION['isAuthenticated'] = true;
        $_SESSION['user'] = [
          'id' => $user['administrator_id'],
          'account' => $user['username'],
          'email' => $user['email'],
          'name' => $user['fullname'],
          'status' => $user['status']
        ];

        http_response_code(200);
        echo json_encode([
          "success" => true,
          "message" => "登入成功",
          "user" => $_SESSION['user']
        ]);
      } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "帳號或密碼不正確(2)"]);
      }
    } else {
      http_response_code(401);
      echo json_encode(["success" => false, "message" => "帳號或密碼不正確(1)"]);
    }
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "資料庫錯誤: " . $e->getMessage()]);
  }
} else {
  http_response_code(405);
  header("Content-Type: application/json;charset=UTF-8");
  echo json_encode(["success" => false, "error" => "僅允許 POST 請求。"]);
  exit();
}
