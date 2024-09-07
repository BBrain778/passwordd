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
        $account_name = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $notes = $_POST['notes'] ?? null;

        // Insert the password into the database
        $sql = "INSERT INTO passwordmanage (username, website_name, account_name, encrypted_password, notes) 
                VALUES (?, ?, ?, ?, ?)";
        $params = array($username, $website_name, $account_name, $password, $notes);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['delete_password'])) {
        $id = $_POST['id'];

        // Delete the password from the database
        $sql = "DELETE FROM passwordmanage WHERE id = ? AND username = ?";
        $params = array($id, $username);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Fetch all passwords for the logged-in user
$sql = "SELECT id, website_name, account_name, notes, created_at FROM passwordmanage WHERE username = ?";
$params = array($username);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$passwords = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
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
        /* Your CSS styles here */
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Manager</h2>
        <form action="password_manager.php" method="post">
            <input type="text" name="site" placeholder="Site" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="notes" placeholder="Notes (optional)">
            <input type="submit" name="add_password" value="Add Password">
        </form>

        <h3>Stored Passwords</h3>
        <table>
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Username</th>
                    <th>Notes</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($passwords as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['website_name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['notes']); ?></td>
                        <td><?php echo htmlspecialchars($entry['created_at']->format('Y-m-d H:i:s')); ?></td>
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
