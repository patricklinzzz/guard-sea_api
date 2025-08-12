<?php
  require_once("./common/cors.php");
  require_once("./common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){
    $response_data = new stdClass();

    $sql = "SELECT * FROM questions;";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $response_data->questions = $questions;

    $sql = "SELECT quiz_id, title FROM quizzes;";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $quiz_title = $result->fetch_all(MYSQLI_ASSOC);
    $response_data->quiz_title = $quiz_title;

    echo json_encode($response_data);
    $mysqli->close();
    exit();

  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);
?>