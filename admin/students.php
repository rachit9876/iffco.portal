<?php
// --- Admin: Student Management ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../db_connect.php';

// Redirect if not admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Helper function to recursively delete a folder (defined once at the top)
function deleteFolder($folder) {
    if (!is_dir($folder)) return;
    foreach (scandir($folder) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = "$folder/$file";
        is_dir($path) ? deleteFolder($path) : unlink($path);
    }
    rmdir($folder);
}

// Auto-cert settings file
$settings_file = __DIR__ . '/auto_cert_settings.json';
$auto_cert_settings = ['enabled' => false, 'last_run' => null];
if (file_exists($settings_file)) {
    $json = file_get_contents($settings_file);
    $data = json_decode($json, true);
    if (is_array($data)) $auto_cert_settings = array_merge($auto_cert_settings, $data);
}

// Helper: generate certificate for a given student id. Returns true on success.
function generate_certificate_for_student($conn, $student_id) {
    $stmt = $conn->prepare("SELECT u.*, d.name as department, p.name as program, pr.project_name FROM users u LEFT JOIN departments d ON u.department_id = d.id LEFT JOIN programs p ON u.program_id = p.id LEFT JOIN projects pr ON u.id = pr.user_id WHERE u.id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) return false;

    $template_path = __DIR__ . '/../user/certificate_template.html';
    if (!file_exists($template_path)) return false;
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
    $html = str_replace('</body>', '\n    <button id="downloadBtn" style="position: fixed; top: 20px; right: 20px; padding: 15px 30px; background: #22c55e; color: white; border: 3px solid #000; font-weight: bold; cursor: pointer; z-index: 9999; box-shadow: 4px 4px 0 #000;">Download as Image</button>\n    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>\n    <script>\n        document.getElementById("downloadBtn").onclick = function() {\n            this.textContent = "Generating...";\n            const btn = this;\n            html2canvas(document.querySelector(".page-wrapper"), {\n                scale: 2,\n                useCORS: true,\n                backgroundColor: "#ffffff"\n            }).then(canvas => {\n                const link = document.createElement("a");\n                link.download = "IFFCO_Certificate_' . $student['roll_no'] . '.png";\n                link.href = canvas.toDataURL("image/png");\n                link.click();\n                btn.textContent = "Download as Image";\n            });\n        };\n    </script>\n    </body>', $html);

    $user_folder = __DIR__ . "/../user/uploads/{$student['roll_no']}";
    if (!file_exists($user_folder)) mkdir($user_folder, 0777, true);
    $cert_path = "$user_folder/certificate.html";
    file_put_contents($cert_path, $html);

    $stmt = $conn->prepare("INSERT INTO certificates (user_id, certificate_path, issue_date) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE certificate_path = ?, issue_date = NOW()");
    $stmt->bind_param("iss", $student_id, $cert_path, $cert_path);
    $stmt->execute();
    $stmt->close();
    return true;
}

// Get pending applications count
$pending_count_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE status = 'pending'");
$pending_count = $pending_count_result->fetch_assoc()['total'];



// Bulk Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_action']) && isset($_POST['student_ids'])) {
    $action = $_POST['bulk_action'];
    $student_ids = $_POST['student_ids'];
    
    if ($action === 'delete') {
        foreach ($student_ids as $student_id) {
            $stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ? AND role = 'user'");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->bind_result($roll_no);
            $stmt->fetch();
            $stmt->close();
            
            $upload_dir = "../user/uploads/$roll_no";
            deleteFolder($upload_dir);
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: students.php?msg=" . count($student_ids) . " student(s) deleted successfully");
        exit;
    } elseif ($action === 'auto_generate') {
        $count = 0;
        foreach ($student_ids as $student_id) {
            if (generate_certificate_for_student($conn, $student_id)) $count++;
        }
        header("Location: students.php?msg=Generated {$count} certificate(s) successfully");
        exit;
    }
}

// Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];

// Step 1: Get the roll_no first
$stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

// Step 2: Delete the folder (if it exists)
$upload_dir = "../user/uploads/$roll_no";
deleteFolder($upload_dir);

// Step 3: Delete the student from database
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $student_id);
if ($stmt->execute()) {
    header("Location: students.php?msg=Student and their folder deleted successfully");
} else {
    header("Location: students.php?error=Error deleting student");
}
$stmt->close();
exit;
}

// Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $department_id = intval($_POST['department']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Get department code
    $stmt = $conn->prepare("SELECT code FROM departments WHERE id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $stmt->bind_result($dept_code);
    $stmt->fetch();
    $stmt->close();

    // Auto-generate roll no
    $year = date('y');
    $full_year = date('Y');
    $like_pattern = $year . $dept_code . '%';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE roll_no LIKE ?");
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $next_number = $count + 1;
    $roll_no = $year . $dept_code . $next_number;

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, roll_no, department_id, role, status, batch, contact_info) VALUES (?, ?, ?, ?, ?, 'user', 'approved', ?, 'N/A')");
    $stmt->bind_param("ssssis", $name, $email, $hashed_password, $roll_no, $department_id, $full_year);
    
    if ($stmt->execute()) {
    // Create uploads folder only after student successfully added
    $upload_path = "../user/uploads/$roll_no";
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }
    header("Location: students.php?msg=Student added successfully. Roll No: $roll_no");
} else {
    header("Location: students.php?error=Error: " . urlencode($stmt->error));
}
    $stmt->close();
    exit;
}


// Single-student auto-generate (triggered from modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['single_generate']) && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    // Check eligibility: project must be Completed
    $stmt = $conn->prepare("SELECT p.status FROM projects p WHERE p.user_id = ? LIMIT 1");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($proj_status);
    $stmt->fetch();
    $stmt->close();

    if (strtolower(trim($proj_status)) !== 'completed') {
        header("Location: students.php?error=Student not eligible for auto-generation (project not completed)");
        exit;
    }

    if (generate_certificate_for_student($conn, $student_id)) {
        header("Location: students.php?msg=Certificate generated successfully for student");
    } else {
        header("Location: students.php?error=Failed to generate certificate for student");
    }
    exit;
}

// Update auto-cert toggle setting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_auto_toggle'])) {
    $enabled = isset($_POST['auto_cert_enabled']) && $_POST['auto_cert_enabled'] == '1';
    $auto_cert_settings['enabled'] = $enabled;
    if ($enabled) {
        // run generation for all eligible students now
        $sql = "SELECT u.id FROM users u INNER JOIN projects p ON u.id = p.user_id LEFT JOIN certificates c ON u.id = c.user_id WHERE u.role = 'user' AND u.status = 'approved' AND p.status = 'Completed' AND c.id IS NULL";
        $result = $conn->query($sql);
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            if (generate_certificate_for_student($conn, $row['id'])) $count++;
        }
        $auto_cert_settings['last_run'] = date('c');
        file_put_contents($settings_file, json_encode($auto_cert_settings));
        header("Location: students.php?msg=Auto-generation enabled and ran for {$count} student(s)");
    } else {
        $auto_cert_settings['last_run'] = $auto_cert_settings['last_run'];
        file_put_contents($settings_file, json_encode($auto_cert_settings));
        header("Location: students.php?msg=Auto-generation disabled");
    }
    exit;
}

// Revoke Certificate
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['revoke_certificate'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $conn->prepare("SELECT certificate_path FROM certificates WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($cert_path);
    $stmt->fetch();
    $stmt->close();
    
    if ($cert_path && file_exists($cert_path)) {
        unlink($cert_path);
    }
    
    $stmt = $conn->prepare("DELETE FROM certificates WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    if ($stmt->execute()) {
        header("Location: students.php?msg=Certificate revoked successfully");
    } else {
        header("Location: students.php?error=Error revoking certificate");
    }
    $stmt->close();
    exit;
}

// Auto Generate All Certificates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auto_generate_all'])) {
    $sql = "SELECT u.id FROM users u 
            INNER JOIN projects p ON u.id = p.user_id 
            LEFT JOIN certificates c ON u.id = c.user_id 
            WHERE u.role = 'user' AND u.status = 'approved' 
            AND p.status = 'Completed' 
            AND c.id IS NULL";
    
    $result = $conn->query($sql);
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];
        if (generate_certificate_for_student($conn, $student_id)) $count++;
    }
    
    header("Location: students.php?msg=Auto-generated {$count} certificate(s) successfully");
    exit;
}

