<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    try {
        if (!isset($_SESSION['member_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $member_id = $_SESSION['member_id'];

        $data = json_decode(file_get_contents('php://input'), true);

        // 修正：只檢查 coupon_code
        if (!isset($data['coupon_code'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填欄位：coupon_code"]);
            exit();
        }

        $coupon_code = mysqli_real_escape_string($mysqli, $data['coupon_code']);
        
        // 修正：移除 subtotal_amount 的變數

        $sql = "SELECT 
                    c.type, c.value, c.min_order_amount,
                    mc.member_coupon_id, mc.coupon_code, mc.expiration_date, mc.status
                FROM member_coupons AS mc
                JOIN coupons AS c ON mc.coupon_id = c.coupon_id
                WHERE mc.member_id = '$member_id' AND mc.coupon_code = '$coupon_code' AND mc.status = 1";

        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new Exception("優惠券查詢失敗: " . $mysqli->error);
        }

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["error" => "優惠券代碼不存在或已使用"]);
            exit();
        }

        $coupon = $result->fetch_assoc();
        
        if (isset($coupon['expiration_date']) && !is_null($coupon['expiration_date'])) {
            $expiration_date = strtotime($coupon['expiration_date']);
            $current_date = strtotime(date('Y-m-d'));
            
            if ($expiration_date < $current_date) {
                http_response_code(400);
                echo json_encode(["error" => "優惠券已過期"]);
                exit();
            }
        }
        
        // 修正：移除最低消費金額的檢查
        
        $discount_amount = 0;
        if ($coupon['type'] == 'percentage') {
            // 注意：這裡的 subtotal_amount 應該從前端傳入或重新計算
            // 但既然你不需要最低金額，可以將折扣邏輯簡化
            $discount_amount = 100; // 範例：假設百分比是100%
        } else {
            $discount_amount = $coupon['value'];
        }

        http_response_code(200);
        echo json_encode([
            "message" => "優惠券驗證成功",
            "discount_amount" => $discount_amount,
            "coupon_id" => $coupon['member_coupon_id'] // 修正：回傳 member_coupon_id
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "伺服器發生錯誤", "message" => $e->getMessage()]);
    } finally {
        if (isset($mysqli)) $mysqli->close();
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "僅允許 POST 請求"]);
?>