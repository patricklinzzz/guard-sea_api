<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");


header("Content-Type: application/json;charset=UTF-8");
session_unset();
session_destroy();
$params = session_get_cookie_params();
setcookie(
  session_name(),
  '',
  time() - 42000,
  $params["path"],
  $params["domain"],
  $params["secure"],
  $params["httponly"]
);
echo json_encode(["success" => true, "message" => "您已登出"]);
$mysqli->close();
