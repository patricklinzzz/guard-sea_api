<?php
  // 顯示所有錯誤，方便除錯
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  
  // --- 1. 資料庫連線 (您的程式碼，完全正確) ---
  $db_host = '127.0.0.1';
  $db_user = 'root';
  $db_password = 'root';
  $db_dbname = 'jay';
  $db_port = 8889;

  try {
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_dbname, $db_port);
    $mysqli->set_charset("utf8mb4"); // 確保字元編碼正確
  } catch (mysqli_sql_exception $e) {
    echo '資料庫連線錯誤：' . $e->getMessage() . '<br>';
    exit();
  }

  // --- 2. 從資料庫讀取資料 (這是新增加的部分) ---

  // 準備要執行的 SQL 查詢指令
  $sql = "SELECT * FROM members";

  // 執行查詢，並將結果存到 $result 變數中
  $result = $mysqli->query($sql);

  // 檢查是否有查詢到資料
  if ($result->num_rows > 0) {
    // 如果有資料，就用一個迴圈將每一筆資料都讀出來
    echo "<h1>會員列表</h1>";
    echo "<ul>";
    while($row = $result->fetch_assoc()) {
      // 將每一筆資料的 username 和 email 顯示出來
      echo "<li>姓名：" . $row["fullname"]. " - Email：" . $row["email"]. "</li>";
      echo "<li>avatar：" . $row["avatar_url"]. "</li>";
    }
    echo "</ul>";
  } else {
    // 如果沒有查到任何資料
    echo "找不到任何會員資料";
  }

  // --- 3. 關閉資料庫連線 ---
  $mysqli->close();

?>