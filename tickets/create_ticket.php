<?php
$pageTitle  = 'Create Ticket';
$activePage = 'create_ticket';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$db          = getDB();
$departments = $db->query("SELECT id,name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$assets      = $db->query("SELECT id,asset_tag,name FROM assets WHERE status='Active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? '';
    $priority    = $_POST['priority'] ?? 'Medium';
    $dept_id     = $_POST['department_id'] ? (int)$_POST['department_id'] : null;
    $asset_id    = $_POST['asset_id']      ? (int)$_POST['asset_id']      : null;

    if (!$title)       $errors[] = 'Title is required.';
    if (!$description) $errors[] = 'Description is required.';
    if (!$category)    $errors[] = 'Category is required.';

    if (empty($errors)) {
        $ticket_no = generateTicketNo();
        $user_id   = (int)$_SESSION['user_id'];
        // types: ticket_no(s) title(s) description(s) category(s) priority(s) created_by(i) department_id(i) asset_id(i)
        $s = $db->prepare(
            "INSERT INTO tickets (ticket_no,title,description,category,priority,created_by,department_id,asset_id)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $s->bind_param('sssssiii', $ticket_no, $title, $description, $category, $priority, $user_id, $dept_id, $asset_id);
        $s->execute();
        setFlash('success', "Ticket {$ticket_no} created successfully.");
        header('Location: view_tickets.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-plus-circle me-2 text-primary"></i>New Ticket</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info"><div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div><small><?= ucfirst($user['role']) ?></small></div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="view_tickets.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-semibold mb-0">Create Support Ticket</h5>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger small">
        <?php foreach ($errors as $e): ?>
        <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:700px;">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Issue Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               placeholder="Brief description of the issue"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">— Select Category —</option>
                            <?php foreach (['Internet','Printer','Software','Hardware','Network','Other'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($_POST['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Describe the issue in detail…" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($_POST['department_id'] ?? $_SESSION['dept_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Affected Asset <small class="text-muted">(optional)</small></label>
                        <select name="asset_id" class="form-select">
                            <option value="">— Select Asset —</option>
                            <?php foreach ($assets as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($_POST['asset_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['asset_tag'] . ' — ' . $a['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Ticket
                        </button>
                        <a href="view_tickets.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>