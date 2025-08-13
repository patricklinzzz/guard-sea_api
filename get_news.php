<?php
  require_once("./common/cors.php");
  require_once("./common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){
    header("Content-Type: application/json; charset=UTF-8");
    // *** 這是最終要返回的物件 ***
    $response_data = new stdClass();

    // 1. 獲取新聞列表
    $sql_news = "SELECT 
                   n.news_id,
                   n.title,
                   n.content,
                   DATE(n.publish_date) AS publish_date,
                   n.category_id,
                   n.image_url,
                   n.status,
                   nc.category_name 
                 FROM news n
                 LEFT JOIN news_categories nc ON n.category_id = nc.category_id
                 ORDER BY n.publish_date DESC";

    $stmt_news = $mysqli->prepare($sql_news);
    $stmt_news->execute();
    $result_news = $stmt_news->get_result();
    $news_list = $result_news->fetch_all(MYSQLI_ASSOC);
    
    
    // 獲取封面圖start
    // 讓當前的協議 (http) 和主機名稱+端口 (localhost:8888) 讓上傳的圖片圖片能讀到後端的網址 而非讀到前台的網址
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];

    // 遍歷新聞列表，為 image_url 加上完整的 base URL
    foreach ($news_list as &$news_item) { // 使用 & 引用來直接修改陣列元素
        // 確保 image_url 不是空的，並且不是一個完整的 URL
        if (!empty($news_item['image_url']) && strpos($news_item['image_url'], 'http') !== 0) {
            $news_item['image_url'] = $baseUrl . $news_item['image_url'];
        }
    }
    unset($news_item); // 斷開最後一個元素的引用


    // 將處理過的新聞列表放入返回物件
    $response_data->news = $news_list;

     // 獲取封面圖end

    // 2. 獲取分類列表
    $sql_categories = "SELECT category_id, category_name FROM news_categories";
    $stmt_categories = $mysqli->prepare($sql_categories);
    $stmt_categories->execute();
    $result_categories = $stmt_categories->get_result();
    $categories_list = $result_categories->fetch_all(MYSQLI_ASSOC);

    // 將分類列表放入返回物件
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