<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

if (empty($roll_no)) {
    header('Location: dashboard.php?error=' . urlencode('Roll number not set'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['docfile']) || !isset($_POST['doc_type'])) {
    header('Location: dashboard.php?error=' . urlencode('Invalid request'));
    exit;
}

$doc_type = $_POST['doc_type'];
if (!in_array($doc_type, ['noc','referral'])) {
    header('Location: dashboard.php?error=' . urlencode('Invalid document type'));
    exit;
}

$file = $_FILES['docfile'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: dashboard.php?error=' . urlencode('File upload error'));
    exit;
}

// Basic validation: only PDF, max 10MB
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if ($mime !== 'application/pdf') {
    header('Location: dashboard.php?error=' . urlencode('Only PDF files are allowed'));
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    header('Location: dashboard.php?error=' . urlencode('File too large (max 10MB)'));
    exit;
}

$upload_dir = __DIR__ . '/uploads/' . $roll_no . '/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        header('Location: dashboard.php?error=' . urlencode('Failed to create upload directory'));
        exit;
    }
}

$dest_name = ($doc_type === 'noc') ? 'noc.pdf' : 'referral.pdf';
$dest_path = $upload_dir . $dest_name;

if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    header('Location: dashboard.php?error=' . urlencode('Failed to move uploaded file'));
    exit;
}

header('Location: dashboard.php?msg=' . urlencode('File uploaded successfully'));
exit;

?>
