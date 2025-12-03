<?php
require_once 'db_connect.php';

$roll_no = isset($_GET['roll']) ? trim($_GET['roll']) : '';
$certificate = null;
$student = null;

if (!empty($roll_no)) {
    // Sanitize roll_no (allow letters, numbers, underscore and dash)
    $roll_no = preg_replace('/[^a-zA-Z0-9_\-]/', '', $roll_no);
    
    // Fetch certificate and student data
    $stmt = $conn->prepare("
         SELECT u.name, u.roll_no, u.college, u.batch, d.name as department, p.name as program, 
             c.issue_date, c.certificate_path, pr.project_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN certificates c ON u.id = c.user_id
        LEFT JOIN projects pr ON u.id = pr.user_id
        WHERE u.roll_no = ? AND u.role = 'user' AND u.status = 'approved'
    ");
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        // Determine certificate status:
        // 1) If certificates.issue_date is set -> issued
        // 2) If certificates.certificate_path exists on disk -> issued
        // 3) Fallback: check default generated path user/uploads/<roll_no>/certificate.html
        $certificate = false;
        $cert_path = null;

        if (!empty($student['issue_date'])) {
            $certificate = true;
        }

        if (!$certificate && !empty($student['certificate_path'])) {
            $p = $student['certificate_path'];
            if (file_exists($p)) {
                $certificate = true;
                $cert_path = $p;
            }
        }

        if (!$certificate) {
            // check default location under user/uploads/<roll_no>/certificate.html
            $default_path = __DIR__ . '/user/uploads/' . $roll_no . '/certificate.html';
            if (file_exists($default_path)) {
                $certificate = true;
                $cert_path = $default_path;
            }
        }
        // store cert path into student array for later display if available
        if ($cert_path) $student['certificate_path'] = $cert_path;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - IFFCO Portal</title>
    <link rel="stylesheet" href="neobrutalist.css">
    <style>
        body {
            background: #e5e5e5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-card {
            max-width: 600px;
            width: 100%;
        }
        .verify-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .verify-status {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .verify-valid {
            background: #d1fae5;
            border: 3px solid #10b981;
        }
        .verify-invalid {
            background: #fee2e2;
            border: 3px solid #ef4444;
        }
        .verify-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .verify-details div {
            padding: 10px;
            background: #f9f9f9;
            border: 2px solid #e5e5e5;
        }
        .verify-details strong {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        @media (max-width: 500px) {
            .verify-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="unver-card verify-card">
        <div class="unver-card-body">
            <div class="verify-header">
                <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" style="max-width: 100px; margin-bottom: 16px;">
                <h1 class="unver-h2">Certificate Verification</h1>
                <p class="unver-text-muted">IFFCO Vocational Training Portal</p>
            </div>

            <?php if (empty($roll_no)): ?>
                <div class="verify-status verify-invalid">
                    <h2 style="margin: 0; color: #b91c1c;">⚠️ No Roll Number Provided</h2>
                    <p style="margin: 8px 0 0; color: #991b1b;">Please scan a valid certificate QR code.</p>
                </div>
            <?php elseif (!$student): ?>
                <div class="verify-status verify-invalid">
                    <h2 style="margin: 0; color: #b91c1c;">❌ Invalid Certificate</h2>
                    <p style="margin: 8px 0 0; color: #991b1b;">No student found with roll number: <strong><?php echo htmlspecialchars($roll_no); ?></strong></p>
                </div>
            <?php elseif (!$certificate): ?>
                <div class="verify-status verify-invalid">
                    <h2 style="margin: 0; color: #b91c1c;">⏳ Certificate Not Issued</h2>
                    <p style="margin: 8px 0 0; color: #991b1b;">Student found but certificate has not been issued yet.</p>
                </div>
                <div class="verify-details">
                    <div><strong>Name</strong><?php echo htmlspecialchars($student['name']); ?></div>
                    <div><strong>Roll No</strong><?php echo htmlspecialchars($student['roll_no']); ?></div>
                </div>
            <?php else: ?>
                <div class="verify-status verify-valid">
                    <h2 style="margin: 0; color: #065f46;">✅ Valid Certificate</h2>
                    <p style="margin: 8px 0 0; color: #047857;">This certificate is authentic and verified.</p>
                </div>
                <div class="verify-details">
                    <div><strong>Name</strong><?php echo htmlspecialchars($student['name']); ?></div>
                    <div><strong>Roll No</strong><?php echo htmlspecialchars($student['roll_no']); ?></div>
                    <div><strong>Department</strong><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></div>
                    <div><strong>Program</strong><?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?></div>
                    <div><strong>College</strong><?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></div>
                    <div><strong>Batch</strong><?php echo htmlspecialchars($student['batch']); ?></div>
                    <div><strong>Issue Date</strong><?php echo date('F j, Y', strtotime($student['issue_date'])); ?></div>
                    <div><strong>Project</strong><?php echo htmlspecialchars($student['project_name'] ?? 'N/A'); ?></div>
                </div>
                <?php
                // If certificate file path is available, convert to web relative path and show a link
                if (!empty($student['certificate_path'])) {
                    $cert_raw = $student['certificate_path'];
                    $cert_web_path = str_replace('\\', '/', $cert_raw);
                    $cert_web_path = preg_replace('#^[a-zA-Z]:#', '', $cert_web_path);
                    $pos = stripos($cert_web_path, '/user/');
                    if ($pos !== false) {
                        $cert_web_path = substr($cert_web_path, $pos + strlen('/user/'));
                    } else {
                        $pos = stripos($cert_web_path, 'user/');
                        if ($pos !== false) $cert_web_path = substr($cert_web_path, $pos + strlen('user/'));
                    }
                    $cert_web_path = ltrim($cert_web_path, '/\\');
                    if (stripos($cert_web_path, 'uploads/') !== 0) {
                        $p2 = stripos($cert_web_path, 'uploads/');
                        if ($p2 !== false) $cert_web_path = substr($cert_web_path, $p2);
                        else $cert_web_path = 'uploads/' . basename($cert_web_path);
                    }
                    // show button only if file exists on disk
                    $full_fs_path = $student['certificate_path'];
                    if (file_exists($full_fs_path)) {
                        echo '<div style="text-align:center; margin-top:16px;">';
                        echo '<a href="' . htmlspecialchars($cert_web_path) . '" target="_blank" class="unver-btn unver-btn-primary" style="text-decoration:none;">View Certificate</a>';
                        echo '</div>';
                    }
                }
                ?>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 24px;">
                <a href="index.php" class="unver-btn" style="text-decoration: none;">← Back to Portal</a>
            </div>
        </div>
    </div>
</body>
</html>
