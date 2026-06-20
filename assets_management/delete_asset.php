<?php
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$db = getDB();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    // Check for active tickets linked to this asset
    $inUse = $db->query("SELECT COUNT(*) FROM tickets WHERE asset_id=$id AND status NOT IN ('Resolved','Closed')")->fetch_row()[0];
    if ($inUse > 0) {
        setFlash('error','Cannot delete — asset has open/in-progress tickets linked to it.');
    } else {
        $db->query("DELETE FROM assets WHERE id=$id");
        setFlash('success','Asset deleted.');
    }
}

header('Location: view_assets.php'); exit;