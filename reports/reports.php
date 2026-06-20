<?php
$pageTitle  = 'Reports';
$activePage = 'reports';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db = getDB();

// ── Summary stats ──────────────────────────────────────────
$totalAssets     = $db->query("SELECT COUNT(*) FROM assets")->fetch_row()[0];
$activeAssets    = $db->query("SELECT COUNT(*) FROM assets WHERE status='Active'")->fetch_row()[0];
$maintAssets     = $db->query("SELECT COUNT(*) FROM assets WHERE status='Under Maintenance'")->fetch_row()[0];
$decommAssets    = $db->query("SELECT COUNT(*) FROM assets WHERE status='Decommissioned'")->fetch_row()[0];

$totalTickets    = $db->query("SELECT COUNT(*) FROM tickets")->fetch_row()[0];
$openTickets     = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetch_row()[0];
$inProgressT     = $db->query("SELECT COUNT(*) FROM tickets WHERE status='In Progress'")->fetch_row()[0];
$resolvedT       = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Resolved'")->fetch_row()[0];
$closedT         = $db->query("SELECT COUNT(*) FROM tickets WHERE status='Closed'")->fetch_row()[0];

// ── Tickets by department ──────────────────────────────────
$deptTickets = $db->query(
    "SELECT d.name, COUNT(t.id) AS cnt
     FROM departments d
     LEFT JOIN tickets t ON t.department_id=d.id
     GROUP BY d.id ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// ── Tickets by category ────────────────────────────────────
$catTickets = $db->query(
    "SELECT category, COUNT(*) AS cnt FROM tickets GROUP BY category ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// ── Monthly ticket trend (last 6 months) ──────────────────
$monthlyTrend = $db->query(
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS month,
            DATE_FORMAT(created_at,'%Y-%m') AS ym,
            COUNT(*) AS cnt
     FROM tickets
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym"
)->fetch_all(MYSQLI_ASSOC);

// ── Asset categories ───────────────────────────────────────
$assetCats = $db->query(
    "SELECT category, COUNT(*) AS cnt FROM assets GROUP BY category ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

// ── Maintenance cost by month ──────────────────────────────
$maintCost = $db->query(
    "SELECT DATE_FORMAT(maintenance_date,'%b %Y') AS month,
            DATE_FORMAT(maintenance_date,'%Y-%m') AS ym,
            SUM(cost) AS total
     FROM maintenance_logs
     WHERE maintenance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym"
)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Reports & Analytics</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-semibold mb-0">Reports & Analytics</h5>
            <small class="text-muted">As of <?= date('d F Y') ?></small>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print();" title="Print this report">
            <i class="bi bi-printer me-1"></i>Print Report
        </button>
    </div>

    <!-- Asset & Ticket summaries -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card stat-card"><div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-hdd-rack"></i></div><div><div class="stat-num"><?= $totalAssets ?></div><div class="stat-label">Total Assets</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle"></i></div><div><div class="stat-num"><?= $activeAssets ?></div><div class="stat-label">Active Assets</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="bi bi-ticket-detailed"></i></div><div><div class="stat-num"><?= $totalTickets ?></div><div class="stat-label">Total Tickets</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-patch-check"></i></div><div><div class="stat-num"><?= $resolvedT ?></div><div class="stat-label">Resolved Tickets</div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Ticket status donut -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">Tickets by Status</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusChart" width="220" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Tickets by category bar -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">Tickets by Category</div>
                <div class="card-body">
                    <canvas id="catChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Monthly trend -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Monthly Ticket Trend (Last 6 Months)</div>
                <div class="card-body">
                    <canvas id="trendChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <!-- Dept breakdown table -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Tickets by Department</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead><tr><th>Department</th><th class="text-end">Tickets</th></tr></thead>
                        <tbody>
                        <?php foreach ($deptTickets as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['name']) ?></td>
                            <td class="text-end fw-600"><?= $d['cnt'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset category breakdown -->
    <div class="card mb-4">
        <div class="card-header">Asset Inventory by Category</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 small">
                <thead><tr><th>Category</th><th>Count</th><th>% of Total</th></tr></thead>
                <tbody>
                <?php foreach ($assetCats as $ac): $pct = $totalAssets>0?round(($ac['cnt']/$totalAssets)*100,1):0; ?>
                <tr>
                    <td><?= htmlspecialchars($ac['category']) ?></td>
                    <td><?= $ac['cnt'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar" style="width:<?= $pct ?>%;background:#1a56db;"></div>
                            </div>
                            <small><?= $pct ?>%</small>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const palette = ['#3b82f6','#f59e0b','#10b981','#94a3b8','#ef4444','#8b5cf6'];

// Status donut
new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data:{
        labels:['Open','In Progress','Resolved','Closed'],
        datasets:[{data:[<?= $openTickets ?>,<?= $inProgressT ?>,<?= $resolvedT ?>,<?= $closedT ?>],
                   backgroundColor:['#3b82f6','#f59e0b','#10b981','#94a3b8'],borderWidth:0}]
    },
    options:{plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}},cutout:'65%'}
});

// Category bar
new Chart(document.getElementById('catChart'), {
    type:'bar',
    data:{
        labels:[<?= implode(',',array_map(fn($c)=>'"'.addslashes($c['category']).'"',$catTickets)) ?>],
        datasets:[{label:'Tickets',
                   data:[<?= implode(',',array_column($catTickets,'cnt')) ?>],
                   backgroundColor:'#1a56db',borderRadius:4}]
    },
    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
});

// Monthly trend line
new Chart(document.getElementById('trendChart'), {
    type:'line',
    data:{
        labels:[<?= implode(',',array_map(fn($m)=>'"'.addslashes($m['month']).'"',$monthlyTrend)) ?>],
        datasets:[{label:'Tickets Created',
                   data:[<?= implode(',',array_column($monthlyTrend,'cnt')) ?>],
                   borderColor:'#1a56db',backgroundColor:'rgba(26,86,219,.1)',fill:true,tension:.4,pointRadius:4}]
    },
    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>