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

$update_success = '';
$update_error = '';
$can_edit = ($_SESSION['role'] === 'user' && $user_id == $_SESSION['id']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contact']) && $can_edit) {
    $new_contact = trim($_POST['contact_info']);
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);

    if (!empty($new_contact) && !empty($new_name) && !empty($new_email)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, contact_info = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_name, $new_email, $new_contact, $user_id);
        $update_success = $stmt->execute() ? "Profile updated successfully!" : "Error updating profile.";
        $stmt->close();
    } else {
        $update_error = "Name, email, and contact information cannot be empty.";
    }
}

$stmt = $conn->prepare("SELECT name, roll_no, department, batch, contact_info, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $roll_no, $department, $batch, $contact_info, $email);
$stmt->fetch();
$stmt->close();
$conn->close();

$page_title = "Profile & Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?> - IFFCO Portal</title>
  <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script> Back Up--> 
  <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>

<style>
  body { font-family: 'Inter', sans-serif; }

  .mobile-menu-button {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 50;
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px;
    outline: none;
    -webkit-tap-highlight-color: transparent;
  }

  .mobile-menu-button:focus {
    outline: 2px solid rgba(255, 255, 255, 0.7);
    outline-offset: 2px;
  }

  .menu-icon {
    display: block;
    position: relative;
    width: 24px;
    height: 18px;
  }

  .line {
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #36015d;
    transition: all 0.3s ease;
  }

  .line-1 { top: 0; }
  .line-2 { top: 50%; transform: translateY(-50%); }
  .line-3 { bottom: 0; }

  .mobile-menu-button.active .line-1 {
    transform: translateY(8px) rotate(45deg);
  }
  .mobile-menu-button.active .line-2 {
    opacity: 0;
  }
  .mobile-menu-button.active .line-3 {
    transform: translateY(-8px) rotate(-45deg);
  }

  @media (max-width: 768px) {
    .mobile-menu-button {
      display: block;
    }

    .sidebar {
      transform: translateX(-100%);
      position: fixed;
      z-index: 40;
      transition: transform 0.3s ease-in-out;
      height: 100vh;
      overflow-y: auto;
    }

    .sidebar.active {
      transform: translateX(0);
    }

    main {
      margin-left: 0 !important;
    }
  }
</style>


</head>
<body class="bg-gray-100 flex">

<!-- Mobile Menu Button -->
<button class="mobile-menu-button" aria-label="Menu">
  <span class="menu-icon">
    <span class="line line-1"></span>
    <span class="line line-2"></span>
    <span class="line line-3"></span>
  </span>
</button>


  <!-- Sidebar -->
<aside class="sidebar w-64 min-h-screen bg-white shadow-md">
    <div class="p-6">
      <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" class="mx-auto mb-6">
    </div>
    <nav class="px-4 space-y-2">
      <a href="dashboard.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
        <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i><span>Dashboard</span>
      </a>
      <a href="profile.php" class="flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <i class="fas fa-user w-6 h-6 mr-3"></i><span>Profile & Details</span>
      </a>
      <a href="projects.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
        <i class="fas fa-project-diagram w-6 h-6 mr-3"></i><span>Projects</span>
      </a>
      <a href="certificates.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
        <i class="fas fa-certificate w-6 h-6 mr-3"></i><span>Certificates</span>
      </a>
      <a href="../logout.php" class="flex items-center p-3 text-red-600 rounded-lg hover:bg-red-100 mt-8">
        <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-10">
    <header class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
      <p class="text-gray-600">
        <?php if ($can_edit): ?>
          Update your profile details below.
        <?php elseif ($_SESSION['role'] === 'admin'): ?>
          Viewing student profile (read-only).
        <?php endif; ?>
      </p>
    </header>

    <?php if ($update_success): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6"><?php echo htmlspecialchars($update_success); ?></div>
    <?php endif; ?>
    <?php if ($update_error): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6"><?php echo htmlspecialchars($update_error); ?></div>
    <?php endif; ?>

    <form action="profile.php<?php echo ($is_admin_viewing_student) ? '?id=' . intval($_GET['id']) : ''; ?>" method="POST" class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Trainee Information</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          <?php echo $can_edit ? 'Edit the fields and click update.' : 'Fields are read-only.'; ?>
        </p>
      </div>
      <div class="border-t border-gray-200">
        <dl class="divide-y divide-gray-200">

          <!-- Name -->
          <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2">
              <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="w-full border-gray-300 rounded-md shadow-sm" <?php echo $can_edit ? '' : 'readonly'; ?>>
            </dd>
          </div>

          <!-- Email -->
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Email Address</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2">
              <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full border-gray-300 rounded-md shadow-sm" <?php echo $can_edit ? '' : 'readonly'; ?>>
            </dd>
          </div>

          <!-- Roll No -->
          <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Roll Number</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($roll_no); ?></dd>
          </div>

          <!-- Department -->
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Department</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2">
              <input type="text" value="<?php echo htmlspecialchars($department); ?>" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
            </dd>
          </div>

          <!-- Batch -->
          <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Batch</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2">
              <input type="text" value="<?php echo htmlspecialchars($batch); ?>" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
            </dd>
          </div>

          <!-- Contact Info -->
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Contact Info</dt>
            <dd class="mt-1 sm:mt-0 sm:col-span-2">
              <input type="text" name="contact_info" value="<?php echo htmlspecialchars($contact_info); ?>" class="w-full border-gray-300 rounded-md shadow-sm" <?php echo $can_edit ? '' : 'readonly'; ?>>
            </dd>
          </div>

        </dl>
      </div>

      <?php if ($can_edit): ?>
      <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
        <button type="submit" name="update_contact" class="py-2 px-4 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          Update Profile
        </button>
      </div>
      <?php endif; ?>
    </form>
  </main>
<script>
  // Toggle mobile menu
  const menuButton = document.querySelector('.mobile-menu-button');
  const sidebar = document.querySelector('.sidebar');

  menuButton.addEventListener('click', function () {
    this.classList.toggle('active');
    sidebar.classList.toggle('active');
  });

  // Close sidebar on nav click (mobile only)
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