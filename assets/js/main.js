/**
 * ICT Help Desk & Asset Management System
 * Techiman Metropolitan Assembly
 * assets/js/main.js
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile sidebar toggle ──────────────────────────────
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar  = document.querySelector('.sidebar');

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Auto-dismiss flash alerts ──────────────────────────
    document.querySelectorAll('.flash-wrap .alert').forEach(el => {
        setTimeout(() => {
            const instance = bootstrap.Alert.getOrCreateInstance(el);
            if (instance) instance.close();
        }, 4500);
    });

    // ── Confirm delete buttons ─────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Active nav link highlight (fallback) ───────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ── Ticket form: auto-set department from session ──────
    // (handled server-side, this is a fallback UI hint)
    const deptSelect = document.querySelector('select[name="department_id"]');
    if (deptSelect && deptSelect.value === '') {
        // Leave as-is; server pre-selects based on session dept_id
    }

    // ── Table row clickable (rows with data-href) ──────────
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            window.location.href = row.dataset.href;
        });
    });

    // ── Tooltip init ───────────────────────────────────────
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

    // ── Popover init ───────────────────────────────────────
    const popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverEls.forEach(el => new bootstrap.Popover(el));

    // ── Print button ───────────────────────────────────────
    document.querySelectorAll('[data-action="print"]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    // ── Asset tag auto-uppercase ───────────────────────────
    const assetTagInput = document.querySelector('input[name="asset_tag"]');
    if (assetTagInput) {
        assetTagInput.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    }

    // ── Password strength indicator ────────────────────────
    const pwInput = document.querySelector('input[name="password"]');
    if (pwInput) {
        const indicator = document.createElement('div');
        indicator.className = 'mt-1';
        indicator.style.height = '4px';
        indicator.style.borderRadius = '2px';
        indicator.style.transition = 'width .3s, background .3s';
        indicator.style.width = '0%';
        pwInput.parentElement.after(indicator);

        pwInput.addEventListener('input', function () {
            const val = this.value;
            let strength = 0;
            if (val.length >= 6)  strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#10b981'];
            const widths  = ['20%', '40%', '60%', '80%', '100%'];
            indicator.style.background = colors[strength - 1] || '#e2e8f0';
            indicator.style.width      = widths[strength - 1]  || '0%';
        });
    }

});