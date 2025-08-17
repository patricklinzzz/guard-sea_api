<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === "GET") {
    $activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

    if ($activity_id <= 0) {
        echo json_encode(["status" => "error", "message" => "缺少或錯誤的 activity_id"]);
        exit;
    }

    $sql = "SELECT r.activity_registration_id, r.member_id, m.name, m.email,
                    r.phone, r.contact_person, r.contact_phone, r.notes, r.registration_date
            FROM activity_registrations r
            JOIN members m ON r.member_id = m.member_id
            WHERE r.activity_id = ?
            ORDER BY r.registration_date ASC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $registrations = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $registrations
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $mysqli->close();
    exit;
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);