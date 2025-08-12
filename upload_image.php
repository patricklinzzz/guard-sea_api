<?php
require_once("./common/cors.php");
require_once("./common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
        http_response_code(400); 
        echo json_encode(["error" => "檔案上傳失敗或沒有檔案"]);
        exit();
    }

    $upload_type = $_POST['type'] ?? 'general';
    // 修正上傳路徑
    $upload_dir = "../uploads/" . $upload_type . "/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = basename($_FILES["file"]["name"]);
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_file_name = uniqid() . "." . $file_extension;
    $target_file = $upload_dir . $unique_file_name;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // 修正公開 URL 路徑
        $public_url = "/php/uploads/" . $upload_type . "/" . $unique_file_name;
        http_response_code(200);
        echo json_encode(["message" => "檔案上傳成功", "url" => $public_url]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "檔案移動失敗"]);
    }

    $mysqli->close();
    exit();
}

http_response_code(403);
echo json_encode(["error" => "拒絕存取"]);
?>