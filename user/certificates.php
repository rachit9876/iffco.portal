<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$certificate = null;

$stmt = $conn->prepare("SELECT certificate_path, issue_date FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $certificate = $result->fetch_assoc();
}
$stmt->close();

$page_title = "Certificates";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $page_title; ?> - IFFCO Portal</title>
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
        <a href="dashboard.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Dashboard</a>
        <a href="profile.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Profile & Details</a>
        <a href="projects.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Projects</a>
        <a href="certificates.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Certificates</a>
        <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
    </nav>
</aside>

<main style="flex: 1; padding: 40px;">
  <header class="unver-mb-xl">
    <h1 class="unver-h1">Your Certificate</h1>
    <p class="unver-text-muted">Download your vocational training certificate once it has been issued.</p>
  </header>

  <div class="unver-card" style="max-width: 800px; margin: 0 auto;">
    <div class="unver-card-body">
      <?php if ($certificate && !empty($certificate['certificate_path'])): ?>
        <div style="text-align: center;">
          <h2 class="unver-h2">Certificate Issued!</h2>
          <p class="unver-text-muted unver-mb-lg">
            Congratulations! Your certificate has been issued on <?php echo date("F j, Y", strtotime($certificate['issue_date'])); ?>.
          </p>
            <?php
            // Convert stored certificate filesystem path (absolute or relative) to a web-accessible relative path
            $cert_raw = $certificate['certificate_path'];
            $cert_web_path = str_replace('\\', '/', $cert_raw);
            // remove leading Windows drive letter if present (e.g. C:)
            $cert_web_path = preg_replace('#^[a-zA-Z]:#', '', $cert_web_path);
            // try to locate the 'user/' segment and take the path after it (so we get 'uploads/...')
            $pos = stripos($cert_web_path, '/user/');
            if ($pos !== false) {
              $cert_web_path = substr($cert_web_path, $pos + strlen('/user/'));
            } else {
              $pos = stripos($cert_web_path, 'user/');
              if ($pos !== false) $cert_web_path = substr($cert_web_path, $pos + strlen('user/'));
            }
            $cert_web_path = ltrim($cert_web_path, '/\\');
            // ensure path starts with uploads/ so it's relative to this `user/` folder
            if (stripos($cert_web_path, 'uploads/') !== 0) {
              $p2 = stripos($cert_web_path, 'uploads/');
              if ($p2 !== false) $cert_web_path = substr($cert_web_path, $p2);
              else $cert_web_path = 'uploads/' . basename($cert_web_path);
            }
            ?>
            <a href="<?php echo htmlspecialchars($cert_web_path); ?>" target="_blank" class="unver-btn unver-btn-primary" style="text-decoration: none;">
            View Certificate
          </a>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 40px 0;">
          <div style="width: 64px; height: 64px; margin: 0 auto 20px; background: var(--unver-warning); border: 4px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 32px;">🔒</span>
          </div>
          <h2 class="unver-h2">Certificate Locked</h2>
          <p class="unver-text-muted unver-mb-sm">Your certificate has not been issued yet.</p>
          <?php
          // Check eligibility for self-generation
          $eligible = false;
          $proj_stmt = $conn->prepare("SELECT status FROM projects WHERE user_id = ? LIMIT 1");
          $proj_stmt->bind_param("i", $user_id);
          $proj_stmt->execute();
          $proj_stmt->bind_result($proj_status);
          $proj_stmt->fetch();
          $proj_stmt->close();
          if (strtolower(trim($proj_status)) === 'completed') $eligible = true;
          ?>
          <?php if ($eligible): ?>
            <p class="unver-text-muted">You are eligible to generate your certificate.</p>
            <form method="POST" action="generate_certificate.php">
                <button type="submit" class="unver-btn unver-btn-success unver-w-full">Generate Certificate Now</button>
            </form>
          <?php else: ?>
            <p class="unver-text-muted">You are not eligible yet. Complete and submit your project (status must be 'Completed').</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
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
