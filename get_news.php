<?php
  require_once("./common/cors.php");
  require_once("./common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){
    $sql = "SELECT * FROM news";
    $stmt = $mysqli->prepare($sql);

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($data);

    $mysqli->close();
    exit();
  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->erroe = "拒絕存取";
  echo json_encode($reply_data);
?>