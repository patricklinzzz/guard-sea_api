<?php
require_once("../common/cors.php");
require_once("../common/conn.php"); // 這裡要放連線 MySQL 的程式
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    $response_data = new stdClass();

    // 獲取活動列表，直接使用 query()
    $sql_events = "SELECT * FROM activities a
                    JOIN activity_categories c ON a.category_id = c.category_id
                    ORDER BY a.start_date DESC;";

    $result_events = $mysqli->query($sql_events);
    if (!$result_events) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "SQL 語法錯誤: " . $mysqli->error], JSON_UNESCAPED_UNICODE);
        $mysqli->close();
        exit();
    }
    $activities = $result_events->fetch_all(MYSQLI_ASSOC);

    // 處理圖片 URL
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    foreach ($activities as &$activity) {
        if (!empty($activity['image_url']) && strpos($activity['image_url'], 'http') !== 0) {
            $activity['image_url'] = $baseUrl . $activity['image_url'];
        }
    }
    unset($activity);

    $response_data->events = $activities;

    // 獲取分類列表，直接使用 query()
    $sql_categories = "SELECT category_id, category_name FROM activity_categories";
    $result_categories = $mysqli->query($sql_categories);
    if (!$result_categories) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "SQL 語法錯誤: " . $mysqli->error], JSON_UNESCAPED_UNICODE);
        $mysqli->close();
        exit();
    }
    $categories_list = $result_categories->fetch_all(MYSQLI_ASSOC);
    $response_data->categories = $categories_list;

    // 將整個包含兩部分資料的物件編碼成 JSON 返回
    echo json_encode([
        "status" => "success",
        "data" => $response_data
    ], JSON_UNESCAPED_UNICODE);

    $mysqli->close();
    exit;
}

// 非 GET 請求
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
?>