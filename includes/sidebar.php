<?php
// includes/sidebar.php
$activePage = $activePage ?? '';
$user       = currentUser();

function navLink(string $href, string $icon, string $label, string $page, string $activePage): void {
    $active = ($page === $activePage) ? ' active' : '';
    echo "<a href=\"{$href}\" class=\"nav-link{$active}\"><i class=\"bi bi-{$icon}\"></i>{$label}</a>\n";
}
?>

<nav class="sidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <div style="width:38px;height:38px;border-radius:50%;background:#fff;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="Logo"
                     style="width:100%;height:100%;object-fit:contain;padding:3px;">
            </div>
            <div>
                <div class="org-name"><?= SITE_ORG ?></div>
                <div class="system-name">ICT Help Desk</div>
            </div>
        </div>
    </div>

    <div class="mt-2">

        <!-- ── MAIN (all roles) ── -->
        <div class="nav-section-label">Main</div>
        <?php navLink(BASE_URL.'/admin/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $activePage); ?>

        <!-- ── TICKETS (all roles) ── -->
        <div class="nav-section-label">Tickets</div>
        <?php navLink(BASE_URL.'/tickets/view_tickets.php',  'ticket-detailed', 'All Tickets',  'tickets',       $activePage); ?>
        <?php navLink(BASE_URL.'/tickets/create_ticket.php', 'plus-circle',     'New Ticket',   'create_ticket', $activePage); ?>

        <?php if (isAdmin()): ?>
        <?php navLink(BASE_URL.'/tickets/assign_ticket.php', 'person-check', 'Assign Tickets', 'assign_ticket', $activePage); ?>
        <?php endif; ?>

        <!-- ── ASSETS (admin & technician only) ── -->
        <?php if (isAdmin() || isTechnician()): ?>
        <div class="nav-section-label">Assets</div>
        <?php navLink(BASE_URL.'/assets_management/view_assets.php', 'hdd-rack', 'All Assets', 'assets', $activePage); ?>
        <?php if (isAdmin()): ?>
        <?php navLink(BASE_URL.'/assets_management/add_asset.php', 'plus-circle', 'Add Asset', 'add_asset', $activePage); ?>
        <?php endif; ?>

        <!-- ── MAINTENANCE (admin & technician only) ── -->
        <div class="nav-section-label">Maintenance</div>
        <?php navLink(BASE_URL.'/maintenance/maintenance_log.php', 'tools',        'Log Maintenance', 'maintenance_log',     $activePage); ?>
        <?php navLink(BASE_URL.'/maintenance/history.php',         'clock-history','History',         'maintenance_history', $activePage); ?>
        <?php endif; ?>

        <!-- ── ADMIN ONLY ── -->
        <?php if (isAdmin()): ?>
        <div class="nav-section-label">Administration</div>
        <?php navLink(BASE_URL.'/admin/users.php',       'people',         'Users',       'users',       $activePage); ?>
        <?php navLink(BASE_URL.'/admin/departments.php', 'building',       'Departments', 'departments', $activePage); ?>
        <?php navLink(BASE_URL.'/reports/reports.php',   'bar-chart-line', 'Reports',     'reports',     $activePage); ?>
        <?php endif; ?>

    </div>

    <!-- Bottom: user + logout -->
    <div class="mt-auto p-3 border-top" style="border-color:rgba(255,255,255,.07)!important;">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:32px;height:32px;border-radius:50%;background:#1e3a8a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;">
                <?= strtoupper(substr($user['name'],0,2)) ?>
            </div>
            <div>
                <div style="font-size:.8rem;color:#fff;font-weight:500;"><?= htmlspecialchars($user['name']) ?></div>
                <div style="font-size:.68rem;color:#64748b;"><?= ucfirst($user['role']) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/profile.php" class="nav-link" style="padding:.4rem 0;">
            <i class="bi bi-person-circle"></i> My Profile
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger" style="padding:.4rem 0;">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</nav>