<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  session_start();
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: Content-Type");
    header("HTTP/1.1 200 OK");
    exit();
  }

  if($_SERVER["REQUEST_METHOD"] == "POST"){
    $input = file_get_contents("php://input");
    $_POST = json_decode($input, true);

    $quiz_id = $_POST['quiz_id'];

    $coupon_get = "SELECT coupon_id, coupon_code_prefix, valid_days FROM coupons WHERE quiz_id = $quiz_id;";

    $res = $mysqli->query($coupon_get);
    $coupon = $res->fetch_assoc();
    $coupon_id = $coupon['coupon_id'];
    $member_id = $_SESSION["member_id"];

    $coupon_check = "SELECT DATEDIFF(expiration_date, CURDATE()) coupon_redeemable, expiration_date FROM member_coupons WHERE member_id = $member_id AND coupon_id = $coupon_id ORDER BY expiration_date DESC LIMIT 1;";
    $res1 = $mysqli->query($coupon_check);
    $coupon_redeemable = $res1->fetch_assoc();
    if($res1->num_rows == 0 || $coupon_redeemable['coupon_redeemable'] <= 0) {
      $bytes = random_bytes(4); 
      $random_code = bin2hex($bytes); 
      $coupon_code = $coupon['coupon_code_prefix'] . '-' . $random_code;

      $valid_days = $coupon['valid_days'];
  
      $coupon_post = "INSERT INTO member_coupons (member_id, coupon_id, coupon_code, expiration_date) VALUES ($member_id,$coupon_id,'$coupon_code', DATE_ADD(CURRENT_DATE(), INTERVAL $valid_days DAY));";
  
      $result = $mysqli->query($coupon_post);
        
      $reply_data= ["id" => $mysqli->insert_id, 'redeemable' => true];
      echo json_encode($reply_data);

      exit();
    } else{

      $reply_data= ['redeemable' => false, 'redeemable_date' => $coupon_redeemable['expiration_date']];
      echo json_encode($reply_data);

      exit();
    }

    
  }
  http_response_code(403);
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取。";
  echo json_encode($reply_data);
?>