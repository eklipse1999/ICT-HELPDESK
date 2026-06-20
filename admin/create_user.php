<?php
$pageTitle  = 'Create User';
$activePage = 'users';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db          = getDB();
$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'staff';
    $dept_id   = $_POST['department_id'] ?: null;

    if (!$full_name) $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        // Check unique
        $s = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $s->bind_param('ss',$username,$email);
        $s->execute();
        if ($s->get_result()->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $s = $db->prepare("INSERT INTO users (full_name,email,username,password,role,department_id) VALUES (?,?,?,?,?,?)");
            $s->bind_param('sssssi',$full_name,$email,$username,$hash,$role,$dept_id);
            $s->execute();
            setFlash('success','User created successfully.');
            header('Location: users.php'); exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-person-plus me-2 text-primary"></i>Create User</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="users.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Create New User</h5>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?><div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:620px;">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name']??'') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select">
                            <option value="staff"      <?= ($_POST['role']??'staff')==='staff'      ?'selected':'' ?>>Staff</option>
                            <option value="technician" <?= ($_POST['role']??'')==='technician'      ?'selected':'' ?>>Technician</option>
                            <option value="admin"      <?= ($_POST['role']??'')==='admin'            ?'selected':'' ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($_POST['department_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-check me-1"></i>Create User
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>