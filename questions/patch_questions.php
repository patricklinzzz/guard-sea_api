<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }
  
  if($_SERVER['REQUEST_METHOD'] == "PATCH"){

    $input = file_get_contents("php://input");
    $_PATCH = json_decode($input, true);
    
    // $sql = "UPDATE questions SET 
    // quiz_id = ?, question_description = ?, option_1 = ?, option_2 = ?, option_3 = ?, 
    // answer = ?, explanation = ?
    // WHERE question_id = ?;";
    // $stmt = $mysqli->prepare($sql);
    

    // $stmt->bind_param("issssisi", 
    // $_PATCH["quiz_id"], 
    // $_PATCH["question_description"],
    // $_PATCH["option_1"],
    // $_PATCH["option_2"],
    // $_PATCH["option_3"],
    // $_PATCH["answer"],
    // $_PATCH["explanation"],
    // $_PATCH["question_id"],  );
    // $stmt->execute();

    $quiz_id = $_PATCH["quiz_id"]; 
    $question_description = $_PATCH["question_description"];
    $option_1 = $_PATCH["option_1"];
    $option_2 = $_PATCH["option_2"];
    $option_3 = $_PATCH["option_3"];
    $answer = $_PATCH["answer"];
    $explanation = $_PATCH["explanation"];
    $question_id = $_PATCH["question_id"];

    $sql = "UPDATE questions SET 
    quiz_id = $quiz_id, question_description = '$question_description', option_1 = '$option_1', option_2 = '$option_2', option_3 = '$option_3', 
    answer = $answer, explanation = '$explanation'
    WHERE question_id = $question_id;";

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