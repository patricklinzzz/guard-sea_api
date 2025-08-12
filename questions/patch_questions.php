<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  // 遇到 PATCH 和 PUT 和 DELETE 這類類型，要加以下三行的 if。
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }
  
  if($_SERVER['REQUEST_METHOD'] == "PATCH"){
    // code
    $input = file_get_contents("php://input");
    $_PATCH = json_decode($input, true);
    
    // print_r($_PATCH);
    $sql = "UPDATE questions SET 
    quiz_id = ?, question_description = ?, option_1 = ?, option_2 = ?, option_3 = ?, 
    answer = ?, explanation = ?
    WHERE question_id = ?;";
    $stmt = $mysqli->prepare($sql);
    
    // 資料代入 SQL
    $stmt->bind_param("issssisi", 
    $_PATCH["quiz_id"], 
    $_PATCH["question_description"],
    $_PATCH["option_1"],
    $_PATCH["option_2"],
    $_PATCH["option_3"],
    $_PATCH["answer"],
    $_PATCH["explanation"],
    $_PATCH["question_id"],
  );

    // 執行 SQL
    $stmt->execute();
    
    // 回傳資料
    $reply_data = ["result" => "更新成功"];
    echo json_encode($reply_data);

    // 關閉資料庫連線
    $mysqli->close();
    // 程式執行結束
    exit();
  }
  
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "denied";
  echo json_encode($reply_data);
?>