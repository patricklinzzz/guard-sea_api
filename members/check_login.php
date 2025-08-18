<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  session_start();


  if($_SERVER['REQUEST_METHOD']=="GET"){
    if(isset($_SESSION['member_id'])){ 
      echo json_encode([
        'isLoggedIn' => true,
        'member_id' => $_SESSION['member_id'],
        'fullname' => $_SESSION['fullname']
      ]);
    } else {
      echo json_encode([
          'isLoggedIn' => false
      ]);
    }

    exit();
  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);


?>