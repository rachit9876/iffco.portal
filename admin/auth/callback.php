<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../db_connect.php';

// Basic validation
if (!isset($_GET['state']) || !isset($_SESSION['github_oauth_state']) || $_GET['state'] !== $_SESSION['github_oauth_state']) {
    header('Location: /index.php?error=' . urlencode('Invalid OAuth state'));
    exit;
}

if (!isset($_GET['code'])) {
    header('Location: /index.php?error=' . urlencode('Authorization code not found'));
    exit;
}

$code = $_GET['code'];

// Exchange code for access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GITHUB_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'state' => $_SESSION['github_oauth_state']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resp = curl_exec($ch);
if ($resp === false) {
    header('Location: /index.php?error=' . urlencode('Failed to get access token'));
    exit;
}
$data = json_decode($resp, true);
curl_close($ch);

if (empty($data['access_token'])) {
    header('Location: /index.php?error=' . urlencode('Access token not returned'));
    exit;
}

$access_token = $data['access_token'];

// Fetch user profile
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GITHUB_API_USER);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $access_token,
    'User-Agent: IFFCO-Portal-App'
]);
$user_json = curl_exec($ch);
if ($user_json === false) {
    header('Location: /index.php?error=' . urlencode('Failed to fetch GitHub user'));
    exit;
}
$user = json_decode($user_json, true);

// Fetch emails to get primary email if not present
$email = '';
if (!empty($user['email'])) {
    $email = $user['email'];
} else {
    curl_setopt($ch, CURLOPT_URL, GITHUB_API_EMAILS);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $emails_json = curl_exec($ch);
    $emails = json_decode($emails_json, true);
    if (is_array($emails)) {
        foreach ($emails as $e) {
            if (!empty($e['primary']) && !empty($e['email'])) {
                $email = $e['email'];
                break;
            }
        }
        if (empty($email) && isset($emails[0]['email'])) $email = $emails[0]['email'];
    }
}

if (empty($email)) {
    header('Location: /index.php?error=' . urlencode('Could not determine email from GitHub'));
    exit;
}

$name = !empty($user['name']) ? $user['name'] : (!empty($user['login']) ? $user['login'] : $email);

// Determine action (login or connect)
$action = isset($_SESSION['github_oauth_action']) ? $_SESSION['github_oauth_action'] : 'login';

// CONNECT flow: link GitHub to currently logged-in user
if ($action === 'connect') {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: /index.php?error=' . urlencode('You must be logged in to connect GitHub.'));
        exit;
    }

    $current_user_id = $_SESSION['id'];
    $github_id = isset($user['id']) ? strval($user['id']) : null;

    // Check if this GitHub account (github_id or github_email) is already connected to another user
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE (github_id = ? OR github_email = ?) AND id != ?");
    $check_stmt->bind_param('ssi', $github_id, $email, $current_user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        header('Location: /user/profile.php?error=' . urlencode('This GitHub account is already connected to another user.'));
        exit;
    }
    $check_stmt->close();

    // store github id and email on the user
    $update = $conn->prepare("UPDATE users SET github_id = ?, github_email = ? WHERE id = ?");
    $update->bind_param('ssi', $github_id, $email, $current_user_id);
    if ($update->execute()) {
        header('Location: /user/profile.php?msg=' . urlencode('GitHub account connected successfully'));
        exit;
    } else {
        header('Location: /user/profile.php?error=' . urlencode('Failed to connect GitHub account'));
        exit;
    }
}

// LOGIN flow: only allow login if an existing user is found; do NOT auto-create
// Try to find by github_id first, then by email
$found_stmt = $conn->prepare("SELECT id, name, role, status FROM users WHERE github_id = ? OR email = ?");
$github_id = isset($user['id']) ? strval($user['id']) : null;
$found_stmt->bind_param('ss', $github_id, $email);
$found_stmt->execute();
$found_stmt->store_result();

if ($found_stmt->num_rows > 0) {
    $found_stmt->bind_result($id, $db_name, $role, $status);
    $found_stmt->fetch();

    if ($status !== 'approved') {
        header('Location: /index.php?error=' . urlencode('Your account is pending approval'));
        exit;
    }

    // Set session and redirect accordingly
    $_SESSION['loggedin'] = true;
    $_SESSION['id'] = $id;
    $_SESSION['name'] = $db_name;
    $_SESSION['role'] = $role;

    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        header('Location: /user/dashboard.php');
        exit;
    }

} else {
    // No matching local account. Do NOT create one automatically.
    header('Location: /index.php?error=' . urlencode('No local account found for this GitHub user. To use GitHub login, first sign up using the site and then connect GitHub from Profile & Details.'));
    exit;
}

?>
