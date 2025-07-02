<?php
// --- Localhost DB Connection (XAMPP) ---

$host = 'localhost';
$username = 'root';
$password = ''; // XAMPP default is empty
$database = 'if0_38954512_user'; // Use your local imported DB name

// Connect to MySQL
$conn = mysqli_connect($host, $username, $password, $database);

// Connection check
if (!$conn) {
    die(json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]));
}

// Set charset
if (!mysqli_set_charset($conn, "utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
    exit();
}

// Connection ready
?>
