<?php
$pageTitle  = 'Assign Ticket';
$activePage = 'assign_ticket';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db          = getDB();
$technicians = $db->query("SELECT id,full_name FROM users WHERE role='technician' AND is_active=1 ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// Pre-select ticket if passed
$preTicketId = (int)($_GET['id'] ?? 0);

// Unassigned / open tickets
$openTickets = $db->query(
    "SELECT t.id, t.ticket_no, t.title, t.priority, t.status, u.full_name AS reporter
     FROM tickets t
     LEFT JOIN users u ON t.created_by=u.id
     WHERE t.status IN ('Open','In Progress')
     ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.created_at"
)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id    = (int)($_POST['ticket_id'] ?? 0);
    $tech_id      = (int)($_POST['technician_id'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');
    $assigned_by  = (int)$_SESSION['user_id'];

    if ($ticket_id && $tech_id) {
        // Remove previous assignment
        $db->query("DELETE FROM assignments WHERE ticket_id=$ticket_id");
        // Insert new
        $s = $db->prepare("INSERT INTO assignments (ticket_id,technician_id,assigned_by,notes) VALUES (?,?,?,?)");
        $s->bind_param('iiis',$ticket_id,$tech_id,$assigned_by,$notes);
        $s->execute();
        // Update ticket status to In Progress
        $db->query("UPDATE tickets SET status='In Progress' WHERE id=$ticket_id AND status='Open'");
        setFlash('success','Ticket assigned successfully.');
        header('Location: assign_ticket.php'); exit;
    } else {
        setFlash('error','Please select both a ticket and a technician.');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$priorityColors = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high','Critical'=>'badge-critical'];
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-person-check me-2 text-primary"></i>Assign Tickets</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="row g-3">
        <!-- Assignment form -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Assign a Ticket</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ticket <span class="text-danger">*</span></label>
                            <select name="ticket_id" class="form-select" required>
                                <option value="">— Select Ticket —</option>
                                <?php foreach ($openTickets as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $preTicketId===$t['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($t['ticket_no'].' — '.$t['title']) ?> [<?= $t['priority'] ?>]
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select name="technician_id" class="form-select" required>
                                <option value="">— Select Technician —</option>
                                <?php foreach ($technicians as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assignment Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional instructions…"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-check me-1"></i>Assign Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Open tickets list -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Open / In-Progress Tickets</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead><tr><th>Ticket #</th><th>Title</th><th>Priority</th><th>Reporter</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($openTickets)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No open tickets</td></tr>
                        <?php else: ?>
                        <?php foreach ($openTickets as $t): ?>
                        <tr>
                            <td class="mono"><?= htmlspecialchars($t['ticket_no']) ?></td>
                            <td><?= htmlspecialchars($t['title']) ?></td>
                            <td><span class="badge <?= $priorityColors[$t['priority']]??'bg-secondary' ?>"><?= $t['priority'] ?></span></td>
                            <td><?= htmlspecialchars($t['reporter']) ?></td>
                            <td>
                                <a href="ticket_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>