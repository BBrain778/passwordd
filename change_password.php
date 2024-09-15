<?php
session_start();

// 檢查使用者是否已登入
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// 取得密碼的 ID
if (!isset($_GET['id'])) {
    die("無效的密碼 ID");
}

$id = $_GET['id'];

// 資料庫連接
$serverName = "DESKTOP-UB454E1";
$connectionOptions = array(
    "Database" => "SQLinject",
    "Uid" => "sa",
    "PWD" => "123456"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// 取得目前的密碼資訊
$sql = "SELECT website_name, account_name, encrypted_password, notes FROM passwordmanage WHERE id = ? AND username = ?";
$params = array($id, $_SESSION['username']);
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$passwordData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$passwordData) {
    die("找不到密碼資訊");
}

// 更新密碼
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];

    // 生成新的 IV
    $iv = openssl_random_pseudo_bytes(16);

    // 加密新密碼
    $encrypted_password = openssl_encrypt($new_password, 'aes-256-cbc', 'your-encryption-key', 0, $iv);

    // 更新資料庫中的密碼
    $encrypted_data = base64_encode($iv . $encrypted_password);
    $sql = "UPDATE passwordmanage SET encrypted_password = ?, created_at = GETDATE() WHERE id = ? AND username = ?";
    $params = array($encrypted_data, $id, $_SESSION['username']);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // 更新成功後重新導向回密碼管理頁面
    header("Location: password_manager.php");
    exit();
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更換密碼</title>
</head>
<body>
    <h2>更換密碼</h2>
    <form action="change_password.php?id=<?php echo $id; ?>" method="post">
        <p>網站: <?php echo htmlspecialchars($passwordData['website_name']); ?></p>
        <p>帳號: <?php echo htmlspecialchars($passwordData['account_name']); ?></p>
        <p>備註: <?php echo htmlspecialchars($passwordData['notes']); ?></p>
        <label for="new_password">新密碼：</label>
        <input type="text" name="new_password" required>
        <input type="submit" value="更新密碼">
    </form>
</body>
</html>
