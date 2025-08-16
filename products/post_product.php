<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php"); 

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    try {
        $data = $_POST;

        if (!isset($data['sku'], $data['name'], $data['price'], $data['description'], $data['content'], $data['category_id'], $data['status'])) {
            http_response_code(400); 
            echo json_encode(["error" => "缺少必填欄位"]);
            exit();
        }

        $main_image_url = handle_cover_image_upload('main_image_file', 'products/', 'prod_');
        $sub_image_1 = handle_cover_image_upload('sub_image_1_file', 'products/', 'prod_');
        $sub_image_2 = handle_cover_image_upload('sub_image_2_file', 'products/', 'prod_');
        $sub_image_3 = handle_cover_image_upload('sub_image_3_file', 'products/', 'prod_');
        
        $sku = mysqli_real_escape_string($mysqli, $data['sku']);
        $name = mysqli_real_escape_string($mysqli, $data['name']);
        $description = mysqli_real_escape_string($mysqli, $data['description']);
        $content = mysqli_real_escape_string($mysqli, $data['content']);
        $price = mysqli_real_escape_string($mysqli, $data['price']);
        $category_id = mysqli_real_escape_string($mysqli, $data['category_id']);
        $status = mysqli_real_escape_string($mysqli, $data['status']);
        $size = mysqli_real_escape_string($mysqli, $data['size'] ?? '');
        $color_code = mysqli_real_escape_string($mysqli, $data['color_code'] ?? '');

        $main_image_url_sql = $main_image_url ? "'" . mysqli_real_escape_string($mysqli, $main_image_url) . "'" : "''";
        $sub_image_1_sql = $sub_image_1 ? "'" . mysqli_real_escape_string($mysqli, $sub_image_1) . "'" : "''";
        $sub_image_2_sql = $sub_image_2 ? "'" . mysqli_real_escape_string($mysqli, $sub_image_2) . "'" : "''";
        $sub_image_3_sql = $sub_image_3 ? "'" . mysqli_real_escape_string($mysqli, $sub_image_3) . "'" : "''";
        

        $sql = "INSERT INTO products (sku, name, description, content, price, main_image_url, sub_image_1, sub_image_2, sub_image_3, size, color_code, category_id, status)
                VALUES (
                    '$sku', '$name', '$description', '$content', '$price', 
                    $main_image_url_sql, $sub_image_1_sql, $sub_image_2_sql, $sub_image_3_sql, 
                    '$size', '$color_code', '$category_id', '$status'
                )";
        
        $result = $mysqli->query($sql);

        if ($result) {
            http_response_code(201); 
            echo json_encode(["message" => "商品新增成功", "product_id" => $mysqli->insert_id]);
        } else {
            
            throw new mysqli_sql_exception("資料庫查詢失敗: " . $mysqli->error);
        }

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

http_response_code(403);
echo json_encode(["error" => "拒絕存取"]);
?>