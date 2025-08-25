<?php
session_start();
require_once("../common/conn.php");

//手動載入 .env
function loadEnv($filePath) {
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
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}
loadEnv(__DIR__ . '/../.env'); 

$linePayChannelId = $_ENV['LINE_PAY_CHANNEL_ID'];
$linePayChannelSecret = $_ENV['LINE_PAY_CHANNEL_SECRET'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['transactionId']) && isset($_GET['orderId'])) {
        $linePayOrderId = $_GET['orderId'];
        $sql = "SELECT order_id FROM orders WHERE transaction_id = '" . $mysqli->real_escape_string($linePayOrderId) . "'";
        $result = $mysqli->query($sql);
    
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $internalOrderId = $row['order_id'];
        } else {
            http_response_code(404);
            error_log("Line Pay GET: Order ID not found - " . $linePayOrderId);
            header("Location: " . $_ENV['LINEPAY_CLIENT_BACK_URL'] . "?status=error");
            exit();
        }
        
        error_log("DEBUG: GET request received. Redirecting with orderId: " . $internalOrderId);
    
        header("Location: " . $_ENV['LINEPAY_CLIENT_BACK_URL'] . "?order_id=" . $internalOrderId);
        ob_end_flush();
        exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['transactionId']) && isset($_GET['orderId'])) {
    $linePayOfficialTransactionId = $_GET['transactionId'];
    $linePayOrderId = $_GET['orderId'];

    $linePayConfirmUrl = "https://sandbox-api-pay.line.me/v3/payments/" . $linePayOfficialTransactionId . "/confirm";

    $sql = "SELECT order_id, final_amount FROM orders WHERE transaction_id = '" . $mysqli->real_escape_string($linePayOrderId) . "'";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderId = $row['order_id'];
        $orderAmount = $row['final_amount'];
    } else {
        http_response_code(404);
        error_log("Line Pay confirm: Line Pay Order ID not found - " . $linePayOrderId);
        $mysqli->close();
        exit();
    }

    $confirmBody = [
        "amount" => (int)$orderAmount,
        "currency" => "TWD"
    ];
    error_log("DEBUG: Confirmation Amount -> " . (int)$orderAmount);
    
    $confirmBodyJson = json_encode($confirmBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $nonce = uniqid();
    $signatureUrl = "/v3/payments/" . $linePayOfficialTransactionId . "/confirm";
    $signatureContent = $linePayChannelSecret . $signatureUrl . $confirmBodyJson . $nonce;
    $signature = base64_encode(hash_hmac('sha256', $signatureContent, $linePayChannelSecret, true));

    $headers = [
        "Content-Type: application/json",
        "X-LINE-ChannelId: " . $linePayChannelId,
        "X-LINE-Authorization-Nonce: " . $nonce,
        "X-LINE-Authorization: " . $signature,
    ];

    $ch = curl_init($linePayConfirmUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $confirmBodyJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if(curl_errno($ch)) {
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
    $linePayConfirmResponse = json_decode($result, true);

    if ($httpcode !== 200) {
        http_response_code($httpcode);
        echo json_encode([
            "error" => "Line Pay API 呼叫失敗",
            "message" => "Line Pay 伺服器回傳錯誤狀態碼：" . $httpcode . "，回應：" . $result
        ]);
        exit();
    }

    if (isset($linePayConfirmResponse['returnCode']) && $linePayConfirmResponse['returnCode'] === '0000') {
        $updateSql = "UPDATE orders SET payment_status = '已付款', transaction_id = '" . $mysqli->real_escape_string($linePayOfficialTransactionId) . "' WHERE order_id = '" . $mysqli->real_escape_string($orderId) . "'";
        $mysqli->query($updateSql);
        http_response_code(200);
    } else {
        error_log("Line Pay confirm failed for order " . $orderId . ": " . json_encode($linePayConfirmResponse));
        http_response_code(500);
    }

} else {
    http_response_code(400);
    error_log("Line Pay confirm: Invalid request method or missing parameters.");
}
$mysqli->close();
exit();
?>