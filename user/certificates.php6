<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$certificate = null;

$stmt = $conn->prepare("SELECT certificate_path, issue_date, qr_code_path FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $certificate = $result->fetch_assoc();
}
$stmt->close();
$conn->close();

$page_title = "Certificates";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $page_title; ?> - IFFCO Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<aside class="w-64 min-h-screen bg-white shadow-md">
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
    <a href="projects.php" class="flex items-center p-3 text-gray-700 rounded-lg hover:bg-gray-200">
      <i class="fas fa-project-diagram w-6 h-6 mr-3"></i><span>Projects</span>
    </a>
    <a href="certificates.php" class="flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
      <i class="fas fa-certificate w-6 h-6 mr-3"></i><span>Certificates</span>
    </a>
    <a href="../logout.php" class="flex items-center p-3 text-red-600 hover:bg-red-100 mt-8 rounded-lg">
      <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
    </a>
  </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-10">
  <header class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Your Certificate</h1>
    <p class="text-gray-600">Download your vocational training certificate once it has been issued.</p>
  </header>

  <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
    <?php if ($certificate && !empty($certificate['certificate_path'])): ?>
      <div class="text-center">
        <i class="fas fa-check-circle fa-4x text-green-500 mb-4"></i>
        <h2 class="text-2xl font-semibold text-gray-800">Certificate Issued!</h2>
        <p class="text-gray-600 mt-2 mb-6">
          Congratulations! Your certificate has been issued on <?php echo date("F j, Y", strtotime($certificate['issue_date'])); ?>.
        </p>
        <div class="flex flex-col md:flex-row items-center justify-center gap-6">
          <a href="<?php echo htmlspecialchars($certificate['certificate_path']); ?>" download
            class="w-full md:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-download mr-2"></i>
            Download Certificate (PDF)
          </a>
          <?php if (!empty($certificate['qr_code_path'])): ?>
            <div class="text-center">
              <img src="<?php echo htmlspecialchars($certificate['qr_code_path']); ?>" alt="Certificate QR Code"
                class="w-32 h-32 mx-auto border p-1 rounded-md">
              <p class="text-xs text-gray-500 mt-2">Scan to verify</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100">
          <i class="fas fa-lock fa-2x text-yellow-600"></i>
        </div>
        <h2 class="text-2xl font-semibold text-gray-800 mt-4">Certificate Locked</h2>
        <p class="text-gray-600 mt-2">Your certificate is pending issue from the administration.</p>
        <p class="text-gray-500 mt-1">Please check back later.</p>
      </div>
    <?php endif; ?>
  </div>
</main>

</body>
</html>
