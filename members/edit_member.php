<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  require_once("../coverimage.php"); 
  session_start();
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }
  
  if($_SERVER['REQUEST_METHOD'] == "POST"){

    $member_id = $_SESSION["member_id"];
    // $input = file_get_contents("php://input");
    // $_PATCH = json_decode($input, true);
    $get_url = "SELECT avatar_url FROM members WHERE member_id = $member_id;";
    $res1 = $mysqli->query($get_url);
    $avatar = $res1->fetch_assoc();
    $avatar_pre = $avatar["avatar_url"];

    $username = $_POST["name"]; 
    $phone_number = $_POST["phone"];
    $gender = $_POST["gender"];
    $address = $_POST["address"];
    $birthday = $_POST["birthdate"];
    $file_uploaded = isset($_POST["avatar_url"]);
    if($file_uploaded){
        $reset = $_POST["avatar_url"] == 'reset';
    } else
      $reset = false;

    $image_url_for_db = $reset ? '' : handle_cover_image_upload(
      'avatar_url',
      'member/', 
      "member_id=$member_id" . "_"
    );
    if(!$reset && !empty($image_url_for_db)){
      $avatar_res = $image_url_for_db;
      if(!empty($avatar_pre))
        unlink('../' . $avatar_pre);
    } 
    else if($reset){
      $avatar_res = '';
      if(!empty($avatar_pre))
        unlink('../' . $avatar_pre);
    }  
    else $avatar_res = $avatar_pre;

    $sql_pre = "UPDATE members SET 
    fullname = '$username', phone_number = '$phone_number', gender = '$gender', address = '$address', avatar_url = '$avatar_res'";
    if(($birthday === 'null')){
      $sql = $sql_pre . "WHERE member_id = $member_id;";
    } else{
      $sql = $sql_pre . ", birthday = '$birthday' WHERE member_id = $member_id;";
    }


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