<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$servername = "DESKTOP-UB454E1";
$db_username = "sa";
$db_password = "123456";
$dbname = "SQLinject";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username_or_email'])) {
        $username_or_email = $_POST['username_or_email'];

        // Database connection
        $connectionInfo = array(
            "Database" => $dbname,
            "UID" => $db_username,
            "PWD" => $db_password
        );
        $conn = sqlsrv_connect($servername, $connectionInfo);

        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Check if the username or email exists
        $query = "SELECT email FROM users WHERE username = ? OR email = ?";
        $params = array($username_or_email, $username_or_email);
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $user_email = $row['email'];
            
            // Create a unique reset token
            $reset_token = bin2hex(random_bytes(16));
            
            // Store the reset token in the database with an expiration time (optional)
            // Assuming you have a `password_resets` table
            // $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));
            // $insert_query = "INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)";
            // sqlsrv_query($conn, $insert_query, array($user_email, $reset_token, $expiry));

            // Send the reset email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'aligadou49@gmail.com';
                $mail->Password   = 'fwexbtvrfecsxrmh';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('aligadou49@gmail.com', 'Reset password');
                $mail->addAddress($user_email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset';
                //$mail->Body    = 'Click the link to reset your password: <a href="http://yourwebsite.com/reset_password.php?token=' . $reset_token . '">Reset Password</a>';
                $mail->Body = 'Click the link to reset your password: <a href="http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $reset_token . '">Reset Password</a>';



                $mail->send();
                echo 'A password reset link has been sent to your email address.';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "No user found with that username or email.";
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
    } else {
        echo "Username or email must be provided.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            width: 100%;
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <p>Enter your username or email address to receive a password reset link.</p>
        <form method="POST" action="">
            <input type="text" name="username_or_email" placeholder="Username or Email" required>
            <input type="submit" value="Reset Password">
        </form>
    </div>
</body>
</html>
