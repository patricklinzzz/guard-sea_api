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

        $sql = "SELECT fullname, phone_number, address, email FROM members WHERE member_id = '$member_id'";
        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new Exception("會員資料查詢失敗: " . $mysqli->error);
        }

        if ($result->num_rows === 0) {
            http_response_code(404); 
            echo json_encode(["error" => "找不到該會員資料"]);
            exit();
        }

        $member_data = $result->fetch_assoc();

        http_response_code(200);
        echo json_encode(["message" => "會員資料獲取成功", "member" => $member_data]);

    } catch (Exception $e) {
        http_response_code(500); 
        echo json_encode([
            "error" => "伺服器發生錯誤",
            "message" => $e->getMessage()
        ]);
    } finally {
        if (isset($mysqli)) {
            $mysqli->close();
        }
    }
    exit();
}

http_response_code(405); 
echo json_encode(["error" => "僅允許 GET 請求"]);
?>