// Fetch departments for dropdown
$departments_list = $conn->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");

// Fetch filter options
$programs_filter = $conn->query("SELECT DISTINCT id, name FROM programs WHERE is_active = 1 ORDER BY name");
$departments_filter = $conn->query("SELECT DISTINCT id, name FROM departments WHERE is_active = 1 ORDER BY name");

// Fetch all students
$students = [];
$sql = "SELECT u.id, u.name, u.email, u.roll_no, d.name as department, p.name as program, c.id as certificate_id 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN certificates c ON u.id = c.user_id 
        WHERE u.role = 'user' AND u.status = 'approved' 
        ORDER BY u.name";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$conn->close();

$page_title = "Trainee Management";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - IFFCO Portal</title>
    <link rel="stylesheet" href="../neobrutalist.css">
    <style>
        #addStudentModal, #issueCertModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        #addStudentModal.show, #issueCertModal.show { display: flex; }
        .modal-content { max-width: 500px; width: 90%; }
        .bulk-actions { display: flex; padding: 12px; background: #fef3c7; border: 3px solid #000; margin-bottom: 16px; gap: 10px; align-items: center; flex-wrap: wrap; }
        .bulk-actions span { font-weight: bold; }
        .bulk-actions button:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
        .bulk-actions select { padding: 6px 10px; border: 2px solid #000; background: #fff; font-size: 13px; }
        
        /* Mobile card view for students */
        .student-cards { display: none; }
        .student-table { display: block; }
        
        @media (max-width: 768px) {
            main {
                padding: 20px 12px !important;
                margin-top: 60px;
            }
            header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 16px;
            }
            header > div:last-child {
                display: flex;
                flex-direction: column;
                width: 100%;
                gap: 8px;
            }
            header > div:last-child button,
            header > div:last-child form {
                width: 100%;
            }
            header > div:last-child form button {
                width: 100%;
            }
            .unver-h1 {
                font-size: 24px !important;
            }
            
            /* Hide table, show cards on mobile */
            .student-table { display: none !important; }
            .student-cards { display: block !important; }
            
            .student-card {
                background: #fff;
                border: 3px solid #000;
                padding: 16px;
                margin-bottom: 12px;
                box-shadow: 4px 4px 0 #888;
            }
            .student-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
            }
            .student-card-name {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 4px;
            }
            .student-card-email {
                font-size: 12px;
                color: #666;
                word-break: break-all;
            }
            .student-card-info {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 12px;
                font-size: 13px;
            }
            .student-card-info span {
                color: #666;
            }
            .student-card-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .student-card-actions .unver-btn {
                flex: 1;
                min-width: calc(50% - 4px);
                text-align: center;
                justify-content: center;
            }
            .student-card-actions form {
                flex: 1;
                min-width: calc(50% - 4px);
            }
            .student-card-actions form button {
                width: 100%;
            }
            
            .unver-card-body {
                padding: 12px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; display: flex; background: #e5e5e5;">

<button class="unver-mobile-menu-btn" aria-label="Menu">
  <span class="unver-menu-icon">
    <span class="unver-menu-line unver-menu-line-1"></span>
    <span class="unver-menu-line unver-menu-line-2"></span>
    <span class="unver-menu-line unver-menu-line-3"></span>
  </span>
</button>

    <aside class="unver-sidebar unver-sidebar-dark">
        <div style="padding: 24px;">
            <h2 class="unver-h3" style="color: #fff;">IFFCO Admin</h2>
        </div>
        <nav style="padding: 0 16px;">
            <a href="dashboard.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff;">Dashboard</a>
            <a href="students.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Student Management</a>
            <a href="applications.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff; position: relative;">Applications <?php if ($pending_count > 0): ?><span style="position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; background: #22c55e; border-radius: 50%;"></span><?php endif; ?></a>
            <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
        </nav>
    </aside>

    <main style="flex: 1; padding: 40px;">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1 class="unver-h1"><?php echo $page_title; ?></h1>
                <p class="unver-text-muted">Manage all approved student accounts.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="openModal('addStudentModal')" class="unver-btn unver-btn-primary">Add Student</button>
            </div>
        </header>
        
        <div class="bulk-actions" id="bulkActions">
            <span id="selectedCount">0 selected</span>
            <button id="deleteBtn" onclick="bulkAction('delete')" class="unver-btn unver-btn-sm unver-btn-danger" disabled>Delete</button>
            <button id="generateBtn" onclick="bulkAction('auto_generate')" class="unver-btn unver-btn-sm unver-btn-success" disabled>Auto Generate Certificate</button>
            <div style="margin-left: auto; display: flex; gap: 8px; align-items: center;">
                <label style="font-size: 13px;">Filter:</label>
                <select id="programFilter" onchange="applyFilters()" class="unver-input unver-select" style="padding: 6px 10px; font-size: 13px;">
                    <option value="">All Programs</option>
                    <?php while($prog = $programs_filter->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($prog['name']); ?>"><?php echo htmlspecialchars($prog['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <select id="departmentFilter" onchange="applyFilters()" class="unver-input unver-select" style="padding: 6px 10px; font-size: 13px;">
                    <option value="">All Departments</option>
                    <?php while($dept = $departments_filter->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <select id="certFilter" onchange="applyFilters()" class="unver-input unver-select" style="padding: 6px 10px; font-size: 13px;">
                    <option value="">All Certificates</option>
                    <option value="issued">Issued</option>
                    <option value="not_issued">Not Issued</option>
                </select>
                <!-- Auto-cert toggle -->
                <form method="POST" action="students.php" style="display:inline; margin-left: 8px;">
                    <input type="hidden" name="update_auto_toggle" value="1">
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="auto_cert_enabled" value="1" <?php echo $auto_cert_settings['enabled'] ? 'checked' : ''; ?>> Auto-cert
                    </label>
                    <button type="submit" class="unver-btn unver-btn-sm" style="margin-left:6px;">Save</button>
                </form>
                <?php if ($auto_cert_settings['last_run']): ?>
                    <span style="font-size:12px; margin-left:8px; color:#444;">Last run: <?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($auto_cert_settings['last_run']))); ?></span>
                <?php endif; ?>
            </div>
        </div>
        


        <div class="unver-card">
            <div class="unver-card-body">
                <!-- Desktop Table View -->
                <div class="student-table" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f5f5f5; border-bottom: 4px solid #000;">
                                <th style="padding: 12px; text-align: center; font-weight: bold; width: 50px;">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                                </th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Name</th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Roll No.</th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Program</th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Department</th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Certificate</th>
                                <th style="padding: 12px; text-align: left; font-weight: bold;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="student-row" data-program="<?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?>" data-department="<?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>" data-cert="<?php echo $student['certificate_id'] ? 'issued' : 'not_issued'; ?>" style="border-bottom: 2px solid #e5e5e5;">
                                <td style="padding: 12px; text-align: center;">
                                    <input type="checkbox" class="student-checkbox" value="<?php echo $student['id']; ?>" onchange="updateBulkActions()" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <td style="padding: 12px;">
                                    <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                                    <span class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($student['email']); ?></span>
                                </td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></td>
                                <td style="padding: 12px;">
                                    <?php if ($student['certificate_id']): ?>
                                        <span class="unver-badge unver-badge-success">Issued</span>
                                        <form action="students.php" method="post" onsubmit="return confirm('Revoke this certificate?');" style="display: inline; margin-left: 8px;">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" name="revoke_certificate" class="unver-btn unver-btn-sm unver-btn-warning">Revoke</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="unver-badge unver-badge-warning">Not Issued</span>
                                        <button onclick="openCertModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($student['name'])); ?>')" class="unver-btn unver-btn-sm unver-btn-primary" style="margin-left: 8px;">Issue</button>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <div style="display: flex; gap: 10px;">
                                        <a href="view_docs.php?roll=<?php echo urlencode($student['roll_no']); ?>" target="_blank" class="unver-btn unver-btn-sm" style="text-decoration: none;">View</a>
                                        <form action="students.php" method="post" onsubmit="return confirm('Are you sure?');" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" name="delete_student" class="unver-btn unver-btn-sm unver-btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                            <tr><td colspan="7" style="padding: 40px; text-align: center;" class="unver-text-muted">No approved students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="student-cards">
                    <?php if (empty($students)): ?>
                        <p class="unver-text-muted" style="text-align: center; padding: 40px 0;">No approved students found.</p>
                    <?php endif; ?>
                    <?php foreach ($students as $student): ?>
                    <div class="student-card" data-program="<?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?>" data-department="<?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>" data-cert="<?php echo $student['certificate_id'] ? 'issued' : 'not_issued'; ?>">
                        <div class="student-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" class="student-checkbox" value="<?php echo $student['id']; ?>" onchange="updateBulkActions()" style="width: 18px; height: 18px; cursor: pointer;">
                                <div>
                                    <div class="student-card-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                    <div class="student-card-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                            </div>
                            <?php if ($student['certificate_id']): ?>
                                <span class="unver-badge unver-badge-success">Issued</span>
                            <?php else: ?>
                                <span class="unver-badge unver-badge-warning">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="student-card-info">
                            <div><span>Roll No:</span> <?php echo htmlspecialchars($student['roll_no']); ?></div>
                            <div><span>Dept:</span> <?php echo htmlspecialchars($student['department']); ?></div>
                        </div>
                        <div class="student-card-actions">
                            <a href="view_docs.php?roll=<?php echo urlencode($student['roll_no']); ?>" target="_blank" class="unver-btn unver-btn-sm" style="text-decoration: none;">View Docs</a>
                            <?php if ($student['certificate_id']): ?>
                                <form action="students.php" method="post" onsubmit="return confirm('Revoke this certificate?');">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="revoke_certificate" class="unver-btn unver-btn-sm unver-btn-warning">Revoke Cert</button>
                                </form>
                            <?php else: ?>
                                <button onclick="openCertModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($student['name'])); ?>')" class="unver-btn unver-btn-sm unver-btn-primary">Issue Cert</button>
                            <?php endif; ?>
                            <form action="students.php" method="post" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" class="unver-btn unver-btn-sm unver-btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>


    <div id="addStudentModal">
        <div class="unver-card modal-content">
            <div class="unver-card-body">
                <h3 class="unver-h3 unver-text-center unver-mb-md">Add New Student</h3>
                <form action="students.php" method="POST">
                    <input class="unver-input unver-mb-sm" type="text" name="name" placeholder="Full Name" required>
                    <input class="unver-input unver-mb-sm" type="email" name="email" placeholder="Email" required>
                    <input class="unver-input unver-mb-sm" type="password" name="password" placeholder="Password" required>
                    <select name="department" class="unver-input unver-select unver-mb-md" required>
                        <option value="">Select Department</option>
                        <?php while($dept = $departments_list->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="add_student" class="unver-btn unver-btn-primary unver-w-full">Add Student</button>
                </form>
                <button onclick="closeModal('addStudentModal')" class="unver-btn unver-btn-sm unver-mt-sm" style="position: absolute; top: 10px; right: 10px;">&times;</button>
            </div>
        </div>
    </div>
    
    <div id="issueCertModal">
        <div class="unver-card modal-content">
            <div class="unver-card-body">
                <h3 class="unver-h3 unver-text-center">Issue Certificate</h3>
                <p id="issueCertStudentName" class="unver-text-center unver-text-muted unver-mb-md"></p>
                
                <form id="autoForm" action="students.php" method="POST" style="display: block;">
                    <input type="hidden" name="single_generate" value="1">
                    <input type="hidden" id="autoStudentId" name="student_id">
                    <p class="unver-text-sm unver-text-muted unver-mb-md">This will automatically generate a certificate using the student's data and the standard template (only if the project status is 'Completed').</p>
                    <button type="submit" class="unver-btn unver-btn-success unver-w-full">Generate Certificate</button>
                </form>
                
                <button onclick="closeModal('issueCertModal')" class="unver-btn unver-btn-sm unver-mt-sm" style="position: absolute; top: 10px; right: 10px;">&times;</button>
            </div>
        </div>
    </div>
    
    <script src="../toast.js"></script>
    <script>
        <?php if (isset($_GET['msg'])): ?>showToast(<?php echo json_encode($_GET['msg']); ?>, 'success');<?php endif; ?>
        <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
        function openModal(modalId) { document.getElementById(modalId).classList.add('show'); }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('show'); }
        function openCertModal(studentId, studentName) {
            document.getElementById('autoStudentId').value = studentId;
            document.getElementById('issueCertStudentName').innerText = 'For: ' + studentName;
            document.getElementById('autoForm').style.display = 'block';
            openModal('issueCertModal');
        }
        
        const menuButton = document.querySelector('.unver-mobile-menu-btn');
        const sidebar = document.querySelector('.unver-sidebar');
        menuButton.addEventListener('click', function () {
            this.classList.toggle('active');
            sidebar.classList.toggle('active');
        });
        if (window.innerWidth <= 768) {
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menuButton.classList.remove('active');
                    sidebar.classList.remove('active');
                });
            });
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedCount = document.getElementById('selectedCount');
            const deleteBtn = document.getElementById('deleteBtn');
            const generateBtn = document.getElementById('generateBtn');
            
            selectedCount.textContent = checkboxes.length + ' selected';
            
            if (checkboxes.length > 0) {
                deleteBtn.disabled = false;
                generateBtn.disabled = false;
            } else {
                deleteBtn.disabled = true;
                generateBtn.disabled = true;
            }
            
            document.getElementById('selectAll').checked = checkboxes.length === document.querySelectorAll('.student-checkbox').length;
        }
        
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkboxes.length === 0) return;
            
            let confirmMsg = '';
            if (action === 'delete') {
                confirmMsg = 'Delete ' + checkboxes.length + ' student(s)?';
            } else if (action === 'auto_generate') {
                confirmMsg = 'Generate certificates for ' + checkboxes.length + ' student(s)?';
            }
            
            if (!confirm(confirmMsg)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'students.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'student_ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function applyFilters() {
            const programFilter = document.getElementById('programFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;
            const certFilter = document.getElementById('certFilter').value;
            const rows = document.querySelectorAll('.student-row');
            const cards = document.querySelectorAll('.student-card');
            
            rows.forEach(row => {
                const program = row.getAttribute('data-program');
                const department = row.getAttribute('data-department');
                const cert = row.getAttribute('data-cert');
                
                let show = true;
                if (programFilter && program !== programFilter) show = false;
                if (departmentFilter && department !== departmentFilter) show = false;
                if (certFilter && cert !== certFilter) show = false;
                
                row.style.display = show ? '' : 'none';
            });
            
            cards.forEach(card => {
                const program = card.getAttribute('data-program');
                const department = card.getAttribute('data-department');
                const cert = card.getAttribute('data-cert');
                
                let show = true;
                if (programFilter && program !== programFilter) show = false;
                if (departmentFilter && department !== departmentFilter) show = false;
                if (certFilter && cert !== certFilter) show = false;
                
                card.style.display = show ? '' : 'none';
            });
            
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }
    </script>
</body>
</html>