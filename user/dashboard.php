<?php
// --- User Dashboard ---
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$page_title = "Dashboard";
$username = isset($_SESSION["name"]) ? htmlspecialchars($_SESSION["name"]) : "User";
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
<!--<body style="background: url('https://i.postimg.cc/cCkcjBDx/bg.png') no-repeat center center fixed; background-size: cover;">-->

<!-- Mobile Menu Button -->
<button class="mobile-menu-button" aria-label="Menu">
  <span class="menu-icon">
    <span class="line line-1"></span>
    <span class="line line-2"></span>
    <span class="line line-3"></span>
  </span>
</button>


    <!-- Sidebar Navigation -->
<aside class="sidebar w-64 min-h-screen bg-white shadow-md">

        <div class="p-6">
            <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" class="mx-auto mb-6">
        </div>
        <nav class="px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-user w-6 h-6 mr-3"></i>
                <span>Profile & Details</span>
            </a>
            <a href="projects.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-project-diagram w-6 h-6 mr-3"></i>
                <span>Projects</span>
            </a>
            <a href="certificates.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-certificate w-6 h-6 mr-3"></i>
                <span>Certificates</span>
            </a>
            <a href="../logout.php" class="flex items-center p-3 text-red-600 rounded-lg hover:bg-red-100 mt-8">
                <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo $username; ?>!</h1>
            <p class="text-gray-600">This is your central hub for managing your vocational training progress.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Profile Card -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Your Profile</h3>
                        <p class="text-gray-500">View and update your personal details.</p>
                        <a href="profile.php" class="text-blue-600 font-semibold mt-2 inline-block">Go to Profile &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Projects Card -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-project-diagram fa-2x"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Your Projects</h3>
                        <p class="text-gray-500">Submit and track your project status.</p>
                        <a href="projects.php" class="text-green-600 font-semibold mt-2 inline-block">Manage Projects &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Certificates Card -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-certificate fa-2x"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Your Certificates</h3>
                        <p class="text-gray-500">Download your certificate once issued.</p>
                        <a href="certificates.php" class="text-yellow-600 font-semibold mt-2 inline-block">View Certificates &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="mt-10 bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Announcements</h2>
            <p class="text-gray-600">No new announcements at this time. Please check back later for updates on your training schedule, new projects, or events.</p>
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