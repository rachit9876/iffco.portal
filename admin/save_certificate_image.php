<?php
session_start();

// Authentication check - only admins can save certificate images
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['roll_no'])) {
    // Validate roll_no to prevent path traversal
    $roll_no = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['roll_no']);
    $user_folder = "../user/uploads/{$roll_no}";
    
    if (!file_exists($user_folder)) {
        mkdir($user_folder, 0777, true);
    }
    
    $image_path = "{$user_folder}/certificate.png";
    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    
    // Delete temp HTML
    $temp_html = "{$user_folder}/temp_certificate.html";
    if (file_exists($temp_html)) {
        unlink($temp_html);
    }
    
    echo "success";
}
?>
