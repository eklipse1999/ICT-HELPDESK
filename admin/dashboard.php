<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../config/session.php';
requireLogin();
date_default_timezone_set('Africa/Accra');

$db   = getDB();
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// ── Greeting ───────────────────────────────────────────────
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', $_SESSION['full_name'])[0];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// ══════════════════════════════════════════════════════════
//  ADMIN DASHBOARD
// ══════════════════════════════════════════════════════════
if ($role === 'admin'):

$totalAssets     = $db->query("SELECT COUNT(*) FROM assets")->fetch_row()[0];
$activeAssets    = $db->query("SELECT COUNT(*) FROM assets WHERE status='Active'")->fetch_row()[0];
$openTickets     = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetch_row()[0];
$totalUsers      = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$unassigned      = $db->query("SELECT COUNT(*) FROM tickets t LEFT JOIN assignments a ON a.ticket_id=t.id WHERE t.status='Open' AND a.id IS NULL")->fetch_row()[0];
$resolvedToday   = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Resolved' AND DATE(updated_at)=CURDATE()")->fetch_row()[0];

$recentTickets = $db->query(
    "SELECT t.id, t.ticket_no, t.title, t.priority, t.status, t.created_at,
            u.full_name AS reporter, d.name AS dept
     FROM tickets t
     LEFT JOIN users u ON t.created_by=u.id
     LEFT JOIN departments d ON t.department_id=d.id
     ORDER BY t.created_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$statusData = $db->query("SELECT status, COUNT(*) AS cnt FROM tickets GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$totalTickets = $db->query("SELECT COUNT(*) FROM tickets")->fetch_row()[0];
$statusMap = ['Open'=>0,'In Progress'=>0,'Resolved'=>0,'Closed'=>0];
foreach ($statusData as $s) $statusMap[$s['status']] = (int)$s['cnt'];
?>

<!-- TOPBAR -->
<div class="topbar">
    <span class="page-title"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</span>
    <div class="user-badge" style="margin-left:auto;display:flex;align-items:center;gap:.6rem;">
        <!-- Notification Bell -->
        <div style="position:relative;">
            <button class="notif-btn" id="notifBtn" title="Unassigned Tickets">
                <i class="bi bi-bell"></i>
                <?php if ($unassigned > 0): ?>
                <span class="notif-badge" id="notifBadge"><?= $unassigned > 9 ? '9+' : $unassigned ?></span>
                <?php else: ?>
                <span class="notif-badge hidden" id="notifBadge"></span>
                <?php endif; ?>
            </button>
        </div>
        <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <small>Administrator</small>
        </div>
    </div>
</div>

<!-- Notification Dropdown -->
<div class="notif-dropdown" id="notifDropdown">
    <div class="notif-header">
        <span>🔔 Unassigned Tickets</span>
        <a href="<?= BASE_URL ?>/tickets/assign_ticket.php">Assign →</a>
    </div>
    <div class="notif-list" id="notifList">
        <div class="notif-empty">Loading…</div>
    </div>
    <div class="notif-footer">
        <a href="<?= BASE_URL ?>/tickets/view_tickets.php?status=Open">View all open tickets</a>
    </div>
</div>

<div class="main-content">
    <div class="mb-4">
        <h5 class="fw-semibold mb-0"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</h5>
        <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>

    <?php if ($unassigned > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 py-2" style="border-radius:.65rem;">
        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
        <span class="small"><strong><?= $unassigned ?> ticket(s)</strong> are open and unassigned.
        <a href="<?= BASE_URL ?>/tickets/assign_ticket.php" class="fw-600">Assign now →</a></span>
    </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-hdd-rack"></i></div>
                <div><div class="stat-num"><?= $totalAssets ?></div><div class="stat-label">Total Assets</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle"></i></div>
                <div><div class="stat-num"><?= $activeAssets ?></div><div class="stat-label">Active Assets</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="bi bi-ticket-detailed"></i></div>
                <div><div class="stat-num"><?= $openTickets ?></div><div class="stat-label">Open Tickets</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-people"></i></div>
                <div><div class="stat-num"><?= $totalUsers ?></div><div class="stat-label">System Users</div></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Recent Tickets</span>
                    <a href="<?= BASE_URL ?>/tickets/view_tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Ticket #</th><th>Title</th><th>Reporter</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php if (empty($recentTickets)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No tickets yet</td></tr>
                            <?php else: foreach ($recentTickets as $t):
                                $pc = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high','Critical'=>'badge-critical'];
                                $sc = ['Open'=>'badge-open','In Progress'=>'badge-inprogress','Resolved'=>'badge-resolved','Closed'=>'badge-closed'];
                            ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/tickets/ticket_details.php?id=<?= $t['id'] ?>" class="mono text-primary"><?= htmlspecialchars($t['ticket_no']) ?></a></td>
                                <td><?= htmlspecialchars($t['title']) ?></td>
                                <td><?= htmlspecialchars($t['reporter']) ?></td>
                                <td><span class="badge <?= $pc[$t['priority']]??'bg-secondary' ?>"><?= $t['priority'] ?></span></td>
                                <td><span class="badge <?= $sc[$t['status']]??'bg-secondary' ?>"><?= $t['status'] ?></span></td>
                                <td><?= date('d M', strtotime($t['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">Ticket Summary</div>
                <div class="card-body">
                    <?php
                    $colors = ['Open'=>'#3b82f6','In Progress'=>'#f59e0b','Resolved'=>'#10b981','Closed'=>'#94a3b8'];
                    foreach ($statusMap as $label => $count):
                        $pct = $totalTickets > 0 ? round(($count/$totalTickets)*100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-500"><?= $label ?></small>
                            <small class="text-muted"><?= $count ?> (<?= $pct ?>%)</small>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $colors[$label] ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between"><span class="small text-muted">Total Tickets</span><strong><?= $totalTickets ?></strong></div>
                    <div class="d-flex justify-content-between mt-1"><span class="small text-muted">Resolved Today</span><strong class="text-success"><?= $resolvedToday ?></strong></div>
                    <div class="mt-4 d-grid gap-2">
                        <a href="<?= BASE_URL ?>/tickets/create_ticket.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>New Ticket</a>
                        <a href="<?= BASE_URL ?>/reports/reports.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart-line me-1"></i>View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ══════════════════════════════════════════════════════════
//  TECHNICIAN DASHBOARD
// ══════════════════════════════════════════════════════════
elseif ($role === 'technician'):

$myAssigned   = $db->query("SELECT COUNT(*) FROM assignments a JOIN tickets t ON t.id=a.ticket_id WHERE a.technician_id=$uid AND t.status='In Progress'")->fetch_row()[0];
$myResolved   = $db->query("SELECT COUNT(*) FROM tickets t JOIN assignments a ON a.ticket_id=t.id WHERE a.technician_id=$uid AND t.status='Resolved'")->fetch_row()[0];
$myTotal      = $db->query("SELECT COUNT(*) FROM assignments WHERE technician_id=$uid")->fetch_row()[0];
$openAll      = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetch_row()[0];

$myTickets = $db->query(
    "SELECT t.id, t.ticket_no, t.title, t.priority, t.status, t.category, t.created_at,
            u.full_name AS reporter
     FROM tickets t
     JOIN assignments a ON a.ticket_id=t.id
     LEFT JOIN users u ON t.created_by=u.id
     WHERE a.technician_id=$uid AND t.status NOT IN ('Resolved','Closed')
     ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.created_at ASC
     LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</span>
    <div class="user-badge" style="margin-left:auto;display:flex;align-items:center;gap:.6rem;">
        <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <small>Technician</small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="mb-4">
        <h5 class="fw-semibold mb-0"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</h5>
        <small class="text-muted"><?= date('l, d F Y') ?> &mdash; Here are your active assignments</small>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="bi bi-wrench-adjustable"></i></div>
                <div><div class="stat-num"><?= $myAssigned ?></div><div class="stat-label">My Active Tickets</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-patch-check"></i></div>
                <div><div class="stat-num"><?= $myResolved ?></div><div class="stat-label">Resolved by Me</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-list-check"></i></div>
                <div><div class="stat-num"><?= $myTotal ?></div><div class="stat-label">Total Assigned to Me</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-exclamation-circle"></i></div>
                <div><div class="stat-num"><?= $openAll ?></div><div class="stat-label">All Open Tickets</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>My Active Assignments</span>
            <a href="<?= BASE_URL ?>/tickets/view_tickets.php" class="btn btn-sm btn-outline-primary">View All Tickets</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Ticket #</th><th>Title</th><th>Category</th><th>Reporter</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($myTickets)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-check-circle text-success me-2"></i>No active assignments right now</td></tr>
                <?php else: foreach ($myTickets as $t):
                    $pc = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high','Critical'=>'badge-critical'];
                    $sc = ['Open'=>'badge-open','In Progress'=>'badge-inprogress','Resolved'=>'badge-resolved'];
                ?>
                <tr>
                    <td><span class="mono text-primary"><?= htmlspecialchars($t['ticket_no']) ?></span></td>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td><?= htmlspecialchars($t['category']) ?></td>
                    <td><?= htmlspecialchars($t['reporter']) ?></td>
                    <td><span class="badge <?= $pc[$t['priority']]??'' ?>"><?= $t['priority'] ?></span></td>
                    <td><span class="badge <?= $sc[$t['status']]??'' ?>"><?= $t['status'] ?></span></td>
                    <td><a href="<?= BASE_URL ?>/tickets/ticket_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// ══════════════════════════════════════════════════════════
//  STAFF DASHBOARD
// ══════════════════════════════════════════════════════════
else:

$myOpen       = $db->query("SELECT COUNT(*) FROM tickets WHERE created_by=$uid AND status='Open'")->fetch_row()[0];
$myInProgress = $db->query("SELECT COUNT(*) FROM tickets WHERE created_by=$uid AND status='In Progress'")->fetch_row()[0];
$myResolved   = $db->query("SELECT COUNT(*) FROM tickets WHERE created_by=$uid AND status='Resolved'")->fetch_row()[0];
$myTotal      = $db->query("SELECT COUNT(*) FROM tickets WHERE created_by=$uid")->fetch_row()[0];

$myTickets = $db->query(
    "SELECT t.id, t.ticket_no, t.title, t.priority, t.status, t.created_at,
            u2.full_name AS assigned_to
     FROM tickets t
     LEFT JOIN assignments a ON a.ticket_id=t.id
     LEFT JOIN users u2 ON a.technician_id=u2.id
     WHERE t.created_by=$uid
     ORDER BY t.created_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</span>
    <div class="user-badge" style="margin-left:auto;display:flex;align-items:center;gap:.6rem;">
        <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <small>Staff</small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="mb-4">
        <h5 class="fw-semibold mb-0"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</h5>
        <small class="text-muted"><?= date('l, d F Y') ?> &mdash; Track your ICT support requests below</small>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-ticket-detailed"></i></div>
                <div><div class="stat-num"><?= $myTotal ?></div><div class="stat-label">My Total Tickets</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="stat-num"><?= $myOpen ?></div><div class="stat-label">Open</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="bi bi-arrow-repeat"></i></div>
                <div><div class="stat-num"><?= $myInProgress ?></div><div class="stat-label">In Progress</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle"></i></div>
                <div><div class="stat-num"><?= $myResolved ?></div><div class="stat-label">Resolved</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>My Recent Tickets</span>
            <a href="<?= BASE_URL ?>/tickets/create_ticket.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle me-1"></i>New Ticket</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Ticket #</th><th>Title</th><th>Priority</th><th>Assigned To</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($myTickets)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">You haven't submitted any tickets yet. <a href="<?= BASE_URL ?>/tickets/create_ticket.php">Create one now</a></td></tr>
                <?php else: foreach ($myTickets as $t):
                    $pc = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high','Critical'=>'badge-critical'];
                    $sc = ['Open'=>'badge-open','In Progress'=>'badge-inprogress','Resolved'=>'badge-resolved','Closed'=>'badge-closed'];
                ?>
                <tr>
                    <td><span class="mono text-primary"><?= htmlspecialchars($t['ticket_no']) ?></span></td>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td><span class="badge <?= $pc[$t['priority']]??'' ?>"><?= $t['priority'] ?></span></td>
                    <td><?= $t['assigned_to'] ? htmlspecialchars($t['assigned_to']) : '<span class="text-muted small">Pending</span>' ?></td>
                    <td><span class="badge <?= $sc[$t['status']]??'' ?>"><?= $t['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                    <td><a href="<?= BASE_URL ?>/tickets/ticket_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- ── Notification JS (admin only) ── -->
<?php if ($role === 'admin'): ?>
<script>
(function(){
    const btn      = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    const badge    = document.getElementById('notifBadge');
    const list     = document.getElementById('notifList');
    const BASE     = '<?= BASE_URL ?>';

    function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function pColor(p){ return {Critical:'#ef4444',High:'#f97316',Medium:'#f59e0b',Low:'#10b981'}[p]||'#64748b'; }
    function dotClass(p){ return {Critical:'critical',High:'high',Medium:'medium',Low:'low'}[p]||'medium'; }

    function load(){
        fetch(BASE+'/api/notifications.php')
        .then(r=>r.json())
        .then(data=>{
            // update badge
            if(data.count>0){
                badge.textContent = data.count>9?'9+':data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
            // update list
            if(!data.tickets.length){
                list.innerHTML='<div class="notif-empty"><i class="bi bi-check-circle" style="font-size:1.5rem;display:block;margin-bottom:.5rem;color:#10b981;"></i>All tickets assigned!</div>';
                return;
            }
            list.innerHTML = data.tickets.map(t=>`
                <a class="notif-item" href="${BASE}/tickets/ticket_details.php?id=${t.id}">
                    <span class="notif-dot ${dotClass(t.priority)}"></span>
                    <div style="flex:1;min-width:0;">
                        <div class="notif-title">${esc(t.title)}</div>
                        <div class="notif-meta">
                            <span class="mono">${esc(t.ticket_no)}</span> &middot; ${esc(t.category)} &middot; ${esc(t.dept)}
                            &middot; <strong style="color:${pColor(t.priority)}">${t.priority}</strong>
                        </div>
                        <div class="notif-meta">By ${esc(t.reporter)} &middot; ${esc(t.ago)}</div>
                    </div>
                    <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:.7rem;flex-shrink:0;margin-top:3px;"></i>
                </a>`).join('');
        }).catch(()=>{});
    }

    btn.addEventListener('click', function(e){
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if(dropdown.classList.contains('open')) load();
    });

    document.addEventListener('click', function(e){
        if(!dropdown.contains(e.target) && e.target!==btn)
            dropdown.classList.remove('open');
    });

    load();
    setInterval(load, 60000);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>