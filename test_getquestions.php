<?php
  require_once('./common/cors.php');
  require_once('./common/conn.php');

  $sql="SELECT * FROM questions";
  $result =$mysqli->query($sql);
  $data = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode($data);
  $mysqli->close()
?>