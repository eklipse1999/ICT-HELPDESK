<?php
// Root entry point — redirect to dashboard if logged in, else to login
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;