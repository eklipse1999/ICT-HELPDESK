<?php
$pageTitle  = 'Edit User';
$activePage = 'users';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db   = getDB();
$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u) { setFlash('error','User not found.'); header('Location: users.php'); exit; }

$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? $u['role'];
    $dept_id   = $_POST['department_id'] ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$full_name) $errors[] = 'Full name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $s = $db->prepare("UPDATE users SET full_name=?,email=?,username=?,password=?,role=?,department_id=?,is_active=? WHERE id=?");
            $s->bind_param('sssssiii',$full_name,$email,$username,$hash,$role,$dept_id,$is_active,$id);
        } else {
            $s = $db->prepare("UPDATE users SET full_name=?,email=?,username=?,role=?,department_id=?,is_active=? WHERE id=?");
            $s->bind_param('ssssiii',$full_name,$email,$username,$role,$dept_id,$is_active,$id);
        }
        $s->execute();
        setFlash('success','User updated successfully.');
        header('Location: users.php'); exit;
    }
    // Repopulate from POST
    $u = array_merge($u, $_POST);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-pencil me-2 text-primary"></i>Edit User</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="users.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Edit User: <?= htmlspecialchars($u['full_name']) ?></h5>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger small"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:620px;">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="staff"      <?= $u['role']==='staff'      ?'selected':'' ?>>Staff</option>
                            <option value="technician" <?= $u['role']==='technician' ?'selected':'' ?>>Technician</option>
                            <option value="admin"      <?= $u['role']==='admin'      ?'selected':'' ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $u['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $u['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Account Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                        <a href="users.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>