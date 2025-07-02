<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

require_once '../db_connect.php'; // <-- required for DB fetch

$roll_no = isset($_GET['roll']) ? basename($_GET['roll']) : '';
$upload_dir = "../user/uploads/$roll_no";

// --- Fetch student data ---
$student = null;
if ($roll_no) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE roll_no = ? AND role = 'user'");
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

// --- Scan uploaded files ---
$files = [];
if ($roll_no && is_dir($upload_dir)) {
    foreach (scandir($upload_dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $full_path = "$upload_dir/$file";

        if (is_file($full_path)) {
            $label = '';
            if (str_starts_with($file, 'ref_')) $label = 'Referral Document';
            elseif (str_starts_with($file, 'noc_')) $label = 'NOC Document';
            else $label = 'Document';

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $icon = match ($ext) {
                'pdf' => 'fa-file-pdf text-red-500',
                'jpg', 'jpeg', 'png' => 'fa-file-image text-blue-500',
                'doc', 'docx' => 'fa-file-word text-indigo-500',
                default => 'fa-file text-gray-500'
            };

            $files[] = [
                'name' => $file,
                'label' => $label,
                'icon' => $icon,
                'url' => "$full_path"
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Docs - <?php echo htmlspecialchars($roll_no); ?></title>
    <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script> Back Up--> 
  <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Student Info Card -->
        <?php if ($student): ?>
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Student Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
                <div><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_no']); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($student['department']); ?></div>
                <div><strong>Batch:</strong> <?php echo htmlspecialchars($student['batch']); ?></div>
                <div><strong>Semester:</strong> <?php echo htmlspecialchars($student['semester']); ?></div>
                <div><strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?></div>
                <div><strong>College:</strong> <?php echo htmlspecialchars($student['college']); ?></div>
                <div><strong>Duration:</strong> <?php echo htmlspecialchars($student['duration']); ?></div>
                <div><strong>Referral Type:</strong> <?php echo htmlspecialchars($student['referral_type']); ?></div>
                <div><strong>Contact Info:</strong> <?php echo htmlspecialchars($student['contact_info']); ?></div>
                <div><strong>Status:</strong> <?php echo htmlspecialchars($student['status']); ?></div>
                <div><strong>Registered At:</strong> <?php echo htmlspecialchars($student['created_at']); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documents Card -->
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-bold mb-6">Documents for Roll No: <?php echo htmlspecialchars($roll_no); ?></h2>

            <?php if (empty($files)): ?>
                <p class="text-gray-500">No uploaded documents found.</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($files as $f): ?>
                        <li class="flex items-center justify-between bg-gray-50 p-3 rounded hover:bg-gray-100">
                            <div class="flex items-center space-x-3">
                                <i class="fas <?php echo $f['icon']; ?> fa-lg"></i>
                                <div>
                                    <div class="font-medium"><?php echo $f['label']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($f['name']); ?></div>
                                </div>
                            </div>
                            <a href="<?php echo $f['url']; ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="mt-6">
                <a href="students.php" class="text-blue-500 hover:underline">&larr; Back to Students</a>
            </div>
        </div>
    </div>

</body>
</html>
