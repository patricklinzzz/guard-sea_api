<?php
  require_once("./common/cors.php");
  require_once("./common/conn.php");

  if($_SERVER['REQUEST_METHOD']=="GET"){
 // *** 這是最終要返回的物件 ***
    $response_data = new stdClass();

    // 1. 獲取新聞列表 (使用 JOIN，方便列表頁直接顯示分類名稱)
    $sql_news = "SELECT 
                   n.news_id,
                   n.title,
                   n.content,
                   DATE(n.publish_date) AS publish_date, /*修改成date函式不顯示時分秒 */
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
    
    // 將新聞列表放入返回物件
    $response_data->news = $news_list;

    // 2. 獲取分類列表 (給新增/編輯頁的下拉選單使用)
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
  $reply_data = new stdClass();
  $reply_data->error = "拒絕存取";
  echo json_encode($reply_data);
?>