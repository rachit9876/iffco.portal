<!-- certificate.php -->
<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, roll_no, department, batch, college, created_at, duration FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


// Fetch project info
$stmt = $conn->prepare("SELECT project_name, file_path, report_path, submission_date FROM projects WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

$unlocked = !empty($project['file_path']) && !empty($project['report_path']);

if (!$unlocked) {
  ?>
  
  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Locked - IFFCO Portal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb; /* Consistent background with sidebar example */
        }

        /* Custom styles for mobile menu button */
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
            background-color: #36015d; /* Dark purple from example */
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

        /* Responsive sidebar styles */
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
                margin-left: 0 !important; /* Reset margin for mobile */
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
            <!-- Placeholder for IFFCO Logo -->
             <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" class="mx-auto mb-6">
        </div>
        <nav class="px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i><span>Dashboard</span>
            </a>
            <a href="profile.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-user w-6 h-6 mr-3"></i><span>Profile & Details</span>
            </a>
            <a href="projects.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-project-diagram w-6 h-6 mr-3"></i><span>Projects</span>
            </a>
            <!-- Highlight Certificates link as this is the certificates page -->
            <a href="certificates.php" class="flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-certificate w-6 h-6 mr-3"></i><span>Certificates</span>
            </a>
            <a href="../logout.php" class="flex items-center p-3 text-red-600 rounded-lg hover:bg-red-100 mt-8">
                <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-4 md:p-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Certificates</h1>
            <p class="text-gray-600">View and manage your project certificates.</p>
        </header>

        <div class="locked-container max-w-md mx-auto bg-white rounded-lg shadow-md text-center p-8 md:p-10">
            <h2 class="text-red-600 text-2xl font-semibold mb-4">Certificate Locked</h2>
            <p class="text-gray-700 text-lg">Please upload <strong class="font-medium">project.zip</strong> and <strong class="font-medium">report.pdf</strong> to view your certificate.</p>
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


  <?php
  exit;
}

$name = htmlspecialchars($user['name']);
$roll_no = htmlspecialchars($user['roll_no']);
$branch = htmlspecialchars($user['department']);
$college = htmlspecialchars($user['college']);
$batch = htmlspecialchars($user['batch']);
$project_name = htmlspecialchars($project['project_name']);
$issue_date = date("d/m/Y", strtotime($project['submission_date']));
$startDateObj = DateTime::createFromFormat('Y-m-d H:i:s', $user['created_at']);
$months = (int) filter_var($user['duration'], FILTER_SANITIZE_NUMBER_INT);

$endDateObj = clone $startDateObj;
$endDateObj->modify("+$months months");

$start_date = $startDateObj->format('d-m-Y');
$end_date = $endDateObj->format('d-m-Y');

$ref_no = "Voc Trainee / Internee / $batch / $roll_no";

ob_start();
include 'certificate_template.html';
$page = ob_get_clean();

$replace = [
  '{name}' => $name,
  '{roll_no}' => $roll_no,
  '{department}' => $branch,
  '{college}' => $college,
  '{batch}' => $batch,
  '{project_name}' => $project_name,
  '{issue_date}' => $issue_date,
  '{start_date}' => $start_date,
  '{end_date}' => $end_date,
];

echo strtr($page, $replace);
?>
