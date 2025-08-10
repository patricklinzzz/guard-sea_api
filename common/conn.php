<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  
  $db_host = '127.0.0.1';
  $db_user = 'root';
  $db_password = '';
  $db_dbname = 'GuardSea';
  $db_port = 3307;

  try {
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_dbname, $db_port);
  } catch (mysqli_sql_exception $e) {
    echo '資料庫線線錯誤：' . $e->getMessage() . '<br>';
    exit();
  }
?>