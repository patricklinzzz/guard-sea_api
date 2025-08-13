<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    header("Content-Type: application/json; charset=UTF-8");
    $response_data = new stdClass();

    // 1. 獲取新聞列表
    $sql_news = "SELECT 
                    n.news_id, n.title, n.content, DATE(n.publish_date) AS publish_date, 
                    n.category_id, n.image_url, n.status, nc.category_name 
                 FROM news n
                 LEFT JOIN news_categories nc ON n.category_id = nc.category_id
                 ORDER BY n.publish_date DESC";

    $stmt_news = $mysqli->prepare($sql_news);

    if (!$stmt_news) {
        http_response_code(500);
        echo json_encode(["error" => "SQL 語法錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }

    $stmt_news->execute();
    
    // 綁定結果到變數
    $stmt_news->bind_result(
        $news_id, $title, $content, $publish_date, $category_id, $image_url, $status, $category_name
    );

    // 遍歷結果集並手動組裝陣列
    $news_list = [];
    while ($stmt_news->fetch()) {
        $news_item = [
            'news_id' => $news_id,
            'title' => $title,
            'content' => $content,
            'publish_date' => $publish_date,
            'category_id' => $category_id,
            'image_url' => $image_url,
            'status' => $status,
            'category_name' => $category_name
        ];
        $news_list[] = $news_item;
    }

    // 處理圖片 URL 的邏輯
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    foreach ($news_list as &$news_item) {
        if (!empty($news_item['image_url']) && strpos($news_item['image_url'], 'http') !== 0) {
            $news_item['image_url'] = $baseUrl . $news_item['image_url'];
        }
    }
    unset($news_item);

    $response_data->news = $news_list;

    // 2. 獲取分類列表
    $sql_categories = "SELECT category_id, category_name FROM news_categories";
    $stmt_categories = $mysqli->prepare($sql_categories);
    
    if (!$stmt_categories) {
        http_response_code(500);
        echo json_encode(["error" => "SQL 語法錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }
    
    $stmt_categories->execute();


    // 綁定結果到變數
    $stmt_categories->bind_result($category_id, $category_name);

    // 遍歷結果集並手動組裝陣列
    $categories_list = [];
    while ($stmt_categories->fetch()) {
        $categories_list[] = [
            'category_id' => $category_id,
            'category_name' => $category_name
        ];
    }

    
    $response_data->categories = $categories_list;

    // 3. 將整個包含兩部分資料的物件編碼成 JSON 返回
    echo json_encode($response_data);

    $mysqli->close();
    exit();
}

http_response_code(403);
header("Content-Type: application/json; charset=UTF-8");
$reply_data = new stdClass();
$reply_data->error = "拒絕存取";
echo json_encode($reply_data);
?>