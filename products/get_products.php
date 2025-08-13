<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    $types = "";
    if (isset($_GET['category_id'])) {
        $sql .= " AND category_id = ?";
        $params[] = $_GET['category_id'];
        $types .= "i"; 
    }

    if (isset($_GET['status'])) {
        $sql .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= "i"; 
    }

    $stmt = $mysqli->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(["error" => "SQL 語法錯誤: " . $mysqli->error]);
        $mysqli->close();
        exit();
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($data);

    $mysqli->close();
    exit();
}

http_response_code(403);
echo json_encode(["error" => "拒絕存取"]);
?>