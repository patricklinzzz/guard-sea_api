<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  session_start();

  $member_id = $_SESSION['member_id'];

  if($_SERVER['REQUEST_METHOD']=="GET"){
    $sql = "SELECT member_coupon_id id, coupon_code, expiration_date validityPeriod, value, title FROM member_coupons mc JOIN coupons c ON (mc.coupon_id = c.coupon_id) WHERE member_id = $member_id AND status = 1;";

    $result = $mysqli->query($sql);

    $response_data = $result->fetch_all(MYSQLI_ASSOC);
    

    echo json_encode($response_data);
    $mysqli->close();
    exit();

  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);
?>