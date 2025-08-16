<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
session_start();

header("Content-Type: application/json;charset=UTF-8");

if (isset($_SESSION['isAuthenticated']) && $_SESSION['isAuthenticated'] === true) {
  echo json_encode([
    "isAuthenticated" => true,
    "user" => $_SESSION['user']
  ]);
} else {
  echo json_encode([
    "isAuthenticated" => false,
    "user" => null
  ]);
}

$mysqli->close();
