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

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Simple validation
    if (empty($username) || empty($password) || empty($email)) {
        echo "All fields are required.";
    } else {
        // Password encryption
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert data
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        $params = array($username, $hashed_password, $email);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo "Registration successful!";
        } else {
            echo "Error: " . print_r(sqlsrv_errors(), true);
        }
    }
}

sqlsrv_close($conn)

?>

<!-- HTML Form -->
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username"><br><br>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password"><br><br>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email"><br><br>

    <input type="submit" value="Register">
</form>
