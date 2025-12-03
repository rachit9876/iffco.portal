<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['user', 'admin'])) {
    header("location: ../index.php");
    exit;
}

if ($_SESSION['role'] === 'admin' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $is_admin_viewing_student = true;
} else {
    $user_id = $_SESSION['id'];
    $is_admin_viewing_student = false;
}


$can_edit = ($_SESSION['role'] === 'user' && $user_id == $_SESSION['id']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contact']) && $can_edit) {
    $new_contact = trim($_POST['contact_info']);
    $new_name = trim($_POST['name']);
    $new_college = strtoupper(trim($_POST['college']));

    if (!empty($new_name)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, contact_info = ?, college = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_name, $new_contact, $new_college, $user_id);
        if ($stmt->execute()) {
            header("Location: profile.php?msg=Profile updated successfully");
        } else {
            header("Location: profile.php?error=Error updating profile");
        }
        $stmt->close();
        exit;
    } else {
        header("Location: profile.php?error=Name cannot be empty");
        exit;
    }
}

// Handle GitHub disconnect
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['disconnect_github']) && $can_edit) {
    $stmt = $conn->prepare("UPDATE users SET github_id = NULL, github_email = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header("Location: profile.php?msg=GitHub account disconnected successfully");
    } else {
        header("Location: profile.php?error=Error disconnecting GitHub account");
    }
    $stmt->close();
    exit;
}

// Query to fetch user profile - always use JOINs to get department/program names
$sql = "SELECT u.name, u.roll_no, d.name as department, u.batch, u.contact_info, u.email, u.github_id, u.github_email, p.name as program, u.college 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        LEFT JOIN programs p ON u.program_id = p.id 
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $roll_no, $department, $batch, $contact_info, $email, $github_id, $github_email, $program, $college);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - IFFCO Portal</title>
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
        <a href="profile.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Profile & Details</a>
        <a href="projects.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Projects</a>
        <a href="certificates.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Certificates</a>
        <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
    </nav>
</aside>

<main style="flex: 1; padding: 40px;">
    <header class="unver-mb-xl">
      <h1 class="unver-h1">Profile & Details</h1>
      <p class="unver-text-muted">
        <?php echo $can_edit ? 'Update your profile details below.' : 'Viewing profile (read-only).'; ?>
      </p>
    </header>



    <div class="unver-card unver-mb-lg">
      <div class="unver-card-body">
        <h3 class="unver-h3 unver-mb-md">Read-Only Information</h3>

        <div class="unver-form-group">
          <label class="unver-label">Email Address</label>
          <input type="email" value="<?php echo htmlspecialchars($email); ?>" class="unver-input" readonly>
        </div>

        <div class="unver-form-group">
          <label class="unver-label">Roll Number</label>
          <input type="text" value="<?php echo htmlspecialchars($roll_no); ?>" class="unver-input" readonly>
        </div>

        <div class="unver-form-group">
          <label class="unver-label">Program</label>
          <input type="text" value="<?php echo htmlspecialchars($program); ?>" class="unver-input" readonly>
        </div>

        <div class="unver-form-group">
          <label class="unver-label">Department</label>
          <input type="text" value="<?php echo htmlspecialchars($department); ?>" class="unver-input" readonly>
        </div>
        <div class="unver-form-group">
          <label class="unver-label">Batch</label>
          <input type="text" value="<?php echo htmlspecialchars($batch); ?>" class="unver-input" readonly>
        </div>
      </div>
    </div>

    <form id="profile-form" action="profile.php<?php echo ($is_admin_viewing_student) ? '?id=' . intval($_GET['id']) : ''; ?>" method="POST" class="unver-card">
      <div class="unver-card-body">
        <h3 class="unver-h3 unver-mb-md">Editable Information</h3>

        <div class="unver-form-group">
          <label class="unver-label">Full Name <span class="unver-text-muted">(will be shown on certificate)</span></label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="unver-input" <?php echo $can_edit ? '' : 'readonly'; ?>>
        </div>

        <div class="unver-form-group">
          <label class="unver-label">Contact Info</label>
          <input type="text" name="contact_info" value="<?php echo htmlspecialchars($contact_info); ?>" class="unver-input" <?php echo $can_edit ? '' : 'readonly'; ?>>
        </div>

        <div class="unver-form-group">
          <label class="unver-label">Institute / College</label>
          <input type="text" name="college" value="<?php echo htmlspecialchars($college); ?>" class="unver-input" <?php echo $can_edit ? '' : 'readonly'; ?>>
        </div>

        <?php if ($can_edit): ?>
        <div style="display:flex;gap:12px;align-items:center;margin-top:12px;">
        <button type="submit" name="update_contact" class="unver-btn unver-btn-primary">
          Update Profile
        </button>

        <?php if (empty($github_id)): ?>
          <a href="/admin/auth/redirect.php?action=connect" class="unver-btn" style="background:#24292e;color:#fff;text-decoration:none;">Connect GitHub</a>
        <?php else: ?>
          <span class="unver-text-sm" style="display:inline-block;padding:10px;background:#e6f0ff;border-radius:6px;">GitHub connected: <?php echo htmlspecialchars($github_email ?? $github_id); ?></span>
          <form action="profile.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to disconnect your GitHub account?');">
            <button type="submit" name="disconnect_github" class="unver-btn unver-btn-danger">Disconnect</button>
          </form>
        <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </form>
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
