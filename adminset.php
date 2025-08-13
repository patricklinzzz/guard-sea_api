<?php
// require_once("./common/conn.php");
// 1. 設定資料庫連線資訊
$servername = "localhost";
$username = "root";       // 替換成你的資料庫使用者名稱
$password = ""; // 替換成你的資料庫密碼
$dbname = "guardsea";  // 替換成你的資料庫名稱
$db_port = 3307;

try {
    // 建立 PDO 資料庫連線
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;port=$db_port;charset=utf8mb4", $username, $password,);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "資料庫連線成功！<br>";

    // 2. 準備要插入的資料
    $user_data = [
        'username' => 'admin',
        'password' => 'password123!', // 這是明文密碼
        'email'    => 'admin@admin.com',
        'fullname' => 'Admin'
    ];

    // 3. 使用 password_hash() 函式加密密碼
    // 這是最安全的做法，會自動加入「鹽 (salt)」
    $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);

    // 4. 準備 SQL 預處理語句
    $sql = "INSERT INTO administrators (username, password, email, fullname) VALUES (:username, :password, :email, :fullname)";
    
    $stmt = $conn->prepare($sql);

    // 5. 綁定參數並執行
    // 使用預處理語句可以防止 SQL 隱碼攻擊
    $stmt->bindParam(':username', $user_data['username']);
    $stmt->bindParam(':password', $hashed_password); // 將加密後的密碼綁定到參數
    $stmt->bindParam(':email', $user_data['email']);
    $stmt->bindParam(':fullname', $user_data['fullname']);

    $stmt->execute();

    echo "使用者 '{$user_data['username']}' 的資料已成功插入！<br>";

} catch(PDOException $e) {
    echo "錯誤： " . $e->getMessage();
}

// 關閉連線
$conn = null;

?>