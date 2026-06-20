<?php
$pageTitle  = 'Edit Asset';
$activePage = 'assets';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db   = getDB();
$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM assets WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();

if (!$a) { setFlash('error','Asset not found.'); header('Location: view_assets.php'); exit; }

$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $category   = $_POST['category'] ?? '';
    $brand      = trim($_POST['brand'] ?? '');
    $model      = trim($_POST['model'] ?? '');
    $serial     = trim($_POST['serial_number'] ?? '');
    $status     = $_POST['status'] ?? 'Active';
    $dept_id    = $_POST['department_id'] ? (int)$_POST['department_id'] : null;
    $purch_date = $_POST['purchase_date'] ?: null;
    $notes      = trim($_POST['notes'] ?? '');

    if (!$name)     $errors[] = 'Asset name is required.';
    if (!$category) $errors[] = 'Category is required.';

    if (empty($errors)) {
        // types: name(s) category(s) brand(s) model(s) serial(s) status(s) dept_id(i) purchase_date(s) notes(s) id(i)
        $s = $db->prepare(
            "UPDATE assets SET name=?,category=?,brand=?,model=?,serial_number=?,status=?,department_id=?,purchase_date=?,notes=? WHERE id=?"
        );
        $s->bind_param('ssssssissi', $name, $category, $brand, $model, $serial, $status, $dept_id, $purch_date, $notes, $id);
        $s->execute();
        setFlash('success', 'Asset updated.');
        header('Location: view_assets.php');
        exit;
    }
    $a = array_merge($a, $_POST);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-pencil me-2 text-primary"></i>Edit Asset</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="view_assets.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Edit Asset: <span class="mono text-primary"><?= htmlspecialchars($a['asset_tag']) ?></span></h5>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Asset Tag</label>
                        <input type="text" class="form-control mono" value="<?= htmlspecialchars($a['asset_tag']) ?>" disabled>
                        <small class="text-muted">Asset tag cannot be changed after creation.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($a['name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <?php foreach (['Computer','Printer','Router','Switch','UPS','Monitor','Other'] as $c): ?>
                            <option value="<?= $c ?>" <?= $a['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($a['brand'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($a['model'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control mono" value="<?= htmlspecialchars($a['serial_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Active','Under Maintenance','Decommissioned'] as $s): ?>
                            <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $a['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= htmlspecialchars($a['purchase_date'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($a['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                        <a href="view_assets.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>