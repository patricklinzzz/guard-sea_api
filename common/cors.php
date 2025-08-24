<?php
  // 修正：將 session_start() 放在最前面
  //session_start();
  // 允許的域名列表
  $allowed_origins = [
    "http://localhost:5173",
    "http://localhost:5174",
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost:8888",
    "https://tibamef2e.com",
    "https://fc28ef460f6f.ngrok-free.app",
    "https://41e2b5a0c739.ngrok-free.app",
  ];
  // 抓到請求的來源網域
  $origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 

  if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
  }
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(204); // 回應 204 No Content，表示預檢成功
  exit; // 結束程式，不執行後續的業務邏輯
}
?>