<?php
  require_once('../common/cors.php');
  require_once('../common/conn.php');

  if($_SERVER["REQUEST_METHOD"] == "GET"){
    $sql = "select COUNT(*) from members";
    $result = $mysqli->query($sql);
    $member_count = $result->fetch_column();

    $sql = "select COUNT(*) from orders where date(order_date) = CURDATE()";
    $result = $mysqli->query($sql);
    $orders_today = $result ->fetch_column();

    $sql = "select COUNT(*) from activity_registrations where date(registration_date ) = CURDATE();";
    $result = $mysqli->query($sql);
    $registrations_today = $result ->fetch_column();

    $sql = "select COUNT(*) from activities where status = '進行中'";
    $result = $mysqli->query($sql);
    $activities = $result ->fetch_column();

    $data = [
      'member_count' => $member_count,
      'orders_today' => $orders_today,
      'registrations_today' => $registrations_today,
      'activities' => $activities,
    ];

    echo json_encode($data);
    $mysqli->close();
    exit();
  }
?>