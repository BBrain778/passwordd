<?php
// Database configuration
$servername = "DESKTOP-UB454E1";
$database = "SQLinject";
$username = "sa"; // MS SQL username
$password = "123456"; // MS SQL password

// Create connection
$connectionInfo = array("Database" => $database, "UID" => $username, "PWD" => $password);
$conn = sqlsrv_connect($servername, $connectionInfo);

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// 初始化變數
$registration_successful = false;

// Start session to handle CAPTCHA
session_start();

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $ID_number = $_POST['ID_number']; // 新增ID_number變數
    $birthday = $_POST['birthday']; // 新增birthday變數
    $captcha = $_POST['captcha']; // Captcha input

    // Simple validation
    if (empty($username) || empty($password) || empty($email) || empty($ID_number) || empty($birthday) || empty($captcha)) {
        echo "All fields are required.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).+$/', $username)) {
        echo "Username must contain at least one letter and one number.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{7,}$/', $password)) {
        echo "Password must be at least 7 characters long, contain both upper and lower case letters, and at least one number.";
    } elseif ($captcha !== $_SESSION['captcha_code']) {
        echo "Incorrect CAPTCHA.";
    } else {
        // Password encryption using password_hash()
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert data into database
        $sql = "INSERT INTO users (username, password, email, ID_number, birthday) VALUES (?, ?, ?, ?, ?)";
        $params = array($username, $hashed_password, $email, $ID_number, $birthday);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // 註冊成功
            $registration_successful = true;
        } else {
            echo "Error: " . print_r(sqlsrv_errors(), true);
        }
    }
}

// Close connection
sqlsrv_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <style>
        .invalid {
            color: red;
        }

        .valid {
            color: green;
        }

        .hint {
            display: block;
            margin-top: 5px;
        }

        .success {
            display: none;
            color: green;
            font-size: 18px;
        }

        .hint-box {
            border: 1px solid #ccc;
            padding: 15px;
            background-color: #f9f9f9;
            width: 800px;
            margin-bottom: 20px;
        }

        .hint-box p {
            margin: 0;
        }
    </style>
    <script>
        function validateUsername() {
            const username = document.getElementById("username").value;
            const usernameHint = document.getElementById("username-hint");
            const usernameRegex = /^(?=.*[a-zA-Z])(?=.*\d).+$/;

            if (!usernameRegex.test(username)) {
                usernameHint.textContent = "Username must contain at least one letter and one number.";
                usernameHint.className = "invalid";
                usernameHint.style.display = "inline";
            } else {
                usernameHint.textContent = "Username is valid!";
                usernameHint.className = "valid";
            }
        }

        function validatePassword() {
            const password = document.getElementById("password").value;
            const passwordHint = document.getElementById("password-hint");

            const lengthRequirement = /.{7,}/;
            const uppercaseRequirement = /[A-Z]/;
            const lowercaseRequirement = /[a-z]/;
            const numberRequirement = /[0-9]/;

            let messages = [];

            if (!lengthRequirement.test(password)) {
                messages.push("Password must be at least 7 characters long.");
            }
            if (!uppercaseRequirement.test(password)) {
                messages.push("Password must contain at least one uppercase letter.");
            }
            if (!lowercaseRequirement.test(password)) {
                messages.push("Password must contain at least one lowercase letter.");
            }
            if (!numberRequirement.test(password)) {
                messages.push("Password must contain at least one number.");
            }

            if (messages.length > 0) {
                passwordHint.innerHTML = messages.join("<br>");
                passwordHint.className = "invalid";
            } else {
                passwordHint.textContent = "Password is valid!";
                passwordHint.className = "valid";
            }
        }

        function refreshCaptcha() {
            document.getElementById('captcha-image').src = 'captcha.php?' + Date.now();
        }

        function showSuccessAlert() {
            const alertBox = document.getElementById("success-alert");
            alertBox.style.display = "block";
        }

        window.onload = function() {
            <?php if ($registration_successful): ?>
            showSuccessAlert();
            <?php endif; ?>
        };
    </script>
</head>
<body>

<div id="success-alert" class="success">
    <h2>Registration Successful!</h2>
    <p>Your account has been created successfully. <a href="index.html">Return to Login</a></p>
</div>

<?php if (!$registration_successful): ?>
    <h2>Register</h2>

    <div class="hint-box">
        <p>Username must contain at least one letter and one number.</p>
        <p>Password must be at least 7 characters long, contain both upper and lower case letters, and at least one number.</p>
    </div>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" oninput="validateUsername()" required>
        <span id="username-hint" class="hint"></span><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" oninput="validatePassword()" required>
        <span id="password-hint" class="hint"></span><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="ID_number">ID Number:</label>
        <input type="text" id="ID_number" name="ID_number" required><br><br>

        <label for="birthday">Birthday:</label>
        <input type="date" id="birthday" name="birthday" required><br><br>

        <!-- CAPTCHA Section -->
        <label for="captcha">Captcha:</label>
        <img id="captcha-image" src="captcha.php" alt="CAPTCHA Image"><br>
        <button type="button" onclick="refreshCaptcha()">Refresh Captcha</button><br>
        <input type="text" id="captcha" name="captcha" required><br><br>

        <input type="submit" value="Register">
    </form>
<?php endif; ?>

</body>
</html>
