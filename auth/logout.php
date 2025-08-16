<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  session_start();

  header("Content-Type: application/json;charset=UTF-8");
  session_unset();
  session_destroy();
  echo json_encode(["success" => true, "message" => "您已登出"]);
  $mysqli->close();
?>