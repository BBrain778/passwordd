<?php
session_start();

// 資料庫連接設定
$serverName = "DESKTOP-UB454E1";
$connectionOptions = array(
    "Database" => "SQLinject",
    "Uid" => "sa",
    "PWD" => "123456"
);

// 連接資料庫
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(json_encode(["success" => false, "message" => "資料庫連接失敗"]));
}

// 確保使用者已登入並在 session 中儲存了 username
if (!isset($_SESSION['username'])) {
    die(json_encode(["success" => false, "message" => "使用者未登入"]));
}

$username = $_SESSION['username'];
$inputPassword = $_POST['password'];

// 從資料庫中查詢使用者註冊時保存的密碼（假設密碼已加密或雜湊儲存）
$sql = "SELECT password FROM users WHERE username = ?";
$params = array($username);
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(json_encode(["success" => false, "message" => "查詢錯誤"]));
}

// 獲取資料庫中的密碼
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$storedPassword = $row['password'];

// 根據儲存方式進行密碼驗證
// 假設使用 password_hash() 儲存密碼，使用 password_verify() 驗證
if (password_verify($inputPassword, $storedPassword)) {
    // 密碼正確，返回成功訊息
    echo json_encode(["success" => true]);
} else {
    // 密碼不正確
    echo json_encode(["success" => false]);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
