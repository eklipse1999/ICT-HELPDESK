<?php
// includes/header.php
// Usage: include at the top of every page AFTER setting $pageTitle
// Requires $pageTitle to be set before including.
require_once __DIR__ . '/../config/session.php';

$flash = getFlash();
$user  = currentUser();
$title = ($pageTitle ?? 'Dashboard') . ' — ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>/assets/images/logo.jpg">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<button class="mobile-menu-btn" type="button" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>