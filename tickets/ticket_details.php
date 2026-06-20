<?php
$pageTitle  = 'Ticket Details';
$activePage = 'tickets';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT t.*, u.full_name AS reporter, u.email AS reporter_email,
            d.name AS dept, ast.name AS asset_name, ast.asset_tag
     FROM tickets t
     LEFT JOIN users u ON t.created_by=u.id
     LEFT JOIN departments d ON t.department_id=d.id
     LEFT JOIN assets ast ON t.asset_id=ast.id
     WHERE t.id=?"
);
$stmt->bind_param('i',$id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();

if (!$t) { setFlash('error','Ticket not found.'); header('Location: view_tickets.php'); exit; }

// Staff can only view their own
if (isStaff() && $t['created_by'] != $_SESSION['user_id']) {
    setFlash('error','Access denied.'); header('Location: view_tickets.php'); exit;
}

// Assignment
$assign = $db->query(
    "SELECT a.*, u.full_name AS tech_name FROM assignments a
     LEFT JOIN users u ON a.technician_id=u.id
     WHERE a.ticket_id=$id ORDER BY a.assigned_at DESC LIMIT 1"
)->fetch_assoc();

// Maintenance logs linked to this ticket
$logs = $db->query(
    "SELECT ml.*, u.full_name AS tech, ast.name AS asset_name FROM maintenance_logs ml
     LEFT JOIN users u ON ml.technician_id=u.id
     LEFT JOIN assets ast ON ml.asset_id=ast.id
     WHERE ml.ticket_id=$id ORDER BY ml.maintenance_date DESC"
)->fetch_all(MYSQLI_ASSOC);

// Fetch ticket comments
$comments = $db->query(
    "SELECT tc.*, u.full_name, u.role FROM ticket_comments tc
     LEFT JOIN users u ON tc.user_id=u.id
     WHERE tc.ticket_id=$id ORDER BY tc.created_at ASC"
)->fetch_all(MYSQLI_ASSOC);

// Handle adding new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment'] ?? '');
    if (strlen($comment) > 0 && strlen($comment) <= 5000) {
        $stmt = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $id, $_SESSION['user_id'], $comment);
        if ($stmt->execute()) {
            setFlash('success', 'Comment added successfully.');
            header("Location: ticket_details.php?id=$id"); 
            exit;
        } else {
            setFlash('error', 'Failed to add comment.');
        }
    } else {
        setFlash('error', 'Comment must be between 1-5000 characters.');
    }
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    if (isAdmin() || isTechnician()) {
        $ns = $_POST['new_status'];
        $s = $db->prepare("UPDATE tickets SET status=? WHERE id=?");
        $s->bind_param('si',$ns,$id);
        $s->execute();
        setFlash('success','Ticket status updated.');
        header("Location: ticket_details.php?id=$id"); exit;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$priorityColor = ['Low'=>'#d1fae5:#065f46','Medium'=>'#fef3c7:#92400e','High'=>'#fee2e2:#991b1b','Critical'=>'#7f1d1d:#fef2f2'];
[$pbg,$pfg] = explode(':', $priorityColor[$t['priority']] ?? '#f1f5f9:#475569');

$statusColor = ['Open'=>'#dbeafe:#1d4ed8','In Progress'=>'#fef3c7:#92400e','Resolved'=>'#d1fae5:#065f46','Closed'=>'#f1f5f9:#475569'];
[$sbg,$sfg] = explode(':', $statusColor[$t['status']] ?? '#f1f5f9:#475569');
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-ticket-detailed me-2 text-primary"></i>Ticket: <span class="mono"><?= htmlspecialchars($t['ticket_no']) ?></span></span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="view_tickets.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0"><?= htmlspecialchars($t['title']) ?></h5>
        <span class="badge ms-2" style="background:<?=$sbg?>;color:<?=$sfg?>;"><?= $t['status'] ?></span>
        <span class="badge" style="background:<?=$pbg?>;color:<?=$pfg?>;"><?= $t['priority'] ?></span>
    </div>

    <div class="row g-3">
        <!-- Left: details -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Issue Description</div>
                <div class="card-body">
                    <p class="mb-0" style="line-height:1.7;"><?= nl2br(htmlspecialchars($t['description'])) ?></p>
                </div>
            </div>

            <?php if (!empty($logs)): ?>
            <div class="card">
                <div class="card-header">Maintenance / Repair Logs</div>
                <div class="card-body p-0">
                    <table class="table mb-0 small">
                        <thead><tr><th>Date</th><th>Technician</th><th>Action Taken</th><th>Cost (GHS)</th></tr></thead>
                        <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($l['maintenance_date'])) ?></td>
                            <td><?= htmlspecialchars($l['tech'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($l['action_taken']) ?></td>
                            <td><?= number_format($l['cost'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ticket Comments Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-chat-left-text me-2"></i>Updates & Comments
                    <span class="badge bg-secondary ms-2"><?= count($comments) ?></span>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($comments)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-chat-left" style="font-size: 2rem; opacity: 0.5;"></i>
                        <p class="mt-2 mb-0">No comments yet. Start the conversation!</p>
                    </div>
                    <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $c): ?>
                        <div class="comment-item mb-3 pb-3" style="border-bottom: 1px solid #eee; last-child: no border-bottom;">
                            <div class="d-flex gap-2 mb-1">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; flex-shrink: 0;">
                                    <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 0.9rem;">
                                        <?= htmlspecialchars($c['full_name']) ?>
                                        <span class="badge bg-light text-dark" style="font-size: 0.7rem; margin-left: 0.5rem;">
                                            <?= ucfirst($c['role']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('d M Y H:i', strtotime($c['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div style="margin-left: 40px; padding-top: 4px; font-size: 0.95rem; line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($c['comment'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <form method="POST" class="mt-2">
                        <textarea name="comment" class="form-control form-control-sm mb-2" 
                                  placeholder="Add a comment or update..." rows="3" required
                                  maxlength="5000" style="resize: vertical;"></textarea>
                        <div class="d-flex gap-2 justify-content-between">
                            <small class="text-muted">
                                <span id="charCount">0</span>/5000 characters
                            </small>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send me-1"></i>Post Comment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
                document.querySelector('textarea[name="comment"]').addEventListener('input', function() {
                    document.getElementById('charCount').textContent = this.value.length;
                });
            </script>
        </div>

        <!-- Right: meta + actions -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Ticket Information</div>
                <div class="card-body small">
                    <dl class="row mb-0" style="row-gap:.4rem;">
                        <dt class="col-5 text-muted">Ticket #</dt>
                        <dd class="col-7 mono"><?= htmlspecialchars($t['ticket_no']) ?></dd>

                        <dt class="col-5 text-muted">Category</dt>
                        <dd class="col-7"><?= htmlspecialchars($t['category']) ?></dd>

                        <dt class="col-5 text-muted">Reporter</dt>
                        <dd class="col-7"><?= htmlspecialchars($t['reporter']) ?></dd>

                        <dt class="col-5 text-muted">Department</dt>
                        <dd class="col-7"><?= htmlspecialchars($t['dept'] ?? '—') ?></dd>

                        <dt class="col-5 text-muted">Asset</dt>
                        <dd class="col-7"><?= $t['asset_tag'] ? htmlspecialchars($t['asset_tag'].' — '.$t['asset_name']) : '—' ?></dd>

                        <dt class="col-5 text-muted">Assigned To</dt>
                        <dd class="col-7"><?= $assign ? htmlspecialchars($assign['tech_name']) : '<span class="text-muted">Unassigned</span>' ?></dd>

                        <dt class="col-5 text-muted">Opened</dt>
                        <dd class="col-7"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></dd>

                        <dt class="col-5 text-muted">Updated</dt>
                        <dd class="col-7"><?= date('d M Y H:i', strtotime($t['updated_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <?php if (isAdmin() || isTechnician()): ?>
            <div class="card mb-3">
                <div class="card-header">Update Status</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-2">
                            <select name="new_status" class="form-select form-select-sm">
                                <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status']===$s?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="d-grid gap-2">
                <?php if (isAdmin()): ?>
                <a href="assign_ticket.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-check me-1"></i>Assign Ticket
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/maintenance/maintenance_log.php?ticket_id=<?= $t['id'] ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-tools me-1"></i>Log Maintenance
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>