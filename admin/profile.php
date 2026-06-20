<?php
$pageTitle  = 'My Profile';
$activePage = 'profile';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch current user details (use a different variable to avoid colliding with header's $user)
$stmt = $db->prepare(
    "SELECT id, full_name, email, username, role, department_id, created_at
     FROM users WHERE id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();

$message = '';
$message_type = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($current_password)) {
        $message = 'Current password is required.';
        $message_type = 'error';
    } elseif (empty($new_password)) {
        $message = 'New password is required.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New password and confirmation do not match.';
        $message_type = 'error';
    } elseif ($current_password === $new_password) {
        $message = 'New password must be different from current password.';
        $message_type = 'error';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $result['password'])) {
            $message = 'Current password is incorrect.';
            $message_type = 'error';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update password. Please try again.';
                $message_type = 'error';
            }
        }
    }
}

// Fetch department name
$dept = null;
if (!empty($dbUser['department_id'])) {
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->bind_param('i', $dbUser['department_id']);
    $stmt->execute();
    $dept = $stmt->get_result()->fetch_assoc();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-person-circle me-2 text-primary"></i>My Profile</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'] ?? '',0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name'] ?? '') ?></div>
            <small><?= ucfirst($user['role'] ?? '') ?></small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="mb-4">
        <h5 class="fw-semibold">My Profile</h5>
        <small class="text-muted">Manage your account settings and change password</small>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Left: Profile Information -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Account Information
                </div>
                <div class="card-body">
                    <dl class="row mb-0" style="row-gap: 1rem;">
                        <dt class="col-sm-4 text-muted">Full Name</dt>
                        <dd class="col-sm-8 fw-semibold"><?= htmlspecialchars($dbUser['full_name'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">Username</dt>
                        <dd class="col-sm-8 mono"><?= htmlspecialchars($dbUser['username'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">Email</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($dbUser['email'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">Role</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= ($dbUser['role'] ?? '') === 'admin' ? 'danger' : ((($dbUser['role'] ?? '') === 'technician') ? 'warning' : 'info') ?>">
                                <?= ucfirst($dbUser['role'] ?? '') ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Department</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($dept['name'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-4 text-muted">Member Since</dt>
                        <dd class="col-sm-8"><?= !empty($dbUser['created_at']) ? date('d M Y', strtotime($dbUser['created_at'])) : '' ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Right: Change Password -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-key me-2"></i>Change Password
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                   placeholder="Enter your current password" required>
                            <small class="text-muted d-block mt-1">For security, we need to verify your current password.</small>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password (min 8 characters)" required minlength="8">
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                Minimum 8 characters. Use a mix of uppercase, lowercase, numbers, and symbols for better security.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password" required minlength="8">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-lock me-1"></i>Update Password
                            </button>
                        </div>
                    </form>

                    <div class="alert alert-info mt-3 mb-0" role="alert">
                        <small>
                            <i class="bi bi-shield-check me-1"></i>
                            Your password is securely hashed and encrypted. Never share your password with anyone.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
