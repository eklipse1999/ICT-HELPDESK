<?php
$pageTitle  = 'Tickets';
$activePage = 'tickets';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$db = getDB();

$status_f   = $_GET['status'] ?? '';
$priority_f = $_GET['priority'] ?? '';
$search     = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
$types  = '';

if ($status_f   !== '') { $where[] = "t.status=?";   $params[] = $status_f;   $types .= 's'; }
if ($priority_f !== '') { $where[] = "t.priority=?";  $params[] = $priority_f; $types .= 's'; }
if ($search     !== '') {
    $where[] = "(t.ticket_no LIKE ? OR t.title LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $types .= 'ss';
}

$sql = "SELECT t.*, u.full_name AS reporter, d.name AS dept,
               u2.full_name AS assigned_to
        FROM tickets t
        LEFT JOIN users u  ON t.created_by=u.id
        LEFT JOIN departments d ON t.department_id=d.id
        LEFT JOIN assignments a ON a.ticket_id=t.id
        LEFT JOIN users u2 ON a.technician_id=u2.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

function priorityBadge($p){ $m=['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high','Critical'=>'badge-critical']; return '<span class="badge '.($m[$p]??'bg-secondary').'">'.$p.'</span>'; }
function statusBadge($s)  { $k=strtolower(str_replace(' ','',$s)); return '<span class="badge badge-'.$k.'">'.$s.'</span>'; }
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-ticket-detailed me-2 text-primary"></i>All Tickets</span>
    <div class="user-badge" style="margin-left:auto;display:flex;align-items:center;gap:.6rem;">
        <?php if (isAdmin()): ?>
        <div style="position:relative;">
            <?php
            $unassigned = $db->query("SELECT COUNT(*) FROM tickets t LEFT JOIN assignments a ON a.ticket_id=t.id WHERE t.status='Open' AND a.id IS NULL")->fetch_row()[0];
            ?>
            <button class="notif-btn" id="notifBtn" title="Unassigned Tickets">
                <i class="bi bi-bell"></i>
                <span class="notif-badge <?= $unassigned>0?'':'hidden' ?>" id="notifBadge"><?= $unassigned>9?'9+':$unassigned ?></span>
            </button>
        </div>
        <?php endif; ?>
        <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <small><?= ucfirst($_SESSION['role']) ?></small>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<div class="notif-dropdown" id="notifDropdown">
    <div class="notif-header"><span>🔔 Unassigned Tickets</span><a href="<?= BASE_URL ?>/tickets/assign_ticket.php">Assign →</a></div>
    <div class="notif-list" id="notifList"><div class="notif-empty">Loading…</div></div>
    <div class="notif-footer"><a href="<?= BASE_URL ?>/tickets/view_tickets.php?status=Open">View all open tickets</a></div>
</div>
<?php endif; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-semibold mb-0">All Support Tickets</h5>
            <small class="text-muted"><?= count($tickets) ?> ticket(s) found</small>
        </div>
        <a href="create_ticket.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>New Ticket</a>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search ticket # or title…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status_f===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All Priorities</option>
                        <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                        <option value="<?= $p ?>" <?= $priority_f===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="view_tickets.php" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                            <th>Ticket #</th><th>Title</th><th>Category</th><th>Reporter</th>
                            <th>Assigned To</th><th>Priority</th><th>Status</th><th>Date</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No tickets found</td></tr>
                    <?php else: foreach ($tickets as $t): ?>
                    <tr>
                        <td><span class="mono text-primary"><?= htmlspecialchars($t['ticket_no']) ?></span></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td><?= htmlspecialchars($t['category']) ?></td>
                        <td><?= htmlspecialchars($t['reporter']) ?></td>
                        <td><?= $t['assigned_to'] ? htmlspecialchars($t['assigned_to']) : '<span class="text-muted">Unassigned</span>' ?></td>
                        <td><?= priorityBadge($t['priority']) ?></td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                        <td><a href="ticket_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<script>
(function(){
    const btn=document.getElementById('notifBtn'),dropdown=document.getElementById('notifDropdown'),list=document.getElementById('notifList'),badge=document.getElementById('notifBadge'),BASE='<?= BASE_URL ?>';
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    function pColor(p){return{Critical:'#ef4444',High:'#f97316',Medium:'#f59e0b',Low:'#10b981'}[p]||'#64748b';}
    function dotCls(p){return{Critical:'critical',High:'high',Medium:'medium',Low:'low'}[p]||'medium';}
    function load(){
        fetch(BASE+'/api/notifications.php').then(r=>r.json()).then(data=>{
            data.count>0?(badge.textContent=data.count>9?'9+':data.count,badge.classList.remove('hidden')):badge.classList.add('hidden');
            list.innerHTML=data.tickets.length?data.tickets.map(t=>`<a class="notif-item" href="${BASE}/tickets/ticket_details.php?id=${t.id}"><span class="notif-dot ${dotCls(t.priority)}"></span><div style="flex:1;min-width:0;"><div class="notif-title">${esc(t.title)}</div><div class="notif-meta"><span class="mono">${esc(t.ticket_no)}</span> &middot; <strong style="color:${pColor(t.priority)}">${t.priority}</strong> &middot; ${esc(t.ago)}</div></div></a>`).join(''):'<div class="notif-empty">All tickets assigned!</div>';
        }).catch(()=>{});
    }
    btn.addEventListener('click',e=>{e.stopPropagation();dropdown.classList.toggle('open');if(dropdown.classList.contains('open'))load();});
    document.addEventListener('click',e=>{if(!dropdown.contains(e.target)&&e.target!==btn)dropdown.classList.remove('open');});
    load();setInterval(load,60000);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>