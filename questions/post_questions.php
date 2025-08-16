<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }

  if($_SERVER["REQUEST_METHOD"] == "POST"){
    $input = file_get_contents("php://input");
    $_POST = json_decode($input, true);

    $quiz_id = $_POST['quiz_id'];
    $question_description = $_POST['question_description'];
    $option_1 = $_POST['option_1'];
    $option_2 = $_POST['option_2'];
    $option_3 = $_POST['option_3'];
    $answer = $_POST['answer'];
    $explanation = $_POST['explanation'];

      $sql = "INSERT INTO questions (quiz_id, question_description, option_1, option_2, option_3, answer, explanation) VALUES ($quiz_id,'$question_description','$option_1','$option_2','$option_3',$answer,'$explanation')";

      // $stmt = $mysqli->prepare($sql);

      // $stmt->bind_param("issssis", 
      // $_POST['quiz_id'], 
      // $_POST['question_description'], 
      // $_POST['option_1'], 
      // $_POST['option_2'], 
      // $_POST['option_3'], 
      // $_POST['answer'], 
      // $_POST['explanation']);

      // $stmt->execute();
      
      $result = $mysqli->query($sql);
      $reply_data= ["id" => $mysqli->insert_id];
      echo json_encode($reply_data);

    exit();
  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取。";
  echo json_encode($reply_data);
?>