<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connect.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $program = $_POST['program'];
    $department_full = trim($_POST['department']);
    $contact = trim($_POST['contact']);
    $college = strtoupper(trim($_POST['college']));
    $semester = $_POST['semester'];
    $duration = $_POST['duration'];
    $referral_type = $_POST['referral_type'];

    // Generate roll number and batch
   // Department short-code map
 // full name like Chemical

$dept_shortcodes = [
    'Chemical' => 'CHE',
    'Mechanical' => 'ME',
    'Electrical' => 'EE',
    'Instrumentation' => 'INST',
    'CS' => 'CS',
    'IT' => 'IT',
    'Civil' => 'CE',
    'Finance' => 'FIN',
    'MBA' => 'MBA',
    'HR' => 'HR',
    'Other' => 'OTR'
];

$dept_code = isset($dept_shortcodes[$department_full]) ? $dept_shortcodes[$department_full] : 'UNK';

$year = date('y'); // e.g., 25
$batch = date('Y');

$like_pattern = $year . $dept_code . '%';

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE roll_no LIKE ?");
$stmt->bind_param("s", $like_pattern);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

$roll_no = $year . $dept_code . ($count + 1);




    // File handling
    $noc_file = $_FILES['noc_file'];
    $referral_file = $_FILES['referral_file'];
    $target_dir = "uploads/$roll_no/"; // Updated path to include roll_no

    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $noc_path = $target_dir . "noc.pdf";
$referral_path = $target_dir . "referral.pdf";


    if (!move_uploaded_file($noc_file["tmp_name"], $noc_path) ||
        !move_uploaded_file($referral_file["tmp_name"], $referral_path)) {
        $error_message = "Failed to upload documents.";
    } elseif (empty($name) || empty($email) || empty($password) || empty($department_full) || empty($contact) || empty($college) || empty($semester) || empty($referral_type) || empty($duration))
 {

        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
$status = 'pending';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, roll_no, department, batch, contact_info, role, status, college, program, semester, duration, noc_path, referral_type, referral_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssssssssss", $name, $email, $hashed_password, $roll_no, $department_full, $batch, $contact, $role, $status, $college, $program, $semester, $duration, $noc_path, $referral_type, $referral_path);



            if ($stmt->execute()) {
                $success_message = "✅ Registered! Your roll no is <strong>$roll_no</strong>, batch <strong>$batch</strong>. Awaiting admin approval.";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }

    $conn->close();
}
?>
<!-- HTML Form Part remains unchanged -->

<!-- HTML Form Part -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - IFFCO Vocational Training Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet"> -->
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<!--bg-->
<body style="background: url('https://i.postimg.cc/JnF4pk1Z/bg.png') no-repeat center center fixed; background-size: cover;">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-md p-8 space-y-8">
            <div class="text-center">
                <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" class="mx-auto mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Create Your Account</h2>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input name="name" type="text" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input name="email" type="email" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input name="password" type="password" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
    <label class="block text-sm font-medium text-gray-700">Program</label>
    <select name="program" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
        <option value="">-- Select Program --</option>
        <option>ITI</option>
        <option>Diploma</option>
        <option>B.Sc.</option>
        <option>M.Sc.</option>
        <option>B.Tech.</option>
        <option>M.Tech.</option>
        <option>BBA</option>
        <option>MBA</option>
        <option>BA</option>
        <option>MA</option>
        <option>PhD</option>
        <option>Other</option>
    </select>
</div>
                    <div>
    <label class="block text-sm font-medium text-gray-700">Branch / Department</label>
    <select name="department" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
        <option value="">-- Select --</option>
        <option>Chemical</option>
        <option>Mechanical</option>
        <option>Electrical</option>
        <option>Instrumentation</option>
        <option>CS</option>
        <option>IT</option>
        <option>Civil</option>
        <option>Finance</option>
        <option>MBA</option>
        <option>HR</option>
        <option>Other</option>
    </select>
</div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mobile No.</label>
                        <input name="contact" type="text" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">University / College Name</label>
                        <input name="college" type="text" required style="text-transform: uppercase;" class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Semester</label>
                        <select name="semester" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                            <option value="">-- Select Semester --</option>
                            <option>I</option><option>II</option><option>III</option><option>IV</option>
                            <option>V</option><option>VI</option><option>VII</option><option>VIII</option>
                            <option>IX</option><option>X</option>
                        </select>
                    </div>
                    <div>
    <label class="block text-sm font-medium text-gray-700">Training Duration</label>
    <select name="duration" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
        <option value="">-- Select Duration --</option>
        <option>1 Month</option>
        <option>2 Month</option>
    </select>
</div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Upload NOC PDF
                            <span class="relative group cursor-pointer text-blue-600 font-bold">
    ?
    <span class="absolute bottom-full mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded px-2 py-1 z-10 whitespace-nowrap">
        Upload your NOC which you got from college
    </span>
</span>

                        </label>
                        <input type="file" name="noc_file" accept=".pdf" required class="w-full mt-1 border rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Signed Referral Type</label>
                        <select name="referral_type" required class="w-full mt-1 px-3 py-2 border rounded-md shadow-sm">
                            <option value="">-- Select --</option>
                            <option>Employee</option>
                            <option>Organization</option>
                            <option>University</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Upload Referral File PDF
                            <span class="relative group cursor-pointer text-blue-600 font-bold">
    ?
    <span class="absolute bottom-full mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded px-2 py-1 z-10 whitespace-nowrap">
        Upload a signed application <br> referring to the Head of training department <br>
        that you are interested in the training <br> and being referred by above and their sign
    </span>
</span>

                        </label>
                        <input type="file" name="referral_file" accept=".pdf" required class="w-full mt-1 border rounded-md shadow-sm text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                    Register
                </button>
            </form>
        </div>
    </div>
</body>
</html>
