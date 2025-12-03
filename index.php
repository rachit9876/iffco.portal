<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        header("Location: index.php?error=Please enter both email and password");
        exit;
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $role, $status);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                if ($status === 'approved') {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $id;
                    $_SESSION['name'] = $name;
                    $_SESSION['role'] = $role;

                    if ($role == 'admin') {
                        header("location: admin/dashboard.php");
                        exit;
                    } else {
                        header("location: user/dashboard.php");
                        exit;
                    }
                } else {
                    header("Location: index.php?error=Your application is pending approval");
                    exit;
                }
            } else {
                header("Location: index.php?error=The password you entered was not valid");
                exit;
            }
        } else {
            header("Location: index.php?error=No account found with that email");
            exit;
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IFFCO Portal (DEMO)</title>
    <link rel="stylesheet" href="neobrutalist.css">
</head>
<body>
    <div class="unver-demo-banner">
        ⚠️ DEMO WEBSITE - NOT REAL IFFCO PORTAL ⚠️
    </div>

    <div class="unver-bg-fixed unver-bg-cover" style="background-image: url('https://cdn-fast.pages.dev/iffco-portal/bg.webp');"></div>

    <div class="unver-min-h-screen unver-flex unver-items-center unver-justify-center" style="padding: 80px 20px 20px;">
        <div style="max-width: 450px; width: 100%; background: #fff; border: 4px solid #000; padding: 30px;">
            
            <div class="unver-text-center unver-mb-lg">
                <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" style="max-width: 120px; margin: 0 auto 20px; display: block;">
                <h2 class="unver-h2 unver-mb-sm">Vocational Training Portal</h2>
                <p class="unver-text-muted unver-mb-sm">Please sign in to continue</p>
                <p class="unver-text-danger unver-font-bold">DEMO SITE</p>
            </div>



            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="unver-form-group">
                    <label for="email" class="unver-label">Email Address</label>
                    <input type="email" name="email" id="email" class="unver-input" required>
                </div>
                
                <div class="unver-form-group">
                    <label for="password" class="unver-label">Password</label>
                    <input type="password" name="password" id="password" class="unver-input" required>
                </div>
                
                <div class="unver-flex unver-items-center unver-justify-between unver-mb-lg">
                    <div class="unver-flex unver-items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="unver-checkbox">
                        <label for="remember-me" class="unver-text-sm" style="margin-left: 8px;">Remember me</label>
                    </div>
                    <a href="#" class="unver-text-sm unver-text-primary">Forgot password?</a>
                </div>
                
                <button type="button" onclick="document.getElementById('email').value='student@example.com';document.getElementById('password').value='123';this.form.submit();" class="unver-btn unver-btn-success unver-w-full unver-mb-md">
                    Test Now
                </button>

                <button type="submit" class="unver-btn unver-btn-primary unver-w-full unver-mb-md">
                    Sign in
                </button>

                <a href="admin/auth/redirect.php" class="unver-btn" style="background:#24292e;color:#fff;border:0;display:block;text-align:center;padding:10px;margin-top:8px;text-decoration:none;border-radius:4px;">
                    Sign in with GitHub
                </a>

                <a href="user/register.php" class="unver-btn unver-btn-success unver-w-full" style="text-decoration: none; margin-top:8px;">
                    Apply Now
                </a>
            </form>
        </div>
    </div>

    <div class="unver-demo-watermark">
        PROJECT DEMO
    </div>

    <script src="toast.js"></script>
    <script>
        <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
    </script>
</body>
</html>
