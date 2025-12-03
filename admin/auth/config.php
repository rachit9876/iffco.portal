<?php
// GitHub OAuth configuration - fill these with values from your GitHub OAuth app
// Create an OAuth app at https://github.com/settings/developers and set the
// Authorization callback URL to: https://simplex.rf.gd/admin/auth/callback.php

define('GITHUB_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
define('GITHUB_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
// Publicly-accessible callback URL (must match GitHub app)
define('GITHUB_REDIRECT_URI', 'https://iffco-portal.page.gd/admin/auth/callback.php');

// Optional: requested scopes
define('GITHUB_OAUTH_SCOPE', 'read:user user:email');

// Helper: GitHub endpoints
define('GITHUB_AUTHORIZE_URL', 'https://github.com/login/oauth/authorize');
define('GITHUB_TOKEN_URL', 'https://github.com/login/oauth/access_token');
define('GITHUB_API_USER', 'https://api.github.com/user');
define('GITHUB_API_EMAILS', 'https://api.github.com/user/emails');

// NOTE: Keep client secret safe. Do not commit real secrets to public repos.

?>
