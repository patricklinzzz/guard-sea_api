<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){
    header("Content-Type: application/json; charset=UTF-8");

    $sql = "SELECT category_id, category_name FROM product_categories";
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(["error" => "SQL 語法錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($data);

    $mysqli->close();
    exit();
  }
  http_response_code(403);
  echo json_encode(["error" => "拒絕存取"]);
?>