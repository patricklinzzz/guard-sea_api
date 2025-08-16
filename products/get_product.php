<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $sql = "SELECT product_id, sku, name, description, content, price, main_image_url, sub_image_1, sub_image_2, sub_image_3, size, color_code, category_id, status FROM products WHERE 1=1";

    if (isset($_GET['product_id'])) {
        $product_id = mysqli_real_escape_string($mysqli, $_GET['product_id']);
        $sql .= " AND product_id = '$product_id'";
    } else {
        if (isset($_GET['category_id'])) {
            $category_id = mysqli_real_escape_string($mysqli, $_GET['category_id']);
            $sql .= " AND category_id = '$category_id'";
        }
    
        if (isset($_GET['status'])) {
            $status = mysqli_real_escape_string($mysqli, $_GET['status']);
            $sql .= " AND status = '$status'";
        }
    }

    $result = $mysqli->query($sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode(["error" => "SQL 查詢錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode($data);
    
    $result->free();
    $mysqli->close();
    exit();
}

http_response_code(403);
echo json_encode(["error" => "拒絕存取"]);
?>