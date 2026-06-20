<?php
$pageTitle  = 'Departments';
$activePage = 'departments';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db = getDB();

// ── Handle actions ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name !== '') {
            $s = $db->prepare("INSERT INTO departments (name,description) VALUES (?,?)");
            $s->bind_param('ss',$name,$desc);
            $s->execute();
            setFlash('success','Department added successfully.');
        } else {
            setFlash('error','Department name is required.');
        }
    }

    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($id && $name !== '') {
            $s = $db->prepare("UPDATE departments SET name=?,description=? WHERE id=?");
            $s->bind_param('ssi',$name,$desc,$id);
            $s->execute();
            setFlash('success','Department updated.');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Check if dept has users or assets
        $used = $db->query("SELECT COUNT(*) FROM users WHERE department_id=$id")->fetch_row()[0]
               + $db->query("SELECT COUNT(*) FROM assets WHERE department_id=$id")->fetch_row()[0];
        if ($used > 0) {
            setFlash('error','Cannot delete — department has associated users or assets.');
        } else {
            $db->query("DELETE FROM departments WHERE id=$id");
            setFlash('success','Department deleted.');
        }
    }

    header('Location: departments.php'); exit;
}

$departments = $db->query(
    "SELECT d.*, COUNT(DISTINCT u.id) AS user_count, COUNT(DISTINCT a.id) AS asset_count
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id
     LEFT JOIN assets a ON a.department_id=d.id
     GROUP BY d.id ORDER BY d.name"
)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <span class="page-title"><i class="bi bi-building me-2 text-primary"></i>Departments</span>
    <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
        <div class="user-info">
            <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
            <small><?= ucfirst($user['role']) ?></small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-semibold mb-0">Departments</h5>
            <small class="text-muted"><?= count($departments) ?> departments registered</small>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-1"></i>Add Department
        </button>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Assets</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($departments)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No departments yet</td></tr>
                <?php else: ?>
                <?php foreach ($departments as $i => $d): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td class="fw-500"><?= htmlspecialchars($d['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($d['description'] ?? '—') ?></td>
                    <td><span class="badge bg-light text-dark"><?= $d['user_count'] ?></span></td>
                    <td><span class="badge bg-light text-dark"><?= $d['asset_count'] ?></span></td>
                    <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="editDept(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>', '<?= addslashes($d['description']??'') ?>')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">Add Department</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Department</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">Edit Department</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
function editDept(id, name, desc) {
    document.getElementById('edit_id').value   = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_desc').value = desc;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>