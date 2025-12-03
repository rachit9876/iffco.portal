<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    // Fetch student data
    $stmt = $conn->prepare("SELECT u.*, d.name as department, p.name as program FROM users u LEFT JOIN departments d ON u.department_id = d.id LEFT JOIN programs p ON u.program_id = p.id WHERE u.id = ? AND u.role = 'user'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    // Fetch project data
    $stmt = $conn->prepare("SELECT project_name FROM projects WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        // Load template
        $template = file_get_contents('../user/certificate_template.html');
        
        // Calculate dates
        $duration = $student['duration'] ?? '6 weeks';
        $end_date = new DateTime();
        $start_date = clone $end_date;
        $start_date->modify('-' . $duration);
        
        // Replace placeholders
        $verify_url = 'https://iffco-portal.page.gd/verify.php?roll=' . urlencode($student['roll_no']);
        $replacements = [
            '{name}' => strtoupper($student['name']),
            '{roll_no}' => $student['roll_no'],
            '{department}' => $student['department'] ?? 'N/A',
            '{college}' => $student['college'] ?? 'N/A',
            '{batch}' => $student['batch'],
            '{issue_date}' => date('d-m-Y'),
            '{start_date}' => $start_date->format('d-m-Y'),
            '{end_date}' => $end_date->format('d-m-Y'),
            '{project_name}' => $project['project_name'] ?? 'N/A',
            '{verify_url}' => $verify_url
        ];
        
        $html = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        // Save to user's upload folder
        $user_folder = "../user/uploads/{$student['roll_no']}";
        if (!file_exists($user_folder)) {
            mkdir($user_folder, 0777, true);
        }
        
        // Add download button to certificate
        $html = str_replace('</body>', '
    <button id="downloadBtn" style="position: fixed; top: 20px; right: 20px; padding: 15px 30px; background: #22c55e; color: white; border: 3px solid #000; font-weight: bold; cursor: pointer; z-index: 9999; box-shadow: 4px 4px 0 #000;">Download as Image</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        document.getElementById("downloadBtn").onclick = function() {
            this.textContent = "Generating...";
            const btn = this;
            html2canvas(document.querySelector(".page-wrapper"), {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff"
            }).then(canvas => {
                const link = document.createElement("a");
                link.download = "IFFCO_Certificate_' . $student['roll_no'] . '.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
                btn.textContent = "Download as Image";
            });
        };
    </script>
    </body>', $html);
        
        $cert_path = "{$user_folder}/certificate.html";
        file_put_contents($cert_path, $html);
        
        // Save to database
        $stmt = $conn->prepare("INSERT INTO certificates (user_id, certificate_path, issue_date) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE certificate_path = ?, issue_date = NOW()");
        $stmt->bind_param("iss", $student_id, $cert_path, $cert_path);
        $stmt->execute();
        $stmt->close();
        
        header("Location: students.php?msg=Certificate generated successfully");
        exit;
    }
}

$conn->close();
header("Location: students.php?error=Invalid request");
exit;
?>
