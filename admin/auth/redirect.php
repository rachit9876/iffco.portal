<?php
session_start();
require_once __DIR__ . '/config.php';

// generate anti-forgery state
$state = bin2hex(random_bytes(16));
$_SESSION['github_oauth_state'] = $state;

// allow an optional action, e.g. ?action=connect to link GitHub to existing profile
$action = isset($_GET['action']) && $_GET['action'] === 'connect' ? 'connect' : 'login';
$_SESSION['github_oauth_action'] = $action;

$params = http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'scope' => GITHUB_OAUTH_SCOPE,
    'state' => $state,
    'allow_signup' => 'true'
]);

header('Location: ' . GITHUB_AUTHORIZE_URL . '?' . $params);
exit;

?>
