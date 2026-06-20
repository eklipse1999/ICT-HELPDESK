<?php
$pageTitle  = 'Add Asset';
$activePage = 'add_asset';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db          = getDB();
$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag  = strtoupper(trim($_POST['asset_tag'] ?? ''));
    $name       = trim($_POST['name'] ?? '');
    $category   = $_POST['category'] ?? '';
    $brand      = trim($_POST['brand'] ?? '');
    $model      = trim($_POST['model'] ?? '');
    $serial     = trim($_POST['serial_number'] ?? '');
    $status     = $_POST['status'] ?? 'Active';
    $dept_id    = $_POST['department_id'] ? (int)$_POST['department_id'] : null;
    $purch_date = $_POST['purchase_date'] ?: null;
    $notes      = trim($_POST['notes'] ?? '');

    if (!$asset_tag) $errors[] = 'Asset tag is required.';
    if (!$name)      $errors[] = 'Asset name is required.';
    if (!$category)  $errors[] = 'Category is required.';

    if (empty($errors)) {
        // Check duplicate tag
        $chk = $db->prepare("SELECT id FROM assets WHERE asset_tag=?");
        $chk->bind_param('s', $asset_tag);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Asset tag already exists.';
        } else {
            // types: asset_tag(s) name(s) category(s) brand(s) model(s) serial(s) status(s) dept_id(i) purchase_date(s) notes(s)
            $s = $db->prepare(
                "INSERT INTO assets (asset_tag,name,category,brand,model,serial_number,status,department_id,purchase_date,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $s->bind_param('sssssssiss', $asset_tag, $name, $category, $brand, $model, $serial, $status, $dept_id, $purch_date, $notes);
            $s->execute();
            setFlash('success', "Asset '{$name}' added successfully.");
            header('Location: view_assets.php');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Asset</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="view_assets.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Add New Asset</h5>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?>
        <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                        <input type="text" name="asset_tag" class="form-control mono"
                               value="<?= htmlspecialchars($_POST['asset_tag'] ?? '') ?>"
                               placeholder="e.g. ICT-001" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                               placeholder="e.g. Dell Optiplex 3080" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach (['Computer','Printer','Router','Switch','UPS','Monitor','Other'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control"
                               value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" placeholder="e.g. Dell, HP">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control"
                               value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control mono"
                               value="<?= htmlspecialchars($_POST['serial_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Active','Under Maintenance','Decommissioned'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($_POST['status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($_POST['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control"
                               value="<?= htmlspecialchars($_POST['purchase_date'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Save Asset
                        </button>
                        <a href="view_assets.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>