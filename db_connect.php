<?php
// --- Database Connection ---
// This file establishes a connection to the MySQL database using InfinityFree credentials.
// Update this file if you change hosting or credentials.
// In production, it’s best to store these securely using environment variables or an external config.

// Database credentials
$host = 'sql103.infinityfree.com';              // InfinityFree MySQL server
$username = 'if0_38954512';                     // InfinityFree username
$password = '9Rspw1EpUHI8Hs';                   // Your database password
$database = 'if0_38954512_user';                // Your database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    // Connection failed: return error in JSON format (useful for APIs)
    die(json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]));
}

// Set character set to utf8mb4 to support emojis and multilingual data
if (!mysqli_set_charset($conn, "utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
    exit();
}

// The $conn object is now ready for queries throughout your app
?>
