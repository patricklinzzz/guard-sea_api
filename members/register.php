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

$username = mysqli_real_escape_string($mysqli, $data['username']);
$name = mysqli_real_escape_string($mysqli, $data['name']);
$gender = mysqli_real_escape_string($mysqli, $data['gender']);
$email = mysqli_real_escape_string($mysqli, $data['email']);
$password = $data['password'];

$sql_check = "SELECT username, email FROM members WHERE username = '$username' OR email = '$email'";
$result_check = $mysqli->query($sql_check);

if ($result_check->num_rows > 0) {
  $row = $result_check->fetch_assoc();
  if ($row['username'] === $username) {
    echo json_encode(['success' => false, 'error' => '此帳號已被使用。']);
  } elseif ($row['email'] === $email) {
    echo json_encode(['success' => false, 'error' => '此電子郵件已被註冊。']);
  }
  $result_check->free();
  $mysqli->close();
  exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql_insert = "INSERT INTO members (username, fullname, gender, email, password) VALUES ('$username', '$name', '$gender', '$email', '$password_hash')";

if ($mysqli->query($sql_insert) === TRUE) {
  echo json_encode(['success' => true, 'message' => '註冊成功！']);
} else {
  error_log("註冊失敗: " . $mysqli->error);
  echo json_encode(['success' => false, 'error' => '註冊失敗，請稍後再試。']);
}

$mysqli->close();
