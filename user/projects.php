<?php
session_start();
require_once '../db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$upload_message = '';
$error_message = '';

// Get roll_no
$stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

$target_dir = "uploads/" . $roll_no . "/";
$report_path = $target_dir . "report.pdf";
$project_path = $target_dir . "project.zip";

// Handle delete requests
if (isset($_GET['delete'])) {
    $deleteType = $_GET['delete'];

    if ($deleteType == 'report' && file_exists($report_path)) {
        unlink($report_path);
        $stmt = $conn->prepare("UPDATE projects SET report_path = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        $upload_message = "Report deleted successfully.";
    }

    if ($deleteType == 'project' && file_exists($project_path)) {
        unlink($project_path);
        $stmt = $conn->prepare("UPDATE projects SET project_name = NULL, file_path = NULL, status = 'Pending', submission_date = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        $upload_message = "Project deleted successfully.";
    }
}

// Upload Report
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["report_file"])) {
    $report_ext = strtolower(pathinfo($_FILES["report_file"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["report_file"]["size"] > 30 * 1024 * 1024) {
        $error_message = "Report file too large. Max 30MB.";
    } elseif ($report_ext !== "pdf") {
        $error_message = "Only PDF allowed for report.";
    } elseif (move_uploaded_file($_FILES["report_file"]["tmp_name"], $report_path)) {
        $stmt_check = $conn->prepare("SELECT id FROM projects WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE projects SET report_path = ? WHERE user_id = ?");
            $stmt->bind_param("si", $report_path, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO projects (user_id, report_path) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $report_path);
        }
        $stmt->execute();
        $stmt->close();
        $stmt_check->close();

        $upload_message = "Report uploaded successfully.";
    } else {
        $error_message = "Failed to upload report.";
    }
}

// Upload Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["project_file"])) {
    $project_name = trim($_POST['project_name']);
    $project_ext = strtolower(pathinfo($_FILES["project_file"]["name"], PATHINFO_EXTENSION));

    if (empty($project_name)) {
        $error_message = "Project name is required.";
    } elseif ($_FILES["project_file"]["size"] > 30 * 1024 * 1024) {
        $error_message = "Project file too large. Max 30MB.";
    } elseif ($project_ext !== "zip") {
        $error_message = "Only ZIP allowed for project.";
    } elseif (move_uploaded_file($_FILES["project_file"]["tmp_name"], $project_path)) {
        $stmt_check = $conn->prepare("SELECT id FROM projects WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $stmt_update = $conn->prepare("UPDATE projects SET project_name = ?, file_path = ?, status = 'Completed', submission_date = NOW() WHERE user_id = ?");
            $stmt_update->bind_param("ssi", $project_name, $project_path, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO projects (user_id, project_name, file_path, status) VALUES (?, ?, ?, 'Completed')");
            $stmt_insert->bind_param("iss", $user_id, $project_name, $project_path);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        $stmt_check->close();
        $upload_message = "Project uploaded successfully.";
    } else {
        $error_message = "Failed to upload project.";
    }
}

// Fetch Project Data
$project = null;
$report_uploaded = false;
$project_uploaded = false;
$stmt = $conn->prepare("SELECT project_name, status, submission_date, file_path, report_path FROM projects WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $project = $result->fetch_assoc();
    if (!empty($project['file_path'])) $project_uploaded = true;
    if (!empty($project['report_path'])) $report_uploaded = true;
}
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Projects - IFFCO Portal</title>

  <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script> Back Up--> 
  <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style> body { font-family: 'Inter', sans-serif; } </style>

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
    <a href="profile.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
      <i class="fas fa-user w-6 h-6 mr-3"></i><span>Profile & Details</span>
    </a>
    <a href="projects.php" class="flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
      <i class="fas fa-project-diagram w-6 h-6 mr-3"></i><span>Projects</span>
    </a>
    <a href="certificates.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
      <i class="fas fa-certificate w-6 h-6 mr-3"></i><span>Certificates</span>
    </a>
    <a href="../logout.php" class="flex items-center p-3 text-red-600 hover:bg-red-100 mt-8 rounded-lg">
      <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
    </a>
  </nav>
</aside>

<!-- Main -->
<main class="flex-1 p-10">
  <header class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Project Submission</h1>
    <p class="text-gray-600">Upload your project file and track its status here.</p>
  </header>

  <?php if ($upload_message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6"><?php echo $upload_message; ?></div>
  <?php endif; ?>
  <?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6"><?php echo $error_message; ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Upload -->
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-4 text-gray-800">Upload Project & Report</h2>

      <!-- Project Upload -->
      <form action="projects.php" method="post" enctype="multipart/form-data" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
        <input type="text" name="project_name" required class="mb-3 w-full border px-3 py-2 rounded">

        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Project (.zip, max 30MB)</label>
        <input type="file" name="project_file" accept=".zip" class="mb-3 w-full text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md">Upload Project</button>
      </form>

      <!-- Report Upload -->
      <form action="projects.php" method="post" enctype="multipart/form-data">
        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Report (.pdf, max 30MB)</label>
        <input type="file" name="report_file" accept=".pdf" class="mb-3 w-full text-sm file:bg-green-50 file:text-green-700 hover:file:bg-green-100">

        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md">Upload Report</button>
      </form>
    </div>

    <!-- Status -->
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-4 text-gray-800">Current Status</h2>
      <?php if ($project): ?>
        <div class="space-y-4">
          <div>
            <h3 class="font-medium text-gray-700">Project Name:</h3>
            <p class="text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></p>
          </div>
          <div>
            <h3 class="font-medium text-gray-700">Status:</h3>
            <p>
              <?php echo $project_uploaded ? '✅ <span class="text-green-700 font-semibold">Project: DONE</span>' : '❌ <span class="text-red-600">Project: LEFT</span>'; ?>
            </p>
            <p>
              <?php echo $report_uploaded ? '✅ <span class="text-green-700 font-semibold">Report: DONE</span>' : '❌ <span class="text-red-600">Report: LEFT</span>'; ?>
            </p>
          </div>
          <div>
            <h3 class="font-medium text-gray-700">Submission Date:</h3>
            <p class="text-gray-900"><?php echo isset($project['submission_date']) ? date("F j, Y, g:i a", strtotime($project['submission_date'])) : "—"; ?></p>
          </div>
          <?php if (!empty($project['file_path'])): ?>
            <div>
              <h3 class="font-medium text-gray-700">Project File:</h3>
<a href="<?php echo htmlspecialchars($project['file_path']); ?>" class="text-blue-600 hover:underline" download>Download Project</a>
<a href="projects.php?delete=project" class="ml-4 text-red-600 hover:underline" onclick="return confirm('Delete the project file?')">Delete</a>

            </div>
          <?php endif; ?>
          <?php if (!empty($project['report_path'])): ?>
            <div>
              <h3 class="font-medium text-gray-700">Report File:</h3>
<a href="<?php echo htmlspecialchars($project['report_path']); ?>" class="text-green-600 hover:underline" download>Download Report</a>
<a href="projects.php?delete=report" class="ml-4 text-red-600 hover:underline" onclick="return confirm('Delete the report file?')">Delete</a>

            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-10">
          <i class="fas fa-folder-open fa-3x text-gray-400 mb-4"></i>
          <h3 class="text-lg font-medium text-gray-800">No Project Submitted Yet</h3>
          <p class="text-gray-500">Use the form to upload your first project and report.</p>
          <span class="mt-4 px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Not Started</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
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
