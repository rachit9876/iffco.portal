<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connect.php';



// Fetch departments and programs
$departments = $conn->query("SELECT id, name, code FROM departments WHERE is_active = 1 ORDER BY name");
$programs = $conn->query("SELECT id, name FROM programs WHERE is_active = 1 ORDER BY name");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $program_id = intval($_POST['program']);
    $department_id = intval($_POST['department']);
    $contact = trim($_POST['contact']);
    $college = strtoupper(trim($_POST['college']));
    $semester = $_POST['semester'];
    $duration = $_POST['duration'];
    $referral_type = $_POST['referral_type'];

    // Validate required fields FIRST (before any file operations)
    if (empty($name) || empty($email) || empty($password) || empty($department_id) || empty($program_id) || empty($contact) || $college === '' || empty($semester) || empty($referral_type) || empty($duration)) {
        header("Location: register.php?error=Please fill in all required fields");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=Invalid email format");
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: register.php?error=An account with this email already exists");
        exit;
    }
    $stmt->close();

    // Get department code
    $stmt = $conn->prepare("SELECT code FROM departments WHERE id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $stmt->bind_result($dept_code);
    $stmt->fetch();
    $stmt->close();

    // Generate roll number
    $year = date('y');
    $batch = date('Y');
    $like_pattern = $year . $dept_code . '%';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE roll_no LIKE ?");
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $roll_no = $year . $dept_code . ($count + 1);

    // File handling - AFTER validation passes
    $noc_file = $_FILES['noc_file'];
    $referral_file = $_FILES['referral_file'];
    $target_dir = "uploads/$roll_no/";

    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $noc_path = $target_dir . "noc.pdf";
    $referral_path = $target_dir . "referral.pdf";

    if (!move_uploaded_file($noc_file["tmp_name"], $noc_path) ||
        !move_uploaded_file($referral_file["tmp_name"], $referral_path)) {
        // Clean up directory if upload fails
        @rmdir($target_dir);
        header("Location: register.php?error=Failed to upload documents");
        exit;
    } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $status = 'pending';


            $stmt = $conn->prepare("INSERT INTO users (name, email, password, roll_no, department_id, batch, contact_info, role, status, college, program_id, semester, duration, noc_path, referral_type, referral_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("ssssssssssssssss", $name, $email, $hashed_password, $roll_no, $department_id, $batch, $contact, $role, $status, $college, $program_id, $semester, $duration, $noc_path, $referral_type, $referral_path);



            if ($stmt->execute()) {
                header("Location: register.php?msg=✅ Registered! Your roll no is $roll_no, batch $batch. Awaiting admin approval");
                exit;
            } else {
                header("Location: register.php?error=Error: " . urlencode($stmt->error));
                exit;
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
    <title>Register - IFFCO Vocational Training Portal (DEMO)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../neobrutalist.css">
</head>
<body style="margin: 0; overflow-y: auto; min-height: 100vh;">
    <div class="unver-demo-banner">
        ⚠️ THIS IS A DEMO/PROJECT WEBSITE - NOT A REAL IFFCO PORTAL ⚠️
    </div>
    
    <div class="unver-bg-fixed unver-bg-cover" style="background-image: url('https://cdn-fast.pages.dev/iffco-portal/bg.webp');"></div>
    
    <div style="position: relative; z-index: 10; min-height: 100vh; padding-top: 64px;">
        <div style="display: flex; align-items: center; justify-content: center; padding: 48px 16px;">
            <div class="unver-card" style="max-width: 800px; width: 100%; background: rgba(255,255,255,0.95); position: relative;">
                <div class="unver-card-body">
                    <div style="text-align: center;" class="unver-mb-lg">
                        <a href="../index.php" class="unver-btn unver-btn-sm" style="position: absolute; top: 20px; left: 20px; text-decoration: none;">← Back to Login</a>
                        <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" style="max-width: 120px; margin: 0 auto 16px; display: block;">
                        <h2 class="unver-h2">Create Your Account</h2>
                        <p style="color: var(--unver-danger); font-weight: bold;">DEMO SITE - NOT REAL</p>
                    </div>



                    <form action="register.php" method="POST" enctype="multipart/form-data">
                        <div class="unver-grid unver-grid-2 unver-mb-lg">
                

                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Full Name</label>
                                <input name="name" type="text" required class="unver-input">
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Email</label>
                                <input name="email" type="email" required class="unver-input">
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Password</label>
                                <input name="password" type="password" required class="unver-input">
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Program</label>
                                <select name="program" required class="unver-input unver-select">
                                    <option value="">-- Select Program --</option>
                                    <?php while($prog = $programs->fetch_assoc()): ?>
                                        <option value="<?php echo $prog['id']; ?>"><?php echo htmlspecialchars($prog['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Branch / Department</label>
                                <select name="department" required class="unver-input unver-select">
                                    <option value="">-- Select --</option>
                                    <?php while($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Mobile No.</label>
                                <input name="contact" type="text" required class="unver-input">
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">University / College Name</label>
                                <input name="college" type="text" required style="text-transform: uppercase;" class="unver-input">
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Semester</label>
                                <select name="semester" required class="unver-input unver-select">
                                    <option value="">-- Select Semester --</option>
                                    <option>I</option><option>II</option><option>III</option><option>IV</option>
                                    <option>V</option><option>VI</option><option>VII</option><option>VIII</option>
                                    <option>IX</option><option>X</option>
                                </select>
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Training Duration</label>
                                <select name="duration" required class="unver-input unver-select">
                                    <option value="">-- Select Duration --</option>
                                    <option>1 Month</option>
                                    <option>2 Month</option>
                                </select>
                            </div>
                        </div>

                        <div class="unver-form-group">
                            <label class="unver-label unver-text-sm">Upload NOC PDF
                                <span class="unver-tooltip-trigger unver-text-primary unver-font-bold">
                                    ?
                                    <span class="unver-tooltip-text">Upload your NOC which you got from college</span>
                                </span>
                            </label>
                            <input type="file" name="noc_file" accept=".pdf" required class="unver-input">
                        </div>

                        <div class="unver-grid unver-grid-2 unver-mb-lg">
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Signed Referral Type</label>
                                <select name="referral_type" required class="unver-input unver-select">
                                    <option value="">-- Select --</option>
                                    <option>Employee</option>
                                    <option>Organization</option>
                                    <option>University</option>
                                </select>
                            </div>
                            <div class="unver-form-group">
                                <label class="unver-label unver-text-sm">Upload Referral File PDF
                                    <span class="unver-tooltip-trigger unver-text-primary unver-font-bold">
                                        ?
                                        <span class="unver-tooltip-text">Upload a signed application referring to the Head of training department that you are interested in the training and being referred by above and their sign</span>
                                    </span>
                                </label>
                                <input type="file" name="referral_file" accept=".pdf" required class="unver-input">
                            </div>
                        </div>

                        <button type="submit" class="unver-btn unver-btn-primary unver-w-full">
                            Register
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="unver-demo-watermark">
        PROJECT DEMO
    </div>

    <script src="../toast.js"></script>
    <script>
        <?php if (isset($_GET['msg'])): ?>showToast(<?php echo json_encode($_GET['msg']); ?>, 'success');<?php endif; ?>
        <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
    </script>
</body>
</html>