<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

require_once '../db_connect.php'; // <-- required for DB fetch

// Get pending applications count
$pending_count_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE status = 'pending'");
$pending_count = $pending_count_result->fetch_assoc()['total'];

$roll_no = isset($_GET['roll']) ? basename($_GET['roll']) : '';
$upload_dir = "../user/uploads/$roll_no";

// --- Fetch student data ---
$student = null;
if ($roll_no) {
    $stmt = $conn->prepare("SELECT u.*, d.name as department, p.name as program FROM users u LEFT JOIN departments d ON u.department_id = d.id LEFT JOIN programs p ON u.program_id = p.id WHERE u.roll_no = ? AND u.role = 'user'");
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

// --- Scan uploaded files ---
$project_zip = null;
$report_file = null;
$referral_files = [];
$noc_files = [];
$other_files = [];

if ($roll_no && is_dir($upload_dir)) {
    foreach (scandir($upload_dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $full_path = "$upload_dir/$file";

        if (is_file($full_path)) {
            $label = '';
            if (strtolower($file) === 'project.zip') $label = 'Project (ZIP)';
            elseif (strtolower($file) === 'report.pdf') $label = 'Report';
            elseif (str_starts_with($file, 'ref_')) $label = 'Referral Document';
            elseif (str_starts_with($file, 'noc_')) $label = 'NOC Document';
            else $label = 'Document';

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $icon = match ($ext) {
                'pdf' => 'fa-file-pdf text-red-500',
                'jpg', 'jpeg', 'png' => 'fa-file-image text-blue-500',
                'doc', 'docx' => 'fa-file-word text-indigo-500',
                default => 'fa-file text-gray-500'
            };

            $file_data = [
                'name' => $file,
                'label' => $label,
                'icon' => $icon,
                'url' => "$full_path"
            ];

            if (strtolower($file) === 'project.zip') {
                $project_zip = $file_data;
            } elseif (strtolower($file) === 'report.pdf') {
                $report_file = $file_data;
            } elseif (str_starts_with($file, 'ref_')) {
                $referral_files[] = $file_data;
            } elseif (str_starts_with($file, 'noc_')) {
                $noc_files[] = $file_data;
            } else {
                $other_files[] = $file_data;
            }
        }
    }
}

// --- Fetch project with GitHub URL ---
$project = null;
if ($student) {
    $stmt = $conn->prepare("SELECT file_path, project_name FROM projects WHERE user_id = ?");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docs - <?php echo htmlspecialchars($roll_no); ?></title>
    <link rel="stylesheet" href="../neobrutalist.css">
    <style>
        @media (max-width: 768px) {
            main {
                padding: 20px 12px !important;
                margin-top: 60px;
            }
            .unver-grid-2 {
                grid-template-columns: 1fr !important;
            }
            .unver-h2 {
                font-size: 20px !important;
            }
            /* Document card mobile layout */
            .doc-card-content {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px;
            }
            .doc-card-content a.unver-btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
            .doc-card-info {
                width: 100%;
            }
            .doc-card-info .unver-text-sm {
                word-break: break-all;
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
            <a href="students.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff;">Student Management</a>
            <a href="applications.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff; position: relative;">Applications <?php if ($pending_count > 0): ?><span style="position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; background: #22c55e; border-radius: 50%;"></span><?php endif; ?></a>
            <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
        </nav>
    </aside>

    <main style="flex: 1; padding: 40px;">

        <?php if ($student): ?>
        <div class="unver-card unver-mb-lg">
            <div class="unver-card-body">
                <h2 class="unver-h2 unver-mb-md">Student Details</h2>
                <div class="unver-grid unver-grid-2 unver-gap-md">
                    <div><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
                    <div><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_no']); ?></div>
                    <div><strong>Department:</strong> <?php echo htmlspecialchars($student['department']); ?></div>
                    <div><strong>Batch:</strong> <?php echo htmlspecialchars($student['batch']); ?></div>
                    <div><strong>Semester:</strong> <?php echo htmlspecialchars($student['semester']); ?></div>
                    <div><strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?></div>
                    <div><strong>College:</strong> <?php echo htmlspecialchars($student['college']); ?></div>
                    <div><strong>Duration:</strong> <?php echo htmlspecialchars($student['duration']); ?></div>
                    <div><strong>Referral Type:</strong> <?php echo htmlspecialchars($student['referral_type']); ?></div>
                    <div><strong>Contact Info:</strong> <?php echo htmlspecialchars($student['contact_info']); ?></div>
                    <div><strong>Status:</strong> <span class="unver-badge unver-badge-<?php echo $student['status'] === 'approved' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($student['status']); ?></span></div>
                    <div><strong>Registered At:</strong> <?php echo htmlspecialchars($student['created_at']); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="unver-card">
            <div class="unver-card-body">
                <h2 class="unver-h2 unver-mb-lg">Documents for Roll No: <?php echo htmlspecialchars($roll_no); ?></h2>

                <?php if ($project && !empty($project['file_path']) && strpos($project['file_path'], 'http') === 0): ?>
                    <div class="unver-card" style="background: #fff3cd; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold">Project (GitHub Repository)</div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($project['project_name']); ?></div>
                            </div>
                            <a href="<?php echo htmlspecialchars($project['file_path']); ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">View GitHub</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($project_zip): ?>
                    <div class="unver-card" style="background: #fff3cd; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold"><?php echo $project_zip['label']; ?></div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($project_zip['name']); ?></div>
                            </div>
                            <a href="<?php echo $project_zip['url']; ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">Download</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($report_file): ?>
                    <div class="unver-card" style="background: #f9f9f9; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold"><?php echo $report_file['label']; ?></div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($report_file['name']); ?></div>
                            </div>
                            <a href="<?php echo $report_file['url']; ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">Open</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($referral_files as $f): ?>
                    <div class="unver-card" style="background: #f9f9f9; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold"><?php echo $f['label']; ?></div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($f['name']); ?></div>
                            </div>
                            <a href="<?php echo $f['url']; ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($noc_files as $f): ?>
                    <div class="unver-card" style="background: #f9f9f9; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold"><?php echo $f['label']; ?></div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($f['name']); ?></div>
                            </div>
                            <a href="<?php echo $f['url']; ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($other_files as $f): ?>
                    <div class="unver-card" style="background: #f9f9f9; margin-bottom: 16px;">
                        <div class="unver-card-body doc-card-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="doc-card-info">
                                <div class="unver-font-bold"><?php echo $f['label']; ?></div>
                                <div class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($f['name']); ?></div>
                            </div>
                            <a href="<?php echo $f['url']; ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($project_zip) && empty($report_file) && empty($referral_files) && empty($noc_files) && empty($other_files)): ?>
                    <p class="unver-text-muted">No uploaded documents found.</p>
                <?php endif; ?>

                <div class="unver-mt-lg">
                    <a href="students.php" class="unver-btn unver-btn-sm" style="text-decoration: none;">&larr; Back to Students</a>
                </div>
            </div>
        </div>
    </main>

<script>
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
</script>

</body>
</html>
