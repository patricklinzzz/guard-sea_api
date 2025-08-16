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
    
  //   $sql = "UPDATE quizzes SET quiz_description = ?, question_num = ?, pass_grade = ? WHERE quiz_id = ?;";
  //   $stmt = $mysqli->prepare($sql);

  //   $stmt->bind_param("siii", 
  //   $_PATCH["quiz_description"],
  //   $_PATCH["question_num"],
  //   $_PATCH["pass_grade"],
  //   $_PATCH["quiz_id"],
  // );


  //   $stmt->execute();
    
    $quiz_description = $_PATCH["quiz_description"];
    $question_num = $_PATCH["question_num"];
    $pass_grade = $_PATCH["pass_grade"];
    $quiz_id = $_PATCH["quiz_id"];

    $sql = "UPDATE quizzes SET quiz_description = '$quiz_description', question_num = $question_num, pass_grade = $pass_grade WHERE quiz_id = $quiz_id;";

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