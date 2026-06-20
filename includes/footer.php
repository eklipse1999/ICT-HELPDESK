<?php // includes/footer.php ?>
</div><!-- /.main-content -->

<!-- Flash toast -->
<?php if (!empty($flash)): ?>
<div class="flash-wrap">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible shadow-sm fade show" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Auto-dismiss flash after 4s
document.querySelectorAll('.flash-wrap .alert').forEach(el => {
    setTimeout(() => { const a = bootstrap.Alert.getOrCreateInstance(el); a?.close(); }, 4000);
});
</script>
</body>
</html>