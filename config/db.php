<?php
$servername = "localhost"; // Your database server
$username = "root";     // Your database username
$password = "";  // Your database password
$dbname = "task";     // Your database name

// Create a connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
    // If connection fails, show error message
    die("Connection failed: " . $e->getMessage());
}

// Check if PDO connection is successful before using it
if ($pdo) {
    // You can now safely use the prepare() method
    try {
        $sql = "SELECT * FROM users";
        $stmt = $pdo->prepare($sql); // Prepare the SQL query

        // Execute the statement
        $stmt->execute();

        
    } catch (PDOException $e) {
        echo "Query failed: " . $e->getMessage();
    }
} else {
    echo "Connection to the database was not successful.";
}
?>

