<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Fetch statistics
$total_students_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE role = 'user' AND status = 'approved'");
$total_students = $total_students_result->fetch_assoc()['total'];

$pending_apps_result = $conn->query("SELECT COUNT(id) AS total FROM users WHERE status = 'pending'");
$pending_apps = $pending_apps_result->fetch_assoc()['total'];

$dept_stats = [];
$depts = ['CS', 'HR', 'MBA'];
foreach ($depts as $dept) {
    $stmt = $conn->prepare("SELECT COUNT(id) AS total FROM users WHERE role = 'user' AND status = 'approved' AND department = ?");
    $stmt->bind_param("s", $dept);
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
    <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script> Back Up--> 
  <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-200 flex">

    <!-- Sidebar -->
    <aside class="w-64 min-h-screen bg-gray-900 text-white shadow-md">
        <div class="p-6 bg-gray-900">
            <h2 class="text-xl font-bold">IFFCO Admin</h2>
        </div>
        <nav class="px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 bg-blue-800 text-white rounded-lg hover:bg-blue-900">
                <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="students.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
                <i class="fas fa-users w-6 h-6 mr-3"></i>
                <span>Student Management</span>
            </a>
            <a href="applications.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
                <i class="fas fa-inbox w-6 h-6 mr-3"></i>
                <span>Applications</span>
            </a>
            <a href="../logout.php" class="flex items-center p-3 text-red-300 rounded-lg hover:bg-red-600 mt-8">
                <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-600">Overview of the Vocational Training Portal.</p>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium uppercase">Total Students</p>
                        <p class="text-3xl font-bold"><?php echo $total_students; ?></p>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex justify-between items-center relative">
                    <div>
                        <p class="text-sm font-medium uppercase">Pending Applications</p>
                        <p class="text-3xl font-bold"><?php echo $pending_apps; ?></p>
                    </div>
                    <a href="applications.php" class="absolute bottom-4 right-4 text-white"><i class="fas fa-arrow-circle-right"></i></a>
                    <i class="fas fa-inbox fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium uppercase">CS Department</p>
                        <p class="text-3xl font-bold"><?php echo $dept_stats['CS']; ?></p>
                    </div>
                    <i class="fas fa-laptop-code fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="bg-indigo-500 text-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium uppercase">HR & MBA Depts</p>
                        <p class="text-3xl font-bold"><?php echo $dept_stats['HR'] + $dept_stats['MBA']; ?></p>
                    </div>
                    <i class="fas fa-briefcase fa-3x opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-10 bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Quick Actions</h2>
            <div class="flex space-x-4">
                <a href="students.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Add New Student
                </a>
                <a href="students.php?action=import" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center">
                    <i class="fas fa-file-csv mr-2"></i>
                    Bulk Import Students
                </a>
            </div>
        </div>
    </main>

</body>
</html>
