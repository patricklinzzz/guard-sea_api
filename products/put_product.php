<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../coverimage.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "PUT" || ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
    
    $debug_info = [];

    try {
        $data = $_POST;
        $debug_info['post_data'] = $data;

        if (!isset($data['product_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "缺少商品ID"]);
            exit();
        }
        
        $new_main_image_url = handle_cover_image_upload('main_image_file', 'products/', 'prod_');
        $new_sub_image_1 = handle_cover_image_upload('sub_image_1_file', 'products/', 'prod_');
        $new_sub_image_2 = handle_cover_image_upload('sub_image_2_file', 'products/', 'prod_');
        $new_sub_image_3 = handle_cover_image_upload('sub_image_3_file', 'products/', 'prod_');
        
        $product_id = mysqli_real_escape_string($mysqli, $data['product_id']);
        $sku = mysqli_real_escape_string($mysqli, $data['sku']);
        $name = mysqli_real_escape_string($mysqli, $data['name']);
        $description = mysqli_real_escape_string($mysqli, $data['description']);
        $content = mysqli_real_escape_string($mysqli, $data['content']);
        $price = mysqli_real_escape_string($mysqli, $data['price']);
        $category_id = mysqli_real_escape_string($mysqli, $data['category_id']);
        $status = mysqli_real_escape_string($mysqli, $data['status']);
        $size = mysqli_real_escape_string($mysqli, $data['size'] ?? '');
        $color_code = mysqli_real_escape_string($mysqli, $data['color_code'] ?? '');

        $main_image_url_update = $new_main_image_url ? "'" . mysqli_real_escape_string($mysqli, $new_main_image_url) . "'" : (isset($data['main_image_url']) ? "'" . mysqli_real_escape_string($mysqli, $data['main_image_url']) . "'" : "''");
        $sub_image_1_update = $new_sub_image_1 ? "'" . mysqli_real_escape_string($mysqli, $new_sub_image_1) . "'" : (isset($data['sub_image_1_url']) ? "'" . mysqli_real_escape_string($mysqli, $data['sub_image_1_url']) . "'" : "''");
        $sub_image_2_update = $new_sub_image_2 ? "'" . mysqli_real_escape_string($mysqli, $new_sub_image_2) . "'" : (isset($data['sub_image_2_url']) ? "'" . mysqli_real_escape_string($mysqli, $data['sub_image_2_url']) . "'" : "''");
        $sub_image_3_update = $new_sub_image_3 ? "'" . mysqli_real_escape_string($mysqli, $new_sub_image_3) . "'" : (isset($data['sub_image_3_url']) ? "'" . mysqli_real_escape_string($mysqli, $data['sub_image_3_url']) . "'" : "''");

 
        $sql = "UPDATE products SET 
                    sku = '$sku', name = '$name', description = '$description', 
                    content = '$content', price = '$price', 
                    main_image_url = $main_image_url_update, 
                    sub_image_1 = $sub_image_1_update, 
                    sub_image_2 = $sub_image_2_update, 
                    sub_image_3 = $sub_image_3_update, 
                    size = '$size', color_code = '$color_code', 
                    category_id = '$category_id', status = '$status'
                WHERE product_id = '$product_id'";
        
        $debug_info['sql_query'] = $sql; // 將 SQL 語句儲存起來用於除錯

        $result = $mysqli->query($sql);

        if ($result) {
            if ($mysqli->affected_rows > 0) {
                http_response_code(200); 
                echo json_encode(["message" => "商品更新成功", "debug" => $debug_info]);
            } else {
                http_response_code(200);
                echo json_encode(["message" => "商品未找到或資料未變動", "debug" => $debug_info]);
            }
        } else {
            throw new mysqli_sql_exception("商品更新失敗: " . $mysqli->error);
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
http_response_code(405);
echo json_encode(["error" => "僅允許 PUT 請求"]);
?>



