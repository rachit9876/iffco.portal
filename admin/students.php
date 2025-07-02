<?php
// --- Admin: Student Management ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../db_connect.php';
require_once 'qrcode/qrlib.php';  // Ensure this file exists!

// Redirect if not admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$message = '';
$error = '';

// Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];

// Step 1: Get the roll_no first
$stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

// Step 2: Delete the folder (if it exists)
$upload_dir = "../user/uploads/$roll_no";
function deleteFolder($folder) {
    if (!is_dir($folder)) return;
    foreach (scandir($folder) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = "$folder/$file";
        is_dir($path) ? deleteFolder($path) : unlink($path);
    }
    rmdir($folder);
}
deleteFolder($upload_dir);

// Step 3: Delete the student from database
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $student_id);
if ($stmt->execute()) {
    $message = "Student and their folder deleted successfully.";
} else {
    $error = "Error deleting student.";
}
$stmt->close();

}

// Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $department = trim($_POST['department']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
 

    // Auto-generate roll no
    $year = date('y');
    $full_year = date('Y');
    $like_pattern = $year . $department . '%';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE roll_no LIKE ?");
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $next_number = $count + 1;
    $roll_no = $year . $department . $next_number;

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, roll_no, department, role, status, batch, contact_info) VALUES (?, ?, ?, ?, ?, 'user', 'approved', ?, 'N/A')");
    $stmt->bind_param("ssssss", $name, $email, $hashed_password, $roll_no, $department, $full_year);
    
    if ($stmt->execute()) {
    // Create uploads folder only after student successfully added
    $upload_path = "../user/uploads/$roll_no";
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }

    $message = "Student added successfully. Roll No: <strong>$roll_no</strong>";
} else {
    $error = "Error: " . $stmt->error;
}

    $stmt->close();
}


// Issue Certificate
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['issue_certificate'])) {
    $student_id = $_POST['student_id'];
    $file = $_FILES['certificate_pdf'];

    $target_dir = "../uploads/certificates/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = "cert-" . $student_id . "-" . basename($file["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // QR code
        $qr_dir = "../uploads/qr_codes/";
        if (!file_exists($qr_dir)) mkdir($qr_dir, 0777, true);
        $qr_file_path = $qr_dir . "qr-" . $student_id . ".png";
        $certificate_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . "/verify.php?id=" . $student_id;
        QRcode::png($certificate_url, $qr_file_path);

        // Insert/Update certificate
        $stmt = $conn->prepare("INSERT INTO certificates (user_id, certificate_path, qr_code_path, issue_date) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE certificate_path = ?, qr_code_path = ?, issue_date = NOW()");
        $stmt->bind_param("issss", $student_id, $target_file, $qr_file_path, $target_file, $qr_file_path);
        if ($stmt->execute()) {
            $message = "Certificate issued successfully.";
        } else {
            $error = "Database error on issuing certificate.";
        }
        $stmt->close();
    } else {
        $error = "Failed to upload certificate PDF.";
    }
}

// Fetch all students
$students = [];
$sql = "SELECT u.id, u.name, u.email, u.roll_no, u.department, c.id as certificate_id 
        FROM users u 
        LEFT JOIN certificates c ON u.id = c.user_id 
        WHERE u.role = 'user' AND u.status = 'approved' 
        ORDER BY u.name";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$conn->close();

$page_title = "Trainee Management";
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
        
        #addStudentModal, #issueCertModal { display: none; }
    </style>
</head>
<body class="bg-gray-200 flex">

    <!-- Sidebar -->
    <!-- Sidebar -->
<aside class="w-64 min-h-screen bg-gray-900 text-white shadow-md">
    <div class="p-6 bg-gray-900"><h2 class="text-xl font-bold">IFFCO Admin</h2></div>
    <nav class="px-4 space-y-2">
        <a href="dashboard.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
            <i class="fas fa-tachometer-alt w-6 h-6 mr-3"></i><span>Dashboard</span>
        </a>
        <a href="students.php" class="flex items-center p-3 bg-blue-800 text-white rounded-lg hover:bg-blue-900">
            <i class="fas fa-users w-6 h-6 mr-3"></i><span>Student Management</span>
        </a>
        <a href="applications.php" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-gray-700">
            <i class="fas fa-inbox w-6 h-6 mr-3"></i><span>Applications</span>
        </a>
        <a href="../logout.php" class="flex items-center p-3 text-red-300 rounded-lg hover:bg-red-600 mt-8">
            <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i><span>Logout</span>
        </a>
    </nav>
</aside>


    <!-- Main Content -->
    <main class="flex-1 p-10">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
                <p class="text-gray-600">Manage all approved student accounts.</p>
            </div>
            <button onclick="openModal('addStudentModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center"><i class="fas fa-user-plus mr-2"></i>Add Student</button>
        </header>
        
        <?php if ($message): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?php echo $message; ?></p></div><?php endif; ?>
        <?php if ($error): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?php echo $error; ?></p></div><?php endif; ?>

        <!-- Student Table -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Name</th>
                            <th scope="col" class="px-6 py-3">Roll No.</th>
                            <th scope="col" class="px-6 py-3">Department</th>
                            <th scope="col" class="px-6 py-3">Certificate</th>
                            <th scope="col" class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?><br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($student['email']); ?></span></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($student['department']); ?></td>
                            <td class="px-6 py-4">
                                <?php if ($student['certificate_id']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Issued</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Not Issued</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 flex items-center space-x-2">
    <!-- View profile -->
    <!-- View Docs -->
<a href="view_docs.php?roll=<?php echo urlencode($student['roll_no']); ?>" target="_blank" class="text-gray-700 hover:text-blue-700" title="View Uploaded Documents">
    <i class="fas fa-eye text-xl"></i>
</a>


    <!-- Issue Certificate -->
    <button onclick="openCertModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($student['name'])); ?>')" class="text-blue-600 hover:text-blue-900" title="Issue Certificate">
        <i class="fas fa-award text-xl"></i>
    </button>

    <!-- Delete -->
    <form action="students.php" method="post" onsubmit="return confirm('Are you sure you want to delete this student?');" class="inline">
        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
        <button type="submit" name="delete_student" class="text-red-600 hover:text-red-900" title="Delete Student">
            <i class="fas fa-trash-alt text-xl"></i>
        </button>
    </form>
</td>

                            
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="text-center py-4">No approved students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                 <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Student</h3>
                <div class="mt-2 px-7 py-3">
                    <form action="students.php" method="POST">
                         <input class="mt-2 text-sm w-full px-2 py-2 border border-gray-300 rounded-md" type="text" name="name" placeholder="Full Name" required>
                         <input class="mt-2 text-sm w-full px-2 py-2 border border-gray-300 rounded-md" type="email" name="email" placeholder="Email" required>
                         <input class="mt-2 text-sm w-full px-2 py-2 border border-gray-300 rounded-md" type="password" name="password" placeholder="Password" required>
                        
                         <select name="department" class="mt-2 text-sm w-full px-2 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Department</option>
                            <option value="CS">CS</option>
                            <option value="HR">HR</option>
                            <option value="MBA">MBA</option>
                         </select>
                         <div class="items-center px-4 py-3">
                            <button type="submit" name="add_student" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">Add Student</button>
                        </div>
                    </form>
                </div>
                <button onclick="closeModal('addStudentModal')" class="absolute top-0 right-0 mt-4 mr-4 text-gray-500 hover:text-gray-800">&times;</button>
            </div>
        </div>
    </div>
    
    <!-- Issue Certificate Modal -->
    <div id="issueCertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">Issue Certificate</h3>
                <p id="issueCertStudentName" class="text-center text-sm text-gray-500 mb-4"></p>
                <form action="students.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="certStudentId" name="student_id">
                    <div class="mt-2 px-7 py-3">
                         <label for="certificate_pdf" class="block text-sm font-medium text-gray-700">Upload Certificate (PDF)</label>
                         <input type="file" name="certificate_pdf" id="certificate_pdf" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div class="items-center px-4 py-3">
                        <button type="submit" name="issue_certificate" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">Issue Certificate</button>
                    </div>
                </form>
                <button onclick="closeModal('issueCertModal')" class="absolute top-0 right-0 mt-4 mr-4 text-gray-500 hover:text-gray-800">&times;</button>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        function openCertModal(studentId, studentName) {
            document.getElementById('certStudentId').value = studentId;
            document.getElementById('issueCertStudentName').innerText = 'For: ' + studentName;
            openModal('issueCertModal');
        }
    </script>
</body>
</html>