<?php
require_once __DIR__ . '/config/config.php';

// login.php is not included via csrf.php, so it loads the config directly.
// Redirect to the front page using an absolute, sub-folder-aware path.
header('Location: ' . BASE_URL . '/index.html');
exit();
?>
