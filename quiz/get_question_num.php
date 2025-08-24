<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){


    $sql = "SELECT COUNT(*) num FROM questions GROUP BY quiz_id;";

    $result = $mysqli->query($sql);

    $response_data = $result->fetch_all(MYSQLI_ASSOC);
    

    echo json_encode($response_data);
    $mysqli->close();
    exit();

  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);
?>