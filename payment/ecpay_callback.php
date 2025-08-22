<?php
// 處理回呼的 API 不需要 session，因為這是綠界伺服器發送的請求
require_once("../common/conn.php");

// 引入 CheckMacValue 函數
require_once("./generateMacValue.php"); 

date_default_timezone_set("Asia/Taipei");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ecpayData = $_POST;
        
        $ecpayMacValue = $ecpayData["CheckMacValue"];
        unset($ecpayData["CheckMacValue"]);
        
        // 關鍵修正：宣告 MerchantID, HashKey, HashIV
        $MerchantID = "2000132";
        $HashKey = "5294y06p4m7u3d8e";
        $HashIV = "EkRm7iFT261dpevs";

        // 關鍵修正：傳入 $HashKey 和 $HashIV
        $MacValue = generateCheckMacValue($ecpayData, $HashKey, $HashIV);
        
        if ($MacValue == $ecpayMacValue) {
            $order_id = $ecpayData['MerchantTradeNo']; // 綠界回傳的訂單編號
            $payment_status_code = $ecpayData['RtnCode'];
            $transaction_id = $ecpayData['TradeNo'];
            
            if ($payment_status_code == 1) { // 假設 1 代表付款成功
                $payment_status = '已付款';
            } else {
                $payment_status = '付款失敗';
            }

            $sql = "UPDATE orders SET 
                        payment_status = '$payment_status', 
                        transaction_id = '$transaction_id' 
                    WHERE order_id = '$order_id'";
            $mysqli->query($sql);
            
            echo "1|OK";
        } else {
            echo "0|NOTOK";
        }

    } catch (Exception $e) {
        echo "0|NOTOK";
    } finally {
        $mysqli->close();
    }
    exit();
} else {
    echo "請使用 POST 方法提交資料。";
}
?>