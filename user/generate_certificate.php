<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Check if certificate already exists
$stmt = $conn->prepare("SELECT id FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: certificates.php?error=Certificate already exists");
    exit;
}
$stmt->close();

// Check project status
$stmt = $conn->prepare("SELECT status FROM projects WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($proj_status);
$stmt->fetch();
$stmt->close();

if (strtolower(trim($proj_status)) !== 'completed') {
    header("Location: certificates.php?error=Project not completed yet");
    exit;
}

// Get user details and related project name
$stmt = $conn->prepare("SELECT u.name, u.roll_no, u.batch, u.college, d.name as department, p.name as program, pr.project_name FROM users u LEFT JOIN departments d ON u.department_id = d.id LEFT JOIN programs p ON u.program_id = p.id LEFT JOIN projects pr ON u.id = pr.user_id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: certificates.php?error=User not found");
    exit;
}

$template_path = __DIR__ . '/certificate_template.html';
if (!file_exists($template_path)) {
    header("Location: certificates.php?error=Certificate template missing");
    exit;
}

$template = file_get_contents($template_path);
$duration = $student['duration'] ?? '6 weeks';
$end_date = new DateTime();
$start_date = clone $end_date;
$start_date->modify('-' . $duration);

$replacements = [
    '{name}' => strtoupper($student['name']),
    '{roll_no}' => $student['roll_no'],
    '{department}' => $student['department'] ?? 'N/A',
    '{college}' => $student['college'] ?? 'N/A',
    '{batch}' => $student['batch'],
    '{issue_date}' => date('d-m-Y'),
    '{start_date}' => $start_date->format('d-m-Y'),
    '{end_date}' => $end_date->format('d-m-Y'),
    '{project_name}' => $student['project_name'] ?? 'N/A',
    '{verify_url}' => 'https://iffco-portal.page.gd/verify.php?roll=' . urlencode($student['roll_no'])
];

$html = str_replace(array_keys($replacements), array_values($replacements), $template);
$html = str_replace('</body>', '\n    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>\n    </body>', $html);

$user_folder = __DIR__ . '/uploads/' . $student['roll_no'];
if (!file_exists($user_folder)) mkdir($user_folder, 0777, true);
$cert_path = $user_folder . '/certificate.html';
file_put_contents($cert_path, $html);

$stmt = $conn->prepare("INSERT INTO certificates (user_id, certificate_path, issue_date) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $user_id, $cert_path);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: certificates.php?msg=Certificate generated successfully");
    exit;
} else {
    $stmt->close();
    header("Location: certificates.php?error=Failed to save certificate");
    exit;
}

?>
