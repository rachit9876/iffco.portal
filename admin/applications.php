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



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            header("Location: applications.php?msg=Application approved");
        } else {
            header("Location: applications.php?error=Error approving application");
        }
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            header("Location: applications.php?msg=Application rejected and removed");
        } else {
            header("Location: applications.php?error=Error rejecting application");
        }
    }
    $stmt->close();
    exit;
}

$applications = [];
$sql = "SELECT u.id, u.name, u.email, u.roll_no, d.name as department, u.batch, u.contact_info, u.created_at, u.noc_path, u.referral_path 
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.status = 'pending' 
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$conn->close();

$page_title = "New Applications";
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
        <a href="applications.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none; position: relative;">Applications <?php if ($pending_count > 0): ?><span style="position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; background: #22c55e; border-radius: 50%;"></span><?php endif; ?></a>
        <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
    </nav>
</aside>

<main style="flex: 1; padding: 40px;">
    <header class="unver-mb-xl">
        <h1 class="unver-h1"><?php echo $page_title; ?></h1>
        <p class="unver-text-muted">Review and process new trainee sign-up requests.</p>
    </header>



    <div class="unver-card">
        <div class="unver-card-body">
            <?php if (empty($applications)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <h3 class="unver-h3">No pending applications.</h3>
                    <p class="unver-text-muted">All applications have been processed.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($applications as $app): ?>
                <div class="unver-card unver-mb-md">
                    <div class="unver-card-body">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div style="flex: 1;">
                                <p class="unver-font-bold unver-text-lg"><?php echo htmlspecialchars($app['name']); ?></p>
                                <p class="unver-text-sm unver-text-muted"><?php echo htmlspecialchars($app['email']); ?> | Roll: <?php echo htmlspecialchars($app['roll_no']); ?></p>
                                <p class="unver-text-sm unver-text-muted">Dept: <?php echo htmlspecialchars($app['department']); ?> | Batch: <?php echo htmlspecialchars($app['batch']); ?></p>
                            </div>
                            <div class="unver-btn-group">
                                <a href="view_docs.php?roll=<?php echo urlencode($app['roll_no']); ?>" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">See Docs</a>
                                <form action="applications.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="unver-btn unver-btn-sm unver-btn-success">Approve</button>
                                </form>
                                <form action="applications.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this application?');">
                                    <input type="hidden" name="user_id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="unver-btn unver-btn-sm unver-btn-danger">Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script src="../toast.js"></script>
<script>
    <?php if (isset($_GET['msg'])): ?>showToast(<?php echo json_encode($_GET['msg']); ?>, 'success');<?php endif; ?>
    <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
    
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
