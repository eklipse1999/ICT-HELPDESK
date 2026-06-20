<?php
$pageTitle  = 'Assets';
$activePage = 'assets';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$db = getDB();

// Search / filter
$search = trim($_GET['search'] ?? '');
$cat    = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$where = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(a.name LIKE ? OR a.asset_tag LIKE ? OR a.brand LIKE ? OR a.serial_number LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like,$like,$like,$like]);
    $types .= 'ssss';
}
if ($cat !== '') {
    $where[]  = "a.category = ?";
    $params[] = $cat; $types .= 's';
}
if ($status !== '') {
    $where[]  = "a.status = ?";
    $params[] = $status; $types .= 's';
}

$sql = "SELECT a.*, d.name AS dept_name FROM assets a
        LEFT JOIN departments d ON d.id=a.department_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.created_at DESC";

$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-hdd-rack me-2 text-primary"></i>Asset Inventory</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-semibold mb-0">ICT Assets</h5>
            <small class="text-muted"><?= count($assets) ?> asset(s) found</small>
        </div>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/assets_management/add_asset.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Asset
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search by name, tag, brand, serial…"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach (['Computer','Printer','Router','Switch','UPS','Monitor','Other'] as $c): ?>
                        <option value="<?= $c ?>" <?= $cat===$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <?php foreach (['Active','Under Maintenance','Decommissioned'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                    <a href="view_assets.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand / Model</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Purchase Date</th>
                            <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($assets)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No assets found</td></tr>
                    <?php else: ?>
                    <?php foreach ($assets as $a):
                        $statusColors = ['Active'=>'#d1fae5:#065f46','Under Maintenance'=>'#fef3c7:#92400e','Decommissioned'=>'#fee2e2:#991b1b'];
                        [$sbg,$sfg] = explode(':', $statusColors[$a['status']] ?? '#f1f5f9:#475569');
                    ?>
                    <tr>
                        <td><span class="mono"><?= htmlspecialchars($a['asset_tag']) ?></span></td>
                        <td class="fw-500"><?= htmlspecialchars($a['name']) ?></td>
                        <td><?= htmlspecialchars($a['category']) ?></td>
                        <td><?= htmlspecialchars(($a['brand']??'').' '.($a['model']??'')) ?></td>
                        <td><?= htmlspecialchars($a['dept_name'] ?? '—') ?></td>
                        <td><span class="badge" style="background:<?=$sbg?>;color:<?=$sfg?>;"><?= $a['status'] ?></span></td>
                        <td><?= $a['purchase_date'] ? date('d M Y', strtotime($a['purchase_date'])) : '—' ?></td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <a href="edit_asset.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="delete_asset.php" class="d-inline" onsubmit="return confirm('Delete this asset?')">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>