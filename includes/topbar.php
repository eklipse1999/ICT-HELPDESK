<?php
// includes/topbar.php
// Usage: include AFTER setting $pageTitle and $pageIcon (bootstrap icon name)
// e.g. $pageIcon = 'speedometer2'; $pageTitle = 'Dashboard';
// Requires session.php already loaded (via header.php).

$pageIcon = $pageIcon ?? 'circle';
$user     = currentUser();
?>

<div class="topbar">
    <span class="page-title">
        <i class="bi bi-<?= htmlspecialchars($pageIcon) ?> me-2 text-primary"></i>
        <?= htmlspecialchars($pageTitle ?? 'Page') ?>
    </span>

    <div class="user-badge">

        <!-- ── Notification Bell (admin & technician only) ── -->
        <?php if (isAdmin() || isTechnician()): ?>
        <button class="notif-btn" id="notifBtn" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notif-badge hidden" id="notifBadge"></span>
        </button>
        <?php endif; ?>

        <!-- ── User info ── -->
        <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
            <small><?= ucfirst($user['role']) ?></small>
        </div>
    </div>
</div>

<!-- ── Notification dropdown (rendered once, outside topbar flow) ── -->
<?php if (isAdmin() || isTechnician()): ?>
<div class="notif-dropdown" id="notifDropdown">
    <div class="notif-header">
        <span>🔔 Unassigned Tickets</span>
        <a href="<?= BASE_URL ?>/tickets/assign_ticket.php">Assign All →</a>
    </div>
    <div class="notif-list" id="notifList">
        <div class="notif-empty">Loading…</div>
    </div>
    <div class="notif-footer">
        <a href="<?= BASE_URL ?>/tickets/view_tickets.php?status=Open">View all open tickets</a>
    </div>
</div>

<script>
(function () {
    const btn      = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    const badge    = document.getElementById('notifBadge');
    const list     = document.getElementById('notifList');
    const BASE     = '<?= BASE_URL ?>';

    const priorityDot = p => {
        const cls = {Critical:'critical', High:'high', Medium:'medium', Low:'low'};
        return `<span class="notif-dot ${cls[p]??'medium'}"></span>`;
    };

    function renderTickets(data) {
        // Badge
        if (data.count > 0) {
            badge.textContent = data.count > 9 ? '9+' : data.count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // List
        if (data.tickets.length === 0) {
            list.innerHTML = `<div class="notif-empty"><i class="bi bi-check-circle" style="font-size:1.5rem;display:block;margin-bottom:.5rem;color:#10b981;"></i>All tickets are assigned!</div>`;
            return;
        }

        list.innerHTML = data.tickets.map(t => `
            <a class="notif-item" href="${BASE}/tickets/ticket_details.php?id=${t.id}">
                ${priorityDot(t.priority)}
                <div style="flex:1;min-width:0;">
                    <div class="notif-title">${escHtml(t.title)}</div>
                    <div class="notif-meta">
                        <span class="mono">${escHtml(t.ticket_no)}</span>
                        &middot; ${escHtml(t.category)}
                        &middot; ${escHtml(t.dept)}
                        &middot; <strong style="color:${priorityColor(t.priority)}">${t.priority}</strong>
                    </div>
                    <div class="notif-meta">By ${escHtml(t.reporter)} &middot; ${escHtml(t.ago)}</div>
                </div>
                <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:.7rem;flex-shrink:0;margin-top:3px;"></i>
            </a>
        `).join('');
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function priorityColor(p) {
        return {Critical:'#ef4444', High:'#f97316', Medium:'#f59e0b', Low:'#10b981'}[p] ?? '#64748b';
    }

    function fetchNotifications() {
        fetch(`${BASE}/api/notifications.php`)
            .then(r => r.json())
            .then(renderTickets)
            .catch(() => {});
    }

    // Toggle dropdown
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) fetchNotifications();
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('open');
        }
    });

    // Initial load + auto-refresh every 60 seconds
    fetchNotifications();
    setInterval(fetchNotifications, 60000);
})();
</script>
<?php endif; ?>