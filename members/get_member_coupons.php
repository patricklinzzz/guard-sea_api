<?php
require_once('../common/cors.php');
require_once('../common/conn.php');

header("Content-Type:application/json;charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $member_id = $data['member_id'];

  if ($member_id === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "缺少 memberId"]);
    exit();
  }

  try {
    $member_id = $mysqli->real_escape_string($member_id);
    $sql="select c.title,c.value,m.expiration_date from member_coupons as m inner join coupons as c on m.coupon_id =c.coupon_id where m.member_id = '$member_id' and m.status = '1'";
    $result = $mysqli->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["success" => true, "data" =>$data]);
    $mysqli->close();
    exit();
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "伺服器錯誤: " . $e->getMessage()]);
    exit();
  }
} else {
  http_response_code(400);
  header("Allow: POST");
  echo json_encode(["success" => false, "error" => "僅允許 POST 請求。"]);
  exit();
}
