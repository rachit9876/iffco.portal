<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$username = isset($_SESSION["name"]) ? htmlspecialchars($_SESSION["name"]) : "User";
$user_id = $_SESSION['id'];

// Check if certificate is issued
$stmt = $conn->prepare("SELECT issue_date FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();
$stmt->close();

// Fetch user's roll number for documents
$stmt2 = $conn->prepare("SELECT roll_no FROM users WHERE id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$stmt2->bind_result($roll_no);
$stmt2->fetch();
$stmt2->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IFFCO Portal</title>
    <link rel="stylesheet" href="../neobrutalist.css">
</head>
<body style="margin: 0; display: flex; background: #f5f5f5;">

<button class="unver-mobile-menu-btn" aria-label="Menu">
  <span class="unver-menu-icon">
    <span class="unver-menu-line unver-menu-line-1"></span>
    <span class="unver-menu-line unver-menu-line-2"></span>
    <span class="unver-menu-line unver-menu-line-3"></span>
  </span>
</button>

<aside class="unver-sidebar">
    <div style="padding: 24px; text-align: center;">
        <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" style="max-width: 120px; margin: 0 auto;">
    </div>
    <nav style="padding: 0 16px;">
        <a href="dashboard.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Dashboard</a>
        <a href="profile.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Profile & Details</a>
        <a href="projects.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Projects</a>
        <a href="certificates.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Certificates</a>
        <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
    </nav>
</aside>

<main style="flex: 1; padding: 40px;">
    <header class="unver-mb-xl">
        <h1 class="unver-h1">Welcome, <?php echo $username; ?>!</h1>
        <p class="unver-text-muted">This is your central hub for managing your vocational training progress.</p>
    </header>

    <div class="unver-grid unver-grid-3 unver-mb-xl">
        <div class="unver-card">
            <div class="unver-card-body">
                <h3 class="unver-h3">Your Profile</h3>
                <p class="unver-text-muted unver-mb-md">View and update your personal details.</p>
                <a href="profile.php" class="unver-btn unver-btn-primary" style="text-decoration: none;">Go to Profile</a>
            </div>
        </div>

        <div class="unver-card">
            <div class="unver-card-body">
                <h3 class="unver-h3">Your Projects</h3>
                <p class="unver-text-muted unver-mb-md">Submit and track your project status.</p>
                <a href="projects.php" class="unver-btn unver-btn-success" style="text-decoration: none;">Manage Projects</a>
            </div>
        </div>

        <div class="unver-card">
            <div class="unver-card-body">
                <h3 class="unver-h3">Your Certificates</h3>
                <p class="unver-text-muted unver-mb-md">Download your certificate once issued.</p>
                <a href="certificates.php" class="unver-btn unver-btn-warning" style="text-decoration: none;">View Certificates</a>
            </div>
        </div>
    </div>

    <div class="unver-card">
        <div class="unver-card-body">
            <h2 class="unver-h2">Announcements</h2>
            <?php if ($certificate): ?>
                <div class="unver-card" style="background: #d1fae5; border: 3px solid #10b981; margin-bottom: 16px;">
                    <div class="unver-card-body">
                        <h3 class="unver-h3" style="color: #065f46; margin: 0 0 8px 0;">🎉 Certificate Issued!</h3>
                        <p style="margin: 0; color: #047857;">Congratulations! Your training certificate has been issued on <?php echo date('F j, Y', strtotime($certificate['issue_date'])); ?>. You can now view and download it from the Certificates section.</p>
                        <a href="certificates.php" class="unver-btn unver-btn-success" style="text-decoration: none; margin-top: 12px; display: inline-block;">View Certificate</a>
                    </div>
                </div>
            <?php else: ?>
                <p class="unver-text-muted">No new announcements at this time. Please check back later for updates on your training schedule, new projects, or events.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="unver-card unver-mt-lg">
        <div class="unver-card-body">
            <h2 class="unver-h2">Your Documents</h2>
            <p class="unver-text-muted">Files uploaded for your roll number will appear here.</p>
            <?php
            $docs_base = __DIR__ . '/uploads/'; // path: user/uploads/<roll_no>/
            // Use a relative web base so the site works when hosted in a subfolder
            $web_base = 'uploads/';
            if (!empty($roll_no)) {
                $user_folder = $docs_base . $roll_no . '/';

                // Required docs we want the user to upload if missing
                $required = ['noc.pdf' => 'noc', 'referral.pdf' => 'referral'];

                echo '<div style="margin-bottom:12px;">';
                foreach ($required as $filename => $doc_key) {
                    $path = $user_folder . $filename;
                    if (!file_exists($path)) {
                        // show upload form for this missing doc
                        echo '<form action="upload_doc.php" method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">';
                        echo '<input type="file" name="docfile" accept="application/pdf" required style="flex:1;">';
                        echo '<input type="hidden" name="doc_type" value="' . htmlspecialchars($doc_key) . '">';
                        echo '<button type="submit" class="unver-btn">Upload ' . htmlspecialchars(strtoupper($doc_key)) . '</button>';
                        echo '</form>';
                    } else {
                        // show small badge that it's present
                        echo '<div style="margin-bottom:6px;">' . htmlspecialchars($filename) . ' — <span style="color:green;font-weight:600;">Present</span></div>';
                    }
                }
                echo '</div>';

                if (is_dir($user_folder)) {
                    $files = array_values(array_filter(scandir($user_folder), function($f){ return $f !== '.' && $f !== '..'; }));
                    if (count($files) > 0) {
                        echo '<ul style="list-style:none;padding:0;margin:0;">';
                        foreach ($files as $file) {
                            $file_path = $user_folder . $file;
                            $href = $web_base . rawurlencode($roll_no) . '/' . rawurlencode($file);
                            $size = filesize($file_path);
                            $size_kb = round($size / 1024, 1);
                            $mtime = date('F j, Y, g:i a', filemtime($file_path));
                            echo '<li style="margin-bottom:10px;padding:10px;border:1px solid #eee;border-radius:6px;display:flex;justify-content:space-between;align-items:center;">';
                            echo '<div><strong>' . htmlspecialchars($file) . '</strong><div class="unver-text-muted">' . $size_kb . ' KB · ' . $mtime . '</div></div>';
                            echo '<div><a href="' . $href . '" class="unver-btn" style="text-decoration:none;">Download</a></div>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="unver-text-muted">No files found in your uploads folder.</p>';
                    }
                } else {
                    echo '<p class="unver-text-muted">No uploads folder found for your roll number (' . htmlspecialchars($roll_no) . ').</p>';
                }
            } else {
                echo '<p class="unver-text-muted">Your roll number is not set. Documents are stored by roll number.</p>';
            }
            ?>
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

<script src="../toast.js"></script>
<script>
        <?php if (isset($_GET['msg'])): ?>showToast(<?php echo json_encode($_GET['msg']); ?>, 'success');<?php endif; ?>
        <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
</script>

</body>
</html>
