<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){

    $sql = "SELECT * FROM questions;";
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();

    $stmt->bind_result($question_id, $quiz_id, $question_description,$option_1, $option_2, $option_3, $answer, $explanation);

    $response_data = []; 

    while ($stmt->fetch()) {
        $row = [
            'question_id' => $question_id,
            'quiz_id' => $quiz_id,
            'question_description' => $question_description,
            'option_1' => $option_1,
            'option_2' => $option_2,
            'option_3' => $option_3,
            'answer' => $answer,
            'explanation' => $explanation
        ];
        $response_data[] = $row; 
    }
    $stmt->close();

    echo json_encode($response_data);
    $mysqli->close();
    exit();

  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);


?>

