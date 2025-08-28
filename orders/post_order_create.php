<?php
session_start();
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../payment/generateMacValue.php");

header("Content-Type: application/json; charset=UTF-8");
//手動載入 .env
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return false;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, "' \t\n\r\0\x0B\"");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $name . '="' . $value . '"'));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}
loadEnv(__DIR__ . '/../.env');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $mysqli->autocommit(FALSE);

    try {
        if (!isset($_SESSION['member_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "未授權，請先登入"]);
            exit();
        }
        $member_id = $_SESSION['member_id'];

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['payment_method'], $data['receiver_name'], $data['receiver_address'], $data['receiver_phone'], $data['contact_phone'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少必填的訂單資訊"]);
            exit();
        }

        $member_coupon_id = isset($data['coupon_id']) ? mysqli_real_escape_string($mysqli, $data['coupon_id']) : null;
        $payment_method = mysqli_real_escape_string($mysqli, $data['payment_method']);
        $receiver_name = mysqli_real_escape_string($mysqli, $data['receiver_name']);
        $receiver_phone = mysqli_real_escape_string($mysqli, $data['receiver_phone']);
        $receiver_address = mysqli_real_escape_string($mysqli, $data['receiver_address']);
        $contact_phone = mysqli_real_escape_string($mysqli, $data['contact_phone']);
        $notes = isset($data['notes']) ? mysqli_real_escape_string($mysqli, $data['notes']) : null;

        $order_date = date('Y-m-d H:i:s');
        $order_status = '處理中';
        $payment_status = '未付款';
        $transaction_id = null;

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
        $linepay_products = [];
        while ($row = $cart_result->fetch_assoc()) {
            $cart_items[] = $row;
            $price = (float)$row['price'];
            $quantity = (int)$row['quantity'];
            $subtotal_amount += $price * $quantity;
            $item_names[] = $row['name'];
            $linepay_products[] = [
                "id" => (string)$row['product_id'],
                "name" => $row['name'],
                "quantity" => $quantity,
                "price" => $price
            ];
        }
        $subtotal_amount = (int)$subtotal_amount;
        $shipping_fee = (int)60;
        $discount_amount = (int)0;
        $final_amount = $subtotal_amount + $shipping_fee - $discount_amount;
        $linepay_products_total = 0;
        foreach ($linepay_products as $product) {
            $linepay_products_total += $product['price'] * $product['quantity'];
        }
        
        $coupon_id = null;
        if ($member_coupon_id) {
            $coupon_fetch_sql = "SELECT coupon_id FROM member_coupons WHERE member_coupon_id = '$member_coupon_id'";
            $coupon_result = $mysqli->query($coupon_fetch_sql);
            if ($coupon_result && $coupon_result->num_rows > 0) {
                $coupon_row = $coupon_result->fetch_assoc();
                $coupon_id = $coupon_row['coupon_id'];
            } else {
                throw new Exception("無效的優惠券或已使用");
            }
        }

        $order_sql = "INSERT INTO orders (member_id, order_date, status, shipping_address, contact_phone, coupon_id, subtotal_amount, shipping_fee, discount_amount, final_amount, payment_method, receiver_name, receiver_address, receiver_phone, payment_status, notes)
                    VALUES ('$member_id', '$order_date', '$order_status', '$receiver_address', '$contact_phone', " . ($coupon_id ? "'$coupon_id'" : 'NULL') . ", '$subtotal_amount', '$shipping_fee', '$discount_amount', '$final_amount', '$payment_method', '$receiver_name', '$receiver_address', '$receiver_phone', '$payment_status', " . ($notes ? "'$notes'" : 'NULL') . ")";

        // 修改優惠券狀態
        if ($member_coupon_id) {
            $delete_coupon_sql = "update member_coupons set status=0 where member_coupon_id = '$member_coupon_id'";
            $mysqli->query($delete_coupon_sql);

            if ($mysqli->affected_rows === 0) {
                throw new Exception("無法刪除或更新優惠券狀態: " . $mysqli->error);
            }
        }

        $mysqli->query($order_sql);
        $order_id = $mysqli->insert_id;
        if (!$order_id) {
            throw new Exception("無法建立訂單: " . $mysqli->error);
        }

        foreach ($cart_items as $item) {
            $product_id = mysqli_real_escape_string($mysqli, $item['product_id']);
            $quantity = mysqli_real_escape_string($mysqli, $item['quantity']);
            $price = mysqli_real_escape_string($mysqli, $item['price']);
            $size = mysqli_real_escape_string($mysqli, $item['size']);
            $color = mysqli_real_escape_string($mysqli, $item['color_code']);

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
        //綠界第三方支付
        if ($payment_method === 'credit_card') {
            $MerchantID = "3002607";
            $HashKey = "pwFHCqoQZGmho4w6";
            $HashIV = "EkRm7iFT261dpevs";

            $ecpayData = [
                "MerchantID" => $MerchantID,
                "MerchantTradeNo" => "GS" . time(),
                "MerchantTradeDate" => date('Y/m/d H:i:s'),
                "PaymentType" => "aio",
                "TotalAmount" => (int)$final_amount,
                "TradeDesc" => "商品訂單",
                "ItemName" => implode('#', $item_names),
                "ReturnURL" => "https://tibamef2e.com/cjd101/g1/api/payment/ecpay_callback.php", 
                "ChoosePayment" => "Credit",
                "EncryptType" => 1,
                "ClientBackURL" => "https://tibamef2e.com/cjd101/g1/front/ordercomplete?order_id=$order_id"
            ];           
            $CheckMacValue = generateCheckMacValue($ecpayData, $HashKey, $HashIV);
            $ecpayData["CheckMacValue"] = $CheckMacValue;

            $response['payment_form'] = $ecpayData;
            $response['payment_url'] = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
            $response['message'] = "訂單建立成功，準備跳轉至綠界支付頁面";
            //linepay  
        } elseif ($payment_method === 'linepay') {
            $linePayChannelId = $_ENV['LINE_PAY_CHANNEL_ID'];
            $linePayChannelSecret = $_ENV['LINE_PAY_CHANNEL_SECRET'];
            $linePayApiUrl = "https://sandbox-api-pay.line.me/v3/payments/request";
            $nonce = uniqid();
            $linePayOrderId = "GS" . $order_id . time() . uniqid();
            $updateSql = "UPDATE orders SET transaction_id = '" . $mysqli->real_escape_string($linePayOrderId) . "' WHERE order_id = '$order_id'";
            $mysqli->query($updateSql);
            if ($shipping_fee > 0) {
                $linepay_products[] = [
                    "id" => "shipping-fee",
                    "name" => "運費",
                    "quantity" => 1,
                    "price" => (float)$shipping_fee
                ];
            }
            $package_amount = $subtotal_amount + $shipping_fee;
            $requestBody = [
                "amount" => (int)$final_amount,
                "currency" => "TWD",
                "orderId" => $linePayOrderId,
                "packages" => [
                    [
                        "id" => "PKG-" . $order_id,
                        "amount" => (int)$package_amount,
                        "products" => $linepay_products,
                    ]
                ],
                "redirectUrls" => [
                    "confirmUrl" => $_ENV['LINEPAY_CONFIRM_URL'] . "?orderId=" . $linePayOrderId,

                ]
            ];
            $requestBodyJson = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signatureUrl = "/v3/payments/request";
            $signatureContent = $linePayChannelSecret . $signatureUrl . $requestBodyJson . $nonce;
            $signature = base64_encode(hash_hmac('sha256', $signatureContent, $linePayChannelSecret, true));
            $headers = [
                "Content-Type: application/json",
                "X-LINE-ChannelId: " . $linePayChannelId,
                "X-LINE-Authorization-Nonce: " . $nonce,
                "X-LINE-Authorization: " . $signature,
            ];

            $ch = curl_init($linePayApiUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyJson);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                http_response_code(500);
                echo json_encode([
                    "error" => "cURL 請求失敗",
                    "message" => "網路連線錯誤：" . $error_msg
                ]);
                exit();
            }
            curl_close($ch);
            $linePayResponse = json_decode($result, true);
            curl_close($ch);
            if ($httpcode !== 200) {
                http_response_code($httpcode);
                echo json_encode([
                    "error" => "Line Pay API 呼叫失敗",
                    "message" => "Line Pay 伺服器回傳錯誤狀態碼：" . $httpcode . "，回應：" . $result
                ]);
                exit();
            }
            if (isset($linePayResponse['returnCode']) && $linePayResponse['returnCode'] === '0000') {
                $response['redirect_url'] = $linePayResponse['info']['paymentUrl']['web'];
                $response['message'] = "訂單建立成功，準備跳轉至 Line Pay 支付頁面";
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Line Pay API 呼叫失敗",
                    "message" => $linePayResponse['returnMessage'] ?? '未知錯誤'
                ]);
                exit();
            }
        }

        //貨到付款
        else {
            $response['message'] = "訂單已建立，感謝您的訂購！";
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
