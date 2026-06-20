<?php
$pageTitle  = 'Log Maintenance';
$activePage = 'maintenance_log';
require_once __DIR__ . '/../config/session.php';
requireRole('admin', 'technician');

$db          = getDB();
$assets      = $db->query("SELECT id,asset_tag,name FROM assets ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$technicians = $db->query("SELECT id,full_name FROM users WHERE role IN ('technician','admin') AND is_active=1 ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$tickets     = $db->query("SELECT id,ticket_no,title FROM tickets WHERE status IN ('Open','In Progress') ORDER BY ticket_no DESC")->fetch_all(MYSQLI_ASSOC);

$preTicketId = (int)($_GET['ticket_id'] ?? 0);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id   = (int)($_POST['asset_id'] ?? 0);
    $ticket_id  = $_POST['ticket_id']      ? (int)$_POST['ticket_id']      : null;
    $tech_id    = $_POST['technician_id']  ? (int)$_POST['technician_id']  : null;
    $action     = trim($_POST['action_taken'] ?? '');
    $maint_date = $_POST['maintenance_date'] ?? date('Y-m-d');
    $next_date  = $_POST['next_maintenance_date'] ?: null;
    $cost       = (float)($_POST['cost'] ?? 0);

    if (!$asset_id) $errors[] = 'Please select an asset.';
    if (!$action)   $errors[] = 'Action taken is required.';

    if (empty($errors)) {
        // types: asset_id(i) ticket_id(i) tech_id(i) action(s) maint_date(s) next_date(s) cost(d)
        $s = $db->prepare(
            "INSERT INTO maintenance_logs (asset_id,ticket_id,technician_id,action_taken,maintenance_date,next_maintenance_date,cost)
             VALUES (?,?,?,?,?,?,?)"
        );
        $s->bind_param('iiisssd', $asset_id, $ticket_id, $tech_id, $action, $maint_date, $next_date, $cost);
        $s->execute();

        if ($ticket_id && isset($_POST['resolve_ticket'])) {
            $db->query("UPDATE tickets SET status='Resolved' WHERE id=$ticket_id");
        }

        setFlash('success', 'Maintenance record saved.');
        header('Location: history.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-tools me-2 text-primary"></i>Log Maintenance</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="history.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Log Maintenance Activity</h5>
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
                    <div class="col-12">
                        <label class="form-label">Asset <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select" required>
                            <option value="">— Select Asset —</option>
                            <?php foreach ($assets as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($_POST['asset_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['asset_tag'] . ' — ' . $a['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Related Ticket <small class="text-muted">(optional)</small></label>
                        <select name="ticket_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($tickets as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($preTicketId === $t['id'] || ($_POST['ticket_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['ticket_no'] . ' — ' . $t['title']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Technician</label>
                        <select name="technician_id" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Action Taken <span class="text-danger">*</span></label>
                        <textarea name="action_taken" class="form-control" rows="3"
                                  placeholder="Describe what was done…" required><?= htmlspecialchars($_POST['action_taken'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maintenance Date</label>
                        <input type="date" name="maintenance_date" class="form-control"
                               value="<?= htmlspecialchars($_POST['maintenance_date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Next Maintenance Date</label>
                        <input type="date" name="next_maintenance_date" class="form-control"
                               value="<?= htmlspecialchars($_POST['next_maintenance_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost (GHS)</label>
                        <input type="number" name="cost" class="form-control" min="0" step="0.01"
                               value="<?= htmlspecialchars($_POST['cost'] ?? '0') ?>">
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="resolve_ticket" id="resolve_ticket">
                            <label class="form-check-label small" for="resolve_ticket">Mark linked ticket as Resolved</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Log</button>
                        <a href="history.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>