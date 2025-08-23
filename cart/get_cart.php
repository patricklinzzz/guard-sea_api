<?php
// session_start();
// require_once("../common/cors.php");
// require_once("../common/conn.php");

// header("Content-Type: application/json; charset=UTF-8");

// if ($_SERVER['REQUEST_METHOD'] == "GET") {
//     try {
//         if (!isset($_SESSION['member_id'])) {
//             http_response_code(401);
//             echo json_encode(["error" => "未授權，請先登入"]);
//             exit();
//         }
//         $member_id = $_SESSION['member_id'];

//         $sql = "SELECT 
//                     ci.cart_item_id, 
//                     ci.member_id, 
//                     ci.product_id, 
//                     ci.quantity,
//                     p.name, 
//                     p.price, 
//                     p.main_image_url,
//                     p.sub_image_1,
//                     p.size,
//                     p.color_code
//                 FROM cart_items AS ci
//                 JOIN products AS p ON ci.product_id = p.product_id
//                 WHERE ci.member_id = '$member_id'";

//         $result = $mysqli->query($sql);

//         if ($result === false) {
//             throw new mysqli_sql_exception("購物車查詢失敗: " . $mysqli->error);
//         }

//         $items = [];
//         while ($row = $result->fetch_assoc()) {
//             $row['cart_item_id'] = (int)$row['cart_item_id'];
//             $row['member_id'] = (int)$row['member_id'];
//             $row['product_id'] = (int)$row['product_id'];
//             $row['quantity'] = (int)$row['quantity'];
//             $row['price'] = (float)$row['price'];
//             $items[] = $row;
//         }

//         echo json_encode(["items" => $items]);

//         $result->free();

//     } catch (Exception $e) {
//         http_response_code(500);
//         echo json_encode([
//             "error" => "伺服器發生錯誤",
//             "message" => $e->getMessage()
//         ]);
//     } finally {
//         if (isset($mysqli)) {
//             $mysqli->close();
//         }
//     }
//     exit();
// }

// http_response_code(405);
// echo json_encode(["error" => "僅允許 GET 請求"]);
?>
<?php
// session_start();
// require_once("../common/cors.php");
// require_once("../common/conn.php");

// header("Content-Type: application/json; charset=UTF-8");

// if ($_SERVER['REQUEST_METHOD'] == "GET") {
//     try {
//         if (!isset($_SESSION['member_id'])) {
//             http_response_code(401);
//             echo json_encode(["error" => "未授權，請先登入"]);
//             exit();
//         }
//         $member_id = $_SESSION['member_id'];

//         // 修正後的 SQL 查詢
//         // 從 ci (cart_items) 中獲取 size 和 color_code
//         // 從 p (products) 中獲取 name, price, main_image_url
//         $sql = "SELECT 
//                     ci.cart_item_id, 
//                     ci.member_id, 
//                     ci.product_id, 
//                     ci.quantity,
//                     ci.size AS selected_size, 
//                     ci.color_code AS selected_color,
//                     p.name, 
//                     p.price, 
//                     p.main_image_url
//                 FROM cart_items AS ci
//                 JOIN products AS p ON ci.product_id = p.product_id
//                 WHERE ci.member_id = '$member_id'";

//         $result = $mysqli->query($sql);

//         if ($result === false) {
//             throw new mysqli_sql_exception("購物車查詢失敗: " . $mysqli->error);
//         }

//         $items = [];
//         while ($row = $result->fetch_assoc()) {
//             // 重新組織資料，並將資料庫欄位名稱映射為前端期望的屬性名稱
//             $item = [
//                 'id' => (int)$row['product_id'],
//                 'cart_item_id' => (int)$row['cart_item_id'],
//                 'quantity' => (int)$row['quantity'],
//                 'name' => $row['name'],
//                 'price' => (float)$row['price'],
//                 'image' => $row['main_image_url'], // 將 main_image_url 映射為 image
//                 'size' => $row['selected_size'],    // 使用從 cart_items 來的單一值
//                 'color' => $row['selected_color']   // 使用從 cart_items 來的單一值
//             ];
//             $items[] = $item;
//         }

//         echo json_encode(["items" => $items]);

//         $result->free();

//     } catch (Exception $e) {
//         http_response_code(500);
//         echo json_encode([
//             "error" => "伺服器發生錯誤",
//             "message" => $e->getMessage()
//         ]);
//     } finally {
//         if (isset($mysqli)) {
//             $mysqli->close();
//         }
//     }
//     exit();
// }

// http_response_code(405);
// echo json_encode(["error" => "僅允許 GET 請求"]);
?>
<?php
//session_start();
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

        $sql = "SELECT
                    ci.cart_item_id,
                    ci.member_id,
                    ci.product_id,
                    ci.quantity,
                    ci.size,
                    ci.color_code,
                    p.name,
                    p.price,
                    p.main_image_url
                FROM cart_items AS ci
                JOIN products AS p ON ci.product_id = p.product_id
                WHERE ci.member_id = '$member_id'";

        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new mysqli_sql_exception("購物車查詢失敗: " . $mysqli->error);
        }

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $item = [
                'id' => (int)$row['product_id'],
                'cart_item_id' => (int)$row['cart_item_id'],
                'quantity' => (int)$row['quantity'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'image' => $row['main_image_url'],
                'size' => $row['size'],
                'color' => $row['color_code']
            ];
            $items[] = $item;
        }

        echo json_encode(["items" => $items]);

        $result->free();

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