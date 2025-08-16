<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
  }

  if($_SERVER['REQUEST_METHOD'] == "DELETE"){
    
    // $sql = "DELETE FROM questions WHERE question_id = ?;";
    // $stmt = $mysqli->prepare($sql);
    // $stmt->bind_param("i", $_GET["question_id"]);
    // $stmt->execute();
    // if($stmt->affected_rows >= 1){
    //   $reply_data = [
    //     "result" => "deleted $stmt->affected_rows rows"
    //   ];
    // } else{
    //   $reply_data = [
    //     "result" => "no data deleted"
    //   ];
    // }
    // echo json_encode($reply_data);
    // $mysqli->close();

    $question_id = $_GET["question_id"];
    $sql = "DELETE FROM questions WHERE question_id = $question_id;";


    $result = $mysqli->query($sql);
    if($mysqli->affected_rows >= 1){
      $reply_data = [
        "result" => "deleted $mysqli->affected_rows rows"
      ];
    } else{
      $reply_data = [
        "result" => "no data deleted"
      ];
    }
    echo json_encode($reply_data);
    $mysqli->close();



    exit();
  }


  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "denied";
  echo json_encode($reply_data);
?>