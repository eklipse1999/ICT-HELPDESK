<?php
$pageTitle  = 'Users';
$activePage = 'users';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db = getDB();
$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$users = $db->query(
    "SELECT u.*, d.name AS dept_name FROM users u
     LEFT JOIN departments d ON d.id=u.department_id
     ORDER BY u.full_name"
)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-people me-2 text-primary"></i>Users</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
            <small><?= ucfirst($user['role']) ?></small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-semibold mb-0">System Users</h5>
            <small class="text-muted"><?= count($users) ?> registered users</small>
        </div>
        <a href="<?= BASE_URL ?>/admin/create_user.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add User
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td class="fw-500"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="mono"><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php
                        $roleColors = ['admin'=>'#dbeafe:#1d4ed8','technician'=>'#fef3c7:#92400e','staff'=>'#f1f5f9:#475569'];
                        [$bg,$fg] = explode(':', $roleColors[$u['role']] ?? '#f1f5f9:#475569');
                        ?>
                        <span class="badge" style="background:<?=$bg?>;color:<?=$fg?>;"><?= ucfirst($u['role']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($u['dept_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge" style="background:#d1fae5;color:#065f46;">Active</span>
                        <?php else: ?>
                            <span class="badge" style="background:#fee2e2;color:#991b1b;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/users_action.php" class="d-inline"
                              onsubmit="return confirm('Toggle status for this user?')">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="toggle">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>