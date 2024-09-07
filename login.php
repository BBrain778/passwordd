<?php
session_start();

// 引入PHPMailer庫
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$servername = "DESKTOP-UB454E1"; // 資料庫伺服器地址
$username = "sa"; // 資料庫使用者名稱
$password = "123456"; // 資料庫密碼
$dbname = "SQLinject"; // 資料庫名稱

// 獲取使用者提交的使用者名稱和密碼
if (isset($_POST['username']) && isset($_POST['password'])) {
    $user_username = $_POST['username'];
    $user_password = $_POST['password'];

    // 設置連接資訊
    $connectionInfo = array(
        "Database" => $dbname,
        "UID" => $username,
        "PWD" => $password
    );

    // 建立連接
    $conn = sqlsrv_connect($servername, $connectionInfo);

    if ($conn) {
        // 查詢資料庫中是否存在匹配的使用者名稱
        $query = "SELECT * FROM users WHERE username = ?";
        $params = array($user_username);
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt === false) {
            // 查詢出錯
            die(print_r(sqlsrv_errors(), true));
        }

        if ($user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // 使用者名稱存在，驗證密碼
            if (password_verify($user_password, $user['password'])) {
                // 使用者名稱和密碼匹配
                echo '<div style="background-color: yellow;font-size: 2em; text-align: center;">Login successful!</div>';

                // 生成 6 位數的驗證碼
                $verification_code = rand(100000, 999999);
                $_SESSION['verification_code'] = $verification_code;
                $_SESSION['username'] = $user_username;

                // 显示生成的验证码
                echo '<div style="background-color: lightblue; font-size: 1.5em; text-align: center;">Your verification code is: ' . $verification_code . '</div>';

                // 假設使用者的電子郵件在資料庫中保存為 'email'
                $user_email = $user['email'];

                // 使用PHPMailer發送驗證碼到使用者的Gmail
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                    $mail->Username   = 'aligadou49@gmail.com';                 // SMTP username
                    $mail->Password   = 'fwexbtvrfecsxrmh';      // SMTP password  //這是google最新的政策 要用/申請 應用程式專用密碼
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
                    $mail->Port       = 587;                           // TCP port to connect to

                    //Recipients
                    $mail->setFrom('aligadou49@gmail.com', 'Mailer');
                    $mail->addAddress($user_email, $user_username);             // Add a recipient

                    // Content
                    $mail->isHTML(true);                                        // Set email format to HTML
                    $mail->Subject = 'Your Verification Code';
                    $mail->Body    = "Your verification code is <b>$verification_code</b>";
                    $mail->AltBody = "Your verification code is $verification_code";

                    $mail->send();
                    echo 'Verification code has been sent to your email.';
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

                // 跳轉到驗證頁面
                header("Location: verify.php");
                exit();
            } else {
                // 密碼不匹配
                echo '<div style="background-color: black; color: white;font-size: 2em; text-align: center;">Invalid username or password.</div>';
            }
        } else {
            // 使用者名稱不存在
            echo '<div style="background-color: black; color: white;font-size: 2em; text-align: center;">Invalid username or password.</div>';
        }

        // 釋放結果集
        sqlsrv_free_stmt($stmt);

        // 關閉資料庫連接
        sqlsrv_close($conn);
    } else {
        // 連接資料庫失敗
        echo "Connection could not be established.";
    }
} else {
    echo "Username and password must be provided.";
}
?>
