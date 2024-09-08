<?php
// Database configuration
$servername = "DESKTOP-UB454E1";
$database = "SQLinject";
$username = "sa"; // MS SQL username
$password = "123456"; // MS SQL password

// Create connection
$connectionInfo = array("Database"=>$database, "UID"=>$username, "PWD"=>$password);
$conn = sqlsrv_connect($servername, $connectionInfo);

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Variable to check if registration was successful
$registration_successful = false;

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Simple validation
    if (empty($username) || empty($password) || empty($email)) {
        echo "All fields are required.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).+$/', $username)) {
        echo "Username must contain at least one letter and one number.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{7,}$/', $password)) {
        echo "Password must be at least 7 characters long, contain both upper and lower case letters, and at least one number.";
    } else {
        // Password encryption
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert data
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        $params = array($username, $hashed_password, $email);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // Mark the registration as successful
            $registration_successful = true;
        } else {
            echo "Error: " . print_r(sqlsrv_errors(), true);
        }
    }
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <script>
        // Function to redirect to the login page
        function redirectToLogin() {
            window.location.href = "index.html";
        }
    </script>
</head>
<body>

<?php if ($registration_successful): ?>
    <h2>Registration Successful!</h2>
    <p>Your account has been created successfully. Please click the button below to return to the login page.</p>
    <button onclick="redirectToLogin()">Return to Login</button>
<?php else: ?>
    <h2>Register</h2>
    <p>Username must contain at least one letter and one number.</p>
    <p>Password must be at least 7 characters long, contain both upper and lower case letters, and at least one number.</p>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <input type="submit" value="Register">
    </form>
<?php endif; ?>

</body>
</html>
