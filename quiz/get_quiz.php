<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){

    $sql = "SELECT * FROM quizzes;";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
  //   $result = $stmt->get_result();
  //   $response_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->bind_result($id, $title, $quiz_description, $question_num, $pass_grade);

      $response_data = []; // 用於儲存所有結果的陣列

      // 逐行獲取結果
      while ($stmt->fetch()) {
          // 將每一行的數據組合成一個關聯陣列 (associative array)
          $row = [
              'id' => $id,
              'title' => $title,
              'quiz_description' => $quiz_description,
              'question_num' => $question_num,
              'pass_grade' => $pass_grade
          ];
          $response_data[] = $row; // 將單行數據添加到結果陣列
      }
      // 關閉預處理語句
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