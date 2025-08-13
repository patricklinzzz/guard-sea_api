<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['sku'], $data['name'], $data['price'], $data['description'], $data['content'], $data['category_id'], $data['status'])) {
        http_response_code(400); 
        echo json_encode(["error" => "缺少必填欄位"]);
        exit();
    }

    $sql = "INSERT INTO products (sku, name, description, content, price, main_image_url, sub_image_1, sub_image_2, sub_image_3, size, color_code, category_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);

    if ($stmt === false) {
        http_response_code(500); 
        echo json_encode(["error" => "SQL 語法錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }
    $sku = $data['sku'];
    $name = $data['name'];
    $description = $data['description'];
    $content = $data['content'];
    $price = $data['price'];
    $main_image_url = $data['main_image_url'] ?? NULL;
    $sub_image_1 = $data['sub_image_1'] ?? NULL;
    $sub_image_2 = $data['sub_image_2'] ?? NULL;
    $sub_image_3 = $data['sub_image_3'] ?? NULL;
    $size = $data['size'] ?? NULL;
    $color_code = $data['color_code'] ?? NULL;
    $category_id = $data['category_id'];
    $status = $data['status'];

    $stmt->bind_param("sssisssssssii",
        $data['sku'],
        $data['name'],
        $data['description'],
        $data['content'],
        $data['price'],
        $data['main_image_url'], 
        $data['sub_image_1'] ,
        $data['sub_image_2'] ,
        $data['sub_image_3'] ,
        $data['size'] ,
        $data['color_code'],
        $data['category_id'],
        $data['status']
    );

    if ($stmt->execute()) {
        http_response_code(201); 
        echo json_encode(["message" => "商品新增成功", "product_id" => $mysqli->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "商品新增失敗: " . $stmt->error]);
    }

    $stmt->close();
    $mysqli->close();
    exit();
}

http_response_code(403);
echo json_encode(["error" => "拒絕存取"]);
?>