<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    try {
        if (!isset($_SESSION['member_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $member_id = $_SESSION['member_id'];
        
        $sql = "SELECT 
                    mc.member_coupon_id AS id,
                    mc.coupon_code AS code,
                    mc.expiration_date AS validityPeriod,
                    mc.status,
                    c.value,
                    c.title,
                    c.min_order_amount
                FROM member_coupons AS mc
                JOIN coupons AS c ON mc.coupon_id = c.coupon_id
                WHERE mc.member_id = '$member_id'
                ORDER BY mc.expiration_date DESC";

        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new Exception("優惠券查詢失敗: " . $mysqli->error);
        }

        $response_data = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(["coupons" => $response_data]);

        $mysqli->close();
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
    }
}

http_response_code(403);
$reply_data = new stdClass();
$reply_data->error = "拒絕存取";
echo json_encode($reply_data);
?>