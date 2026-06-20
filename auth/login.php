<?php
require_once __DIR__ . '/../config/session.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT id, full_name, username, password, role, department_id, is_active
             FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['dept_id']   = $user['department_id'];
            session_regenerate_id(true);
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or account is inactive.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — ICT Help Desk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page { min-height: 100vh; }
    </style>
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-logo">
        <img src="../assets/images/logo.jpg" alt="Techiman Municipal Assembly Logo">
    </div>
    <div class="login-org"><?= SITE_ORG ?></div>
    <div class="login-title">ICT Help Desk</div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label class="form-label">Username or Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Enter username" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-login w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </button>
    </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>