<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $message = $stmt->execute() ? "Application approved." : "Error approving application.";
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $message = $stmt->execute() ? "Application rejected and removed." : "Error rejecting application.";
    }
    $stmt->close();
}

$applications = [];
$sql = "SELECT id, name, email, roll_no, department, batch, contact_info, created_at, noc_path, referral_path 
        FROM users 
        WHERE status = 'pending' 
        ORDER BY created_at DESC";
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
    <title><?php echo $page_title; ?> - IFFCO Portal</title>
    <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script> Back Up--> 
  <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-200 flex">

<!-- Sidebar -->
<aside class="w-64 min-h-screen bg-gray-900 text-white shadow-md">
    <div class="p-6 bg-gray-900"><h2 class="text-xl font-bold">IFFCO Admin</h2></div>
    <nav class="px-4 space-y-2">
        <a href="dashboard.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
            <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i><span>Dashboard</span>
        </a>
        <a href="students.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
            <i class="fas fa-users w-6 h-6 mr-3"></i><span>Student Management</span>
        </a>
        <a href="applications.php" class="flex items-center p-3 bg-blue-800 text-white rounded-lg hover:bg-blue-900">
            <i class="fas fa-inbox w-6 h-6 mr-3"></i><span>Applications</span>
        </a>
        <a href="../logout.php" class="flex items-center p-3 text-red-300 rounded-lg hover:bg-red-600 mt-8">
            <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
        </a>
    </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-10">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
        <p class="text-gray-600">Review and process new trainee sign-up requests.</p>
    </header>

    <?php if ($message): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6"><?php echo $error; ?></div><?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="space-y-6">
            <?php if (empty($applications)): ?>
                <div class="text-center py-10">
                    <i class="fas fa-check-circle fa-3x text-gray-400 mb-4"></i>
                    <p class="text-gray-500">No pending applications.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($applications as $app): ?>
                <div class="p-4 border rounded-lg flex flex-wrap items-center justify-between">
                    <div class="flex-grow">
                        <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($app['name']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($app['email']); ?> | Roll: <?php echo htmlspecialchars($app['roll_no']); ?></p>
                        <p class="text-sm text-gray-500">Dept: <?php echo htmlspecialchars($app['department']); ?> | Batch: <?php echo htmlspecialchars($app['batch']); ?></p>
                    </div>
                    <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                        <a href="view_docs.php?roll=<?php echo urlencode($app['roll_no']); ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
                            See Docs
                        </a>
                        <form action="applications.php" method="POST" class="inline">
                            <input type="hidden" name="user_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Approve</button>
                        </form>
                        <form action="applications.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reject this application?');">
                            <input type="hidden" name="user_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

</body>
</html>
