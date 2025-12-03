<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Get pending applications count
$pending_count_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE status = 'pending'");
$pending_count = $pending_count_result->fetch_assoc()['total'];

// Fetch statistics
$total_students_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE role = 'user' AND status = 'approved'");
$total_students = $total_students_result->fetch_assoc()['total'];

$pending_apps_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE status = 'pending'");
$pending_apps = $pending_apps_result->fetch_assoc()['total'];

$dept_stats = [];
$depts = ['CS' => 5, 'HR' => 10, 'MBA' => 9];
foreach ($depts as $dept => $dept_id) {
    $stmt = $conn->prepare("SELECT COUNT(u.id) AS total FROM users u WHERE u.role = 'user' AND u.status = 'approved' AND u.department_id = ?");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dept_stats[$dept] = $result->fetch_assoc()['total'];
    $stmt->close();
}

$conn->close();
$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - IFFCO Portal</title>
    <link rel="stylesheet" href="../neobrutalist.css">
    <style>
        @media (max-width: 768px) {
            main {
                padding: 20px 16px !important;
                margin-top: 60px;
            }
            .unver-grid-4 {
                grid-template-columns: 1fr !important;
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
            <a href="dashboard.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Dashboard</a>
            <a href="students.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff;">Student Management</a>
            <a href="applications.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none; background: #374151; color: #fff; position: relative;">Applications <?php if ($pending_count > 0): ?><span style="position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; background: #22c55e; border-radius: 50%;"></span><?php endif; ?></a>
            <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
        </nav>
    </aside>

    <main style="flex: 1; padding: 40px;">
        <header class="unver-mb-xl">
            <h1 class="unver-h1">Admin Dashboard</h1>
            <p class="unver-text-muted">Overview of the Vocational Training Portal.</p>
        </header>

        <div class="unver-grid unver-grid-4 unver-mb-xl">
            <div class="unver-card" style="background: #3b82f6; color: #fff;">
                <div class="unver-card-body">
                    <p class="unver-text-sm unver-uppercase">Total Students</p>
                    <p class="unver-h1" style="margin: 0;"><?php echo $total_students; ?></p>
                </div>
            </div>
            <div class="unver-card" style="background: #eab308; color: #fff;">
                <div class="unver-card-body">
                    <p class="unver-text-sm unver-uppercase">Pending Applications</p>
                    <p class="unver-h1" style="margin: 0;"><?php echo $pending_apps; ?></p>
                    <a href="applications.php" style="color: #fff; text-decoration: underline; font-size: 14px;">View →</a>
                </div>
            </div>
            <div class="unver-card" style="background: #22c55e; color: #fff;">
                <div class="unver-card-body">
                    <p class="unver-text-sm unver-uppercase">CS Department</p>
                    <p class="unver-h1" style="margin: 0;"><?php echo $dept_stats['CS']; ?></p>
                </div>
            </div>
            <div class="unver-card" style="background: #6366f1; color: #fff;">
                <div class="unver-card-body">
                    <p class="unver-text-sm unver-uppercase">HR & MBA Depts</p>
                    <p class="unver-h1" style="margin: 0;"><?php echo $dept_stats['HR'] + $dept_stats['MBA']; ?></p>
                </div>
            </div>
        </div>

        <div class="unver-card">
            <div class="unver-card-body">
                <h2 class="unver-h2 unver-mb-md">Quick Actions</h2>
                <div class="unver-btn-group">
                    <a href="students.php?action=add" class="unver-btn unver-btn-primary" style="text-decoration: none;">Add New Student</a>
                    <a href="students.php?action=import" class="unver-btn unver-btn-success" style="text-decoration: none;">Bulk Import Students</a>
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
