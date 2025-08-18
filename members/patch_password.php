<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  session_start();
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }
  
  if($_SERVER['REQUEST_METHOD'] == "PATCH"){

    
    $input = file_get_contents("php://input");
    $_PATCH = json_decode($input, true);
    $password = $_PATCH["oldPassword"]; 
    $member_id = $_SESSION["member_id"];

    $confirm_pwd = "SELECT password FROM members WHERE member_id = $member_id;";
    $result = $mysqli->query($confirm_pwd);
    if ($result && $result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $new_pwd = password_hash($_PATCH['newPassword'], PASSWORD_DEFAULT);
        $sql = "UPDATE members SET password = '$new_pwd' WHERE member_id = $member_id;";
        $mysqli->query($sql);
        echo json_encode(["success" => true, "message" => "更新成功"]);
        $mysqli->close();
        exit();
      } else{
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "更新失敗"]);
        $mysqli->close();
        exit();
      }
    }
  }
  
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "denied";
  echo json_encode($reply_data);
?>