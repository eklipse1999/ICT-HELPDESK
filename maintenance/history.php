<?php
$pageTitle  = 'Maintenance History';
$activePage = 'maintenance_history';
require_once __DIR__ . '/../config/session.php';
requireRole('admin','technician');

$db = getDB();

$logs = $db->query(
    "SELECT ml.*, a.name AS asset_name, a.asset_tag,
            u.full_name AS tech_name, t.ticket_no
     FROM maintenance_logs ml
     LEFT JOIN assets a ON ml.asset_id=a.id
     LEFT JOIN users u ON ml.technician_id=u.id
     LEFT JOIN tickets t ON ml.ticket_id=t.id
     ORDER BY ml.maintenance_date DESC, ml.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$totalCost = array_sum(array_column($logs,'cost'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-clock-history me-2 text-primary"></i>Maintenance History</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-semibold mb-0">Maintenance History</h5>
            <small class="text-muted"><?= count($logs) ?> records · Total cost: GHS <?= number_format($totalCost,2) ?></small>
        </div>
        <a href="maintenance_log.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Log Maintenance
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Asset</th>
                            <th>Ticket</th>
                            <th>Technician</th>
                            <th>Action Taken</th>
                            <th>Cost (GHS)</th>
                            <th>Next Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No maintenance records yet</td></tr>
                    <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($l['maintenance_date'])) ?></td>
                        <td>
                            <span class="mono small"><?= htmlspecialchars($l['asset_tag']) ?></span><br>
                            <small class="text-muted"><?= htmlspecialchars($l['asset_name']) ?></small>
                        </td>
                        <td>
                            <?php if ($l['ticket_no']): ?>
                            <a href="<?= BASE_URL ?>/tickets/ticket_details.php?id=<?= $l['ticket_id'] ?>" class="mono small text-primary">
                                <?= htmlspecialchars($l['ticket_no']) ?>
                            </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($l['tech_name'] ?? '—') ?></td>
                        <td style="max-width:260px;"><?= htmlspecialchars($l['action_taken']) ?></td>
                        <td><?= number_format($l['cost'],2) ?></td>
                        <td><?= $l['next_maintenance_date'] ? date('d M Y', strtotime($l['next_maintenance_date'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>