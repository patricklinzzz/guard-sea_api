<?php
// session_start();
// require_once("../common/cors.php");
// require_once("../common/conn.php");
// require_once("../payment/generateMacValue.php"); 

// header("Content-Type: application/json; charset=UTF-8");

// if ($_SERVER['REQUEST_METHOD'] == "POST") {

//     $mysqli->autocommit(FALSE);

//     try {
//         if (!isset($_SESSION['member_id'])) {
//             http_response_code(401);
//             echo json_encode(["error" => "未授權，請先登入"]);
//             exit();
//         }
//         $member_id = $_SESSION['member_id'];
        
//         $data = json_decode(file_get_contents('php://input'), true);

//         if (!isset($data['payment_method'], $data['receiver_name'], $data['receiver_address'], $data['receiver_phone'], $data['contact_phone'])) {
//             http_response_code(400);
//             echo json_encode(["error" => "缺少必填的訂單資訊"]);
//             exit();
//         }

//         $coupon_id = isset($data['coupon_id']) ? mysqli_real_escape_string($mysqli, $data['coupon_id']) : null;
//         $payment_method = mysqli_real_escape_string($mysqli, $data['payment_method']);
//         $receiver_name = mysqli_real_escape_string($mysqli, $data['receiver_name']);
//         $receiver_phone = mysqli_real_escape_string($mysqli, $data['receiver_phone']);
//         $receiver_address = mysqli_real_escape_string($mysqli, $data['receiver_address']);
        
//         // 關鍵修正：修正 contact_phone 的變數名稱
//         $contact_phone = mysqli_real_escape_string($mysqli, $data['contact_phone']);
        
//         $notes = isset($data['notes']) ? mysqli_real_escape_string($mysqli, $data['notes']) : null;

//         $order_date = date('Y-m-d H:i:s');
//         $order_status = '處理中';
//         $payment_status = '未付款';
//         $transaction_id = null;

//         $cart_sql = "SELECT ci.product_id, ci.quantity, p.name, p.price FROM cart_items AS ci JOIN products AS p ON ci.product_id = p.product_id WHERE ci.member_id = '$member_id'";
//         $cart_result = $mysqli->query($cart_sql);

//         if ($cart_result === false) {
//             throw new Exception("無法讀取購物車內容: " . $mysqli->error);
//         }
//         if ($cart_result->num_rows === 0) {
//             throw new Exception("購物車是空的，無法建立訂單");
//         }
        
//         $cart_items = [];
//         $subtotal_amount = 0;
//         $item_names = [];
//         while ($row = $cart_result->fetch_assoc()) {
//             $cart_items[] = $row;
//             $subtotal_amount += $row['price'] * $row['quantity'];
//             $item_names[] = $row['name'];
//         }

//         $shipping_fee = 60;
//         $discount_amount = 0;
//         $final_amount = $subtotal_amount + $shipping_fee - $discount_amount; 

//         // 步驟 2：建立訂單記錄
//         $order_sql = "INSERT INTO orders (member_id, order_date, status, shipping_address, contact_phone, coupon_id, subtotal_amount, shipping_fee, discount_amount, final_amount, payment_method, receiver_name, receiver_address, receiver_phone, payment_status, notes)
//                       VALUES ('$member_id', '$order_date', '$order_status', '$receiver_address', '$contact_phone', " . ($coupon_id ? "'$coupon_id'" : 'NULL') . ", '$subtotal_amount', '$shipping_fee', '$discount_amount', '$final_amount', '$payment_method', '$receiver_name', '$receiver_address', '$receiver_phone', '$payment_status', " . ($notes ? "'$notes'" : 'NULL') . ")";
        
//         $mysqli->query($order_sql);
//         $order_id = $mysqli->insert_id;
//         if (!$order_id) {
//             throw new Exception("無法建立訂單: " . $mysqli->error);
//         }
        
//         foreach ($cart_items as $item) {
//             $product_id = mysqli_real_escape_string($mysqli, $item['product_id']);
//             $quantity = mysqli_real_escape_string($mysqli, $item['quantity']);
//             $price = mysqli_real_escape_string($mysqli, $item['price']);
            
//             $order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES ('$order_id', '$product_id', '$quantity', '$price')";
//             $mysqli->query($order_item_sql);
//             if ($mysqli->affected_rows === 0) {
//                 throw new Exception("無法新增訂單商品: " . $mysqli->error);
//             }
//         }
//         $clear_cart_sql = "DELETE FROM cart_items WHERE member_id = '$member_id'";
//         $mysqli->query($clear_cart_sql);
        
//         $mysqli->commit();
//         $response = [
//             "message" => "訂單建立成功",
//             "order_id" => $order_id,
//             "final_amount" => $final_amount
//         ];
        
//         if ($payment_method === 'ecpay_credit_card' || $payment_method === 'linepay') {
//             $MerchantID = "2000132";
//             $HashKey = "5294y06p4m7u3d8e";
//             $HashIV = "EkRm7iFT261dpevs";

//             $ecpayData = [
//                 "MerchantID" => $MerchantID,
//                 "MerchantTradeNo" => "GS" . $order_id . uniqid(),
//                 "MerchantTradeDate" => date('Y/m/d H:i:s'),
//                 "PaymentType" => "aio",
//                 "TotalAmount" => $final_amount,
//                 "TradeDesc" => "商品訂單",
//                 "ItemName" => implode('#', $item_names),
//                 "ReturnURL" => "http://localhost:8888/guard-sea_api/payment/ecpay_callback.php", 
//                 "ChoosePayment" => "ALL",
//                 "EncryptType" => 1,
//                 "IgnorePayment" => "WeiXin#TWQR#BNPL#CVS#BARCODE#ATM#WebATM",
//                 "ClientBackURL" => "http://localhost:5173/payment/complete?order_id=$order_id"
//             ];
            
//             // 關鍵修正：傳入 $HashKey 和 $HashIV
//             $CheckMacValue = generateCheckMacValue($ecpayData, $HashKey, $HashIV);
//             $ecpayData["CheckMacValue"] = $CheckMacValue;
            
//             $response['payment_form'] = $ecpayData;
//             $response['payment_url'] = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
//             $response['message'] = "訂單建立成功，準備跳轉至支付頁面";
//         }

//         http_response_code(201);
//         echo json_encode($response);

//     } catch (Exception $e) {
//         $mysqli->rollback();
//         http_response_code(500);
//         echo json_encode([
//             "error" => "伺服器發生錯誤",
//             "message" => $e->getMessage()
//         ]);
//     } finally {
//         $mysqli->autocommit(TRUE);
//         if (isset($mysqli)) {
//             $mysqli->close();
//         }
//     }
//     exit();
// }

// http_response_code(405);
// echo json_encode(["error" => "僅允許 POST 請求"]);
?>
<?php
// --- 臨時除錯程式碼：強制顯示所有錯誤 ---
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ----------------------------------------
//session_start();
// require_once("../common/cors.php");
// require_once("../common/conn.php");
// require_once("../payment/generateMacValue.php"); 
require_once(__DIR__ . '/../common/cors.php'); 
require_once(__DIR__ . '/../common/conn.php'); 
require_once(__DIR__ . '/../payment/generateMacValue.php');

header("Content-Type: application/json; charset=UTF-8");

echo isset($_COOKIE['PHPSESSID']);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // $_SESSION['member_id'] = 1; // 直接為會員ID賦值，確保測試通過
    $mysqli->autocommit(FALSE);

    try {
        if (!isset($_SESSION['member_id'])) {
            $_SESSION['member_id'] = 1;// 假設你的會員ID為1
            // http_response_code(401);
            // echo json_encode(["error" => "未授權，請先登入"]);
            // exit();
        }
        $member_id = $_SESSION['member_id'];
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['payment_method'], $data['receiver_name'], $data['receiver_address'], $data['receiver_phone'], $data['contact_phone'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填的訂單資訊"]);
            exit();
        }

        $coupon_id = isset($data['coupon_id']) ? mysqli_real_escape_string($mysqli, $data['coupon_id']) : null;
        $payment_method = mysqli_real_escape_string($mysqli, $data['payment_method']);
        $receiver_name = mysqli_real_escape_string($mysqli, $data['receiver_name']);
        $receiver_phone = mysqli_real_escape_string($mysqli, $data['receiver_phone']);
        $receiver_address = mysqli_real_escape_string($mysqli, $data['receiver_address']);
        
        // 修正：修正 contact_phone 的變數名稱
        $contact_phone = mysqli_real_escape_string($mysqli, $data['contact_phone']);
        
        $notes = isset($data['notes']) ? mysqli_real_escape_string($mysqli, $data['notes']) : null;

        $order_date = date('Y-m-d H:i:s');
        $order_status = '處理中';
        $payment_status = '未付款';
        $transaction_id = null;

        // 修正：從 cart_items 中讀取 size 和 color_code 欄位
        $cart_sql = "SELECT ci.product_id, ci.quantity, ci.size, ci.color_code, p.name, p.price 
                    FROM cart_items AS ci 
                    JOIN products AS p ON ci.product_id = p.product_id 
                    WHERE ci.member_id = '$member_id'";
        $cart_result = $mysqli->query($cart_sql);

        if ($cart_result === false) {
            throw new Exception("無法讀取購物車內容: " . $mysqli->error);
        }
        if ($cart_result->num_rows === 0) {
            throw new Exception("購物車是空的，無法建立訂單");
        }
        
        $cart_items = [];
        $subtotal_amount = 0;
        $item_names = [];
        while ($row = $cart_result->fetch_assoc()) {
            $cart_items[] = $row;
            $subtotal_amount += $row['price'] * $row['quantity'];
            $item_names[] = $row['name'];
        }

        $shipping_fee = 60;
        $discount_amount = 0;
        $final_amount = $subtotal_amount + $shipping_fee - $discount_amount; 

        // 步驟 2：建立訂單記錄
        $order_sql = "INSERT INTO orders (member_id, order_date, status, shipping_address, contact_phone, coupon_id, subtotal_amount, shipping_fee, discount_amount, final_amount, payment_method, receiver_name, receiver_address, receiver_phone, payment_status, notes)
                    VALUES ('$member_id', '$order_date', '$order_status', '$receiver_address', '$contact_phone', " . ($coupon_id ? "'$coupon_id'" : 'NULL') . ", '$subtotal_amount', '$shipping_fee', '$discount_amount', '$final_amount', '$payment_method', '$receiver_name', '$receiver_address', '$receiver_phone', '$payment_status', " . ($notes ? "'$notes'" : 'NULL') . ")";
        
        $mysqli->query($order_sql);
        $order_id = $mysqli->insert_id;
        if (!$order_id) {
            throw new Exception("無法建立訂單: " . $mysqli->error);
        }
        
        foreach ($cart_items as $item) {
            $product_id = mysqli_real_escape_string($mysqli, $item['product_id']);
            $quantity = mysqli_real_escape_string($mysqli, $item['quantity']);
            $price = mysqli_real_escape_string($mysqli, $item['price']);
            
            // 修正：從購物車項目中獲取 size 和 color_code
            $size = mysqli_real_escape_string($mysqli, $item['size']);
            $color = mysqli_real_escape_string($mysqli, $item['color_code']);

            // 修正：在 INSERT 語句中新增 size 和 color_code 欄位
            $order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, size, color_code) 
                            VALUES ('$order_id', '$product_id', '$quantity', '$price', '$size', '$color')";
            $mysqli->query($order_item_sql);
            if ($mysqli->affected_rows === 0) {
                throw new Exception("無法新增訂單商品: " . $mysqli->error);
            }
        }

        $clear_cart_sql = "DELETE FROM cart_items WHERE member_id = '$member_id'";
        $mysqli->query($clear_cart_sql);
        
        $mysqli->commit();
        $response = [
            "message" => "訂單建立成功",
            "order_id" => $order_id,
            "final_amount" => $final_amount
        ];
        
        if ($payment_method === 'credit_card' || $payment_method === 'linepay') {
            $MerchantID = "2000132";
            $HashKey = "5294y06p4m7u3d8e";
            $HashIV = "EkRm7iFT261dpevs";

            $ecpayData = [
                "MerchantID" => $MerchantID,
                "MerchantTradeNo" => "GS" . $order_id . uniqid(),
                "MerchantTradeDate" => date('Y/m/d H:i:s'),
                "PaymentType" => "aio",
                "TotalAmount" => $final_amount,
                "TradeDesc" => "商品訂單",
                "ItemName" => implode('#', $item_names),
                "ReturnURL" => "https://fc28ef460f6f.ngrok-free.app/guard-sea_api/payment/ecpay_callback.php", 
                //https://tibamef2e.com/cjd101/g1/api/payment/ecpay_callback.php
                "ChoosePayment" => "ALL",
                "EncryptType" => 1,
                "IgnorePayment" => "WeiXin#TWQR#BNPL#CVS#BARCODE#ATM#WebATM",
                "ClientBackURL" => "https://41e2b5a0c739.ngrok-free.app?order_id=$order_id"
            ];//https://tibamef2e.com/cjd101/g1/ordercomplete
            //https://d0d1423ecc76.ngrok-free.app
            
            // 關鍵修正：傳入 $HashKey 和 $HashIV
            $CheckMacValue = generateCheckMacValue($ecpayData, $HashKey, $HashIV);
            echo $CheckMacValue;
            $ecpayData["CheckMacValue"] = $CheckMacValue;
            
            $response['payment_form'] = $ecpayData;
            $response['payment_url'] = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
            $response['message'] = "訂單建立成功，準備跳轉至支付頁面";
        }

        http_response_code(201);
        echo json_encode($response);

    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode([
            "error" => "伺服器發生錯誤",
            "message" => $e->getMessage()
        ]);
    } finally {
        $mysqli->autocommit(TRUE);
        if (isset($mysqli)) {
            $mysqli->close();
        }
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "僅允許 POST 請求"]);
?>