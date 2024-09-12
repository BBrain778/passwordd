<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    header("Location: verify.php");
    exit();
}

// Database connection parameters
$serverName = "DESKTOP-UB454E1";
$connectionOptions = array(
    "Database" => "SQLinject",
    "Uid" => "sa",
    "PWD" => "123456"
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check if the connection was successful
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Get the username from the session (assuming it's stored there after login)
$username = $_SESSION['username'];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_password'])) {
        $website_name = $_POST['site'];
        $account_name = $_POST['account'];
        $password_plaintext = $_POST['password'];
        $notes = $_POST['notes'] ?? null;
        $created_at = $_POST['created_at'];  // 使用者選擇的創建日期

        // Generate a 16-byte IV
        $iv = openssl_random_pseudo_bytes(16);

        // Encrypt the password
        $encrypted_password = openssl_encrypt($password_plaintext, 'aes-256-cbc', 'your-encryption-key', 0, $iv);

        // Store the IV along with the encrypted password (concatenate them)
        $encrypted_data = base64_encode($iv . $encrypted_password);

        // Insert the password into the database with the selected date
        $sql = "INSERT INTO passwordmanage (username, website_name, account_name, encrypted_password, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($username, $website_name, $account_name, $encrypted_data, $notes, $created_at);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Fetch all passwords for the logged-in user
$sql = "SELECT id, website_name, account_name, encrypted_password, notes, created_at FROM passwordmanage WHERE username = ?";
$params = array($username);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$passwords = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $encrypted_data = base64_decode($row['encrypted_password']);

    // Extract the IV (first 16 bytes) and the encrypted password
    $iv = substr($encrypted_data, 0, 16);
    $encrypted_password = substr($encrypted_data, 16);

    // Decrypt the password
    $decrypted_password = openssl_decrypt($encrypted_password, 'aes-256-cbc', 'your-encryption-key', 0, $iv);
    $row['decrypted_password'] = $decrypted_password;

    // Check if the password is older than 60 days
    $created_at = new DateTime($row['created_at']->format('Y-m-d'));
    $today = new DateTime();
    $interval = $today->diff($created_at);
    
    if ($interval->days > 60) {
        $row['password_alert'] = "需要更換密碼";
    } else {
        $row['password_alert'] = "";
    }

    $passwords[] = $row;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager</title>
    <style>
        .logout-container {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .alert {
            color: red;
            font-weight: bold;
        }
    </style>
    <script>
        let logoutTime = 30; // 預設倒計時30秒

        // 計時器的函數
        function startLogoutCountdown() {
            const timer = setInterval(() => {
                if (logoutTime <= 0) {
                    clearInterval(timer);
                    // 先將頁面變成空白
                    document.body.innerHTML = "";
                    document.body.style.backgroundColor = "white";
                    // 延遲1秒後跳出提示並重導到登錄頁
                    setTimeout(() => {
                        alert('逾時通知');
                        window.location.href = "index.html";
                    }, 1000);
                } else {
                    document.getElementById('logout-timer').innerText = `剩餘時間: ${logoutTime}秒`;
                    logoutTime--;
                }
            }, 1000);
        }

        // 在頁面加載後啟動計時器
        window.onload = function() {
            let savedLogoutTime = localStorage.getItem('logoutTime');
            if (savedLogoutTime === null || isNaN(savedLogoutTime) || savedLogoutTime <= 0) {
                logoutTime = 30;
            } else {
                logoutTime = parseInt(savedLogoutTime);
            }
            startLogoutCountdown();
        };

        // 防止倒計時因為頁面重整而重置
        window.onbeforeunload = () => {
            localStorage.setItem('logoutTime', logoutTime);
        };
    </script>
</head>
<body>
    <div class="logout-container">
        <form action="logout.php" method="post">
            <input type="submit" value="Logout">
        </form>
        <span id="logout-timer">剩餘時間: 30秒</span>
    </div>

    <div class="container">
        <h2>Password Manager</h2>
        <form action="password_manager.php" method="post">
            <input type="text" name="site" placeholder="Site" required>
            <input type="text" name="account" placeholder="Account" required>
            
            <input type="text" name="password" placeholder="Password" required>
            <button type="button" onclick="fillGeneratedPassword()">Generate Strong Password</button>

            <input type="date" name="created_at" required>
            
            <input type="text" name="notes" placeholder="Notes (optional)">
            <input type="submit" name="add_password" value="Add Password">
        </form>

        <h3>Stored Passwords</h3>
        <table>
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Account</th>
                    <th>Password</th>
                    <th>Notes</th>
                    <th>Created At</th>
                    <th>Alert</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($passwords as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['website_name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['decrypted_password']); ?></td>
                        <td><?php echo htmlspecialchars($entry['notes']); ?></td>
                        <td><?php echo htmlspecialchars($entry['created_at']->format('Y-m-d')); ?></td>
                        <td class="alert"><?php echo htmlspecialchars($entry['password_alert']); ?></td>
                        <td>
                            <form action="password_manager.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                <input type="submit" name="delete_password" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
