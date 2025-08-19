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

    $username = $_PATCH["name"]; 
    $phone_number = $_PATCH["phone"];
    $gender = $_PATCH["gender"];
    $address = $_PATCH["address"];
    $birthday = $_PATCH["birthdate"];
    $member_id = $_SESSION["member_id"];

    $sql = "UPDATE members SET 
    username = '$username', phone_number = '$phone_number', gender = '$gender', address = '$address', birthday = '$birthday' WHERE member_id = $member_id;";

    $result = $mysqli->query($sql);



    
    $reply_data = ["result" => "更新成功"];
    echo json_encode($reply_data);

    $mysqli->close();

    exit();
  }
  
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "denied";
  echo json_encode($reply_data);
?>