<?php
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db     = getDB();
$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'toggle' && $id && $id !== (int)$_SESSION['user_id']) {
    $db->query("UPDATE users SET is_active = NOT is_active WHERE id=$id");
    setFlash('success','User status updated.');
}

header('Location: users.php'); exit;