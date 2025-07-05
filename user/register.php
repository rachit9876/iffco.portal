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
                // Check if auto-approval is enabled by any admin
                $autoApproveQuery = $conn->query("SELECT id FROM users WHERE role = 'admin' AND toggle_status = 'ON' LIMIT 1");

                if ($autoApproveQuery && $autoApproveQuery->num_rows > 0) {
                    // Auto-approve and log in the user immediately
                    $updateStmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE email = ?");
                    $updateStmt->bind_param("s", $email);
                    $updateStmt->execute();
                    $updateStmt->close();

                    // Get user id after insertion
                    $getUser = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
                    $getUser->bind_param("s", $email);
                    $getUser->execute();
                    $getUser->bind_result($userId, $userName);
                    $getUser->fetch();
                    $getUser->close();

                    // Set session and redirect
                    session_start();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $userId;
                    $_SESSION['name'] = $userName;
                    $_SESSION['role'] = 'user';

                    header("Location: ../user/dashboard.php");
                    exit;
                } else {
                    $success_message = "✅ Registered! Your roll no is <strong>$roll_no</strong>, batch <strong>$batch</strong>. Awaiting admin approval.";
                }
            } else {
                $error_message = "Error: " . $stmt->error;
            }

        }
        $stmt->close();
    }

    $conn->close();
}
?>
<!-- HTML Form Part -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - IFFCO Vocational Training Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            /* Removed overflow: hidden; to allow scrolling */
        }
        /* Full-screen background container */
        .background-container {
            position: fixed; /* Keep background fixed */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('/uploads/bg.png') no-repeat center center;
            background-size: cover;
            z-index: -1; /* Place it behind other content */
        }

        /* Initial state for the animation for all elements */
        .card-element-hidden {
            opacity: 0;
            transform: translateY(20px); /* Start slightly below its final position */
        }
        /* Final state and transition for the animation for all elements */
        .card-element-reveal {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out; /* Smooth transition */
        }
    </style>
</head>
<body>
    <!-- Dedicated background container -->
    <div class="background-container"></div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 relative z-0">
        <div id="register-card" class="max-w-2xl w-full bg-white bg-opacity-30 rounded-lg shadow-md p-8 space-y-8">
            <div class="text-center card-element-hidden">
                <img src="/uploads/IFFCO.jpg" alt="IFFCO Logo" class="mx-auto mb-4" style="max-width: 120px; height: auto;">
                <h2 class="text-2xl font-bold text-gray-800">Create Your Account</h2>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded card-element-hidden"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded card-element-hidden"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input name="name" type="text" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black  border rounded-md shadow-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input name="email" type="email" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input name="password" type="password" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <select name="program" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
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
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Branch / Department</label>
                        <select name="department" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
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

                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Mobile No.</label>
                        <input name="contact" type="text" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">University / College Name</label>
                        <input name="college" type="text" required style="text-transform: uppercase;" class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Semester</label>
                        <select name="semester" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                            <option value="">-- Select Semester --</option>
                            <option>I</option><option>II</option><option>III</option><option>IV</option>
                            <option>V</option><option>VI</option><option>VII</option><option>VIII</option>
                            <option>IX</option><option>X</option>
                        </select>
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Training Duration</label>
                        <select name="duration" required class="w-full mt-1 px-3 py-2 bg-white bg-opacity-20 text-black border rounded-md shadow-sm">
                            <option value="">-- Select Duration --</option>
                            <option>1 Month</option>
                            <option>2 Month</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">Upload NOC PDF
                            <span class="relative ml-1">
                                <button type="button" tabindex="-1" id="nocHelpBtn" class="text-blue-600 font-bold w-5 h-5 flex items-center justify-center rounded-full border border-blue-300 bg-blue-50 text-xs focus:outline-none" style="line-height:1; padding:0; min-width:1rem; min-height:1rem;" onclick="document.getElementById('nocTooltip').classList.toggle('hidden')">?</button>
                                <span id="nocTooltip" class="hidden absolute left-1/2 transform -translate-x-1/2 bottom-full mb-1 bg-gray-800 text-white text-xs rounded px-2 py-1 z-10" style="min-width:200px; text-align:left;">
                                    Upload your NOC which you got from college<span class="text-blue-200"></span>
                                </span>
                            </span>
                        </label>
                        <input type="file" name="noc_file" accept=".pdf" required class="w-full mt-1 border rounded-md shadow-sm text-sm">
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700">Signed Referral Type</label>
                        <select name="referral_type" required class="w-full mt-1 px-3 py-2 border bg-white bg-opacity-20 text-black rounded-md shadow-sm">
                            <option value="">-- Select --</option>
                            <option>Employee</option>
                            <option>Organization</option>
                            <option>University</option>
                        </select>
                    </div>
                    <div class="card-element-hidden">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">Upload Referral File PDF
                            <span class="relative ml-1">
                                <button type="button" tabindex="-1" id="referralHelpBtn" class="text-blue-600 font-bold w-5 h-5 flex items-center justify-center rounded-full border border-blue-300 bg-blue-50 text-xs focus:outline-none" style="line-height:1; padding:0; min-width:1rem; min-height:1rem;" onclick="document.getElementById('referralTooltip').classList.toggle('hidden')">?</button>
                                <span id="referralTooltip" class="hidden absolute left-1/2 transform -translate-x-1/2 bottom-full mb-1 bg-gray-800 text-white text-xs rounded px-2 py-1 z-10" style="min-width:240px; text-align:left;">
                                    Upload a signed application referring to the Head of training department that you are interested in the training and being referred by above and their sign <span class="text-blue-200"></span>
                                </span>
                            </span>
                        </label>
                        <input type="file" name="referral_file" accept=".pdf" required class="w-full mt-1 border rounded-md shadow-sm text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 card-element-hidden">
                    Register
                </button>

                <a href="../index.php" class="w-full block text-center py-2 px-4 bg-black text-white font-medium rounded-md hover:bg-gray-700 card-element-hidden">
                    Back to Home
                </a>
            
            
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerCard = document.getElementById('register-card');
            // Select ALL elements within the register card that have the 'card-element-hidden' class
            const animatableElements = registerCard.querySelectorAll('.card-element-hidden');

            // Apply the reveal effect to each element with a staggered delay
            Array.from(animatableElements).forEach((element, index) => {
                setTimeout(() => {
                    element.classList.remove('card-element-hidden');
                    element.classList.add('card-element-reveal');
                }, 100 + (index * 50)); // Stagger the animation by 50ms per element for a faster reveal
            });

            // Tooltip close on outside click for referral and NOC file
            document.addEventListener('click', function(event) {
                const referralTooltip = document.getElementById('referralTooltip');
                const referralBtn = document.getElementById('referralHelpBtn');
                if (referralTooltip && referralBtn && !referralTooltip.classList.contains('hidden')) {
                    if (!referralTooltip.contains(event.target) && !referralBtn.contains(event.target)) {
                        referralTooltip.classList.add('hidden');
                    }
                }
                const nocTooltip = document.getElementById('nocTooltip');
                const nocBtn = document.getElementById('nocHelpBtn');
                if (nocTooltip && nocBtn && !nocTooltip.classList.contains('hidden')) {
                    if (!nocTooltip.contains(event.target) && !nocBtn.contains(event.target)) {
                        nocTooltip.classList.add('hidden');
                    }
                }
            });

            // Tooltip show message on hover for both tooltips
            const nocBtn = document.getElementById('nocHelpBtn');
            const nocTooltip = document.getElementById('nocTooltip');
            if (nocBtn && nocTooltip) {
                nocBtn.addEventListener('mouseenter', function() {
                    nocTooltip.classList.remove('hidden');
                });
                nocBtn.addEventListener('mouseleave', function() {
                    nocTooltip.classList.add('hidden');
                });
            }
            const referralBtn = document.getElementById('referralHelpBtn');
            const referralTooltip = document.getElementById('referralTooltip');
            if (referralBtn && referralTooltip) {
                referralBtn.addEventListener('mouseenter', function() {
                    referralTooltip.classList.remove('hidden');
                });
                referralBtn.addEventListener('mouseleave', function() {
                    referralTooltip.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
