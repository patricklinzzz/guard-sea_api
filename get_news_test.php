<?php
require_once("./common/cors.php");
// 假設 common/conn.php 現在已經建立了 PDO 連線
// 並且 PDO 物件變數是 $pdo
require_once("./common/conn.php");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    header("Content-Type: application/json; charset=UTF-8");
    $response_data = new stdClass();

    try {
        // 1. 獲取新聞列表
        $sql_news = "SELECT 
                        n.news_id, n.title, n.content, DATE(n.publish_date) AS publish_date, 
                        n.category_id, n.image_url, n.status, nc.category_name 
                      FROM news n
                      LEFT JOIN news_categories nc ON n.category_id = nc.category_id
                      ORDER BY n.publish_date DESC";

        $stmt_news = $pdo->prepare($sql_news); // 使用 $pdo 的 prepare
        $stmt_news->execute(); // 執行查詢
        $news_list = $stmt_news->fetchAll(PDO::FETCH_ASSOC); // 直接獲取所有新聞資料

        // 處理圖片 URL 的邏輯
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
        foreach ($news_list as &$news_item) {
            if (!empty($news_item['image_url']) && strpos($news_item['image_url'], 'http') !== 0) {
                $news_item['image_url'] = $baseUrl . $news_item['image_url'];
            }
        }
        unset($news_item); // 解除引用，避免潛在的錯誤

        $response_data->news = $news_list;

        // 2. 獲取分類列表
        $sql_categories = "SELECT category_id, category_name FROM news_categories";
        $stmt_categories = $pdo->prepare($sql_categories); // 使用 $pdo 的 prepare
        $stmt_categories->execute(); // 執行查詢
        $categories_list = $stmt_categories->fetchAll(PDO::FETCH_ASSOC); // 直接獲取所有分類資料

        $response_data->categories = $categories_list;

        // 3. 將整個包含兩部分資料的物件編碼成 JSON 返回
        echo json_encode($response_data);
        exit(); // 終止腳本執行

    } catch (PDOException $e) {
        // 捕獲任何 PDO 相關的資料庫錯誤
        http_response_code(500); // 設置 HTTP 狀態碼為 500 (內部伺服器錯誤)
        echo json_encode(["error" => "資料庫查詢失敗: " . $e->getMessage()]); // 包含詳細錯誤訊息
        exit(); // 終止腳本執行
    }
}

// 如果請求方法不是 GET，則返回 403 拒絕存取
http_response_code(403);
header("Content-Type: application/json; charset=UTF-8");
$reply_data = new stdClass();
$reply_data->error = "拒絕存取";
echo json_encode($reply_data);
?>