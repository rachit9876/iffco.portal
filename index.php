<?php
// --- Entry Point & Router ---
// This file acts as the main entry point for the application.
// It handles user authentication and routes them to the appropriate dashboard (user or admin).
// It also displays the main login page for users.

session_start(); // Start the session to manage user login state.

require_once 'db_connect.php'; // Include the database connection file.

$error_message = ''; // Variable to store any login error messages.

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // Prepare a statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $role, $status);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashed_password)) {

                if ($status === 'approved') {
                    // Password is correct, store session data
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $id;
                    $_SESSION['name'] = $name;
                    $_SESSION['role'] = $role;

                    // Redirect based on user role
                    if ($role == 'admin') {
                        header("location: admin/dashboard.php");
                        exit;
                    } else {
                        header("location: user/dashboard.php");
                        exit;
                    }
                } else {
                     $error_message = 'Your application is pending approval.';
                }
            } else {
                $error_message = 'The password you entered was not valid.';
            }
        } else {
            $error_message = 'No account found with that email.';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IFFCO Vocational Training Portal</title>
    <!-- <link href="/assets/CSS/tailwind.min.css" rel="stylesheet"> Back Up-->
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

   
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body style="background: url('https://i.postimg.cc/JnF4pk1Z/bg.png') no-repeat center center fixed; background-size: cover;">
    

    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" class="mx-auto mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Vocational Training Portal Login</h2>
                <p class="text-gray-600">Please sign in to continue</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" name="email" id="email" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" id="password" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Remember me </label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500"> Forgot your password? </a>
                    </div>
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign in
                    </button>
                </div>
            </form>
<div class="mt-6">
    <a href="user/register.php"
       class="w-full inline-block text-center py-3 px-6 rounded-xl shadow-lg text-sm font-semibold text-white
              bg-black hover:bg-gray-900 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-black">
        Apply Now
    </a>
</div>


        </div>
    </div>
</body>
</html>
