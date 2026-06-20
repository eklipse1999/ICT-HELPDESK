<?php
// ============================================================
//  Session & Auth Helpers
// ============================================================

date_default_timezone_set('Africa/Accra');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Redirect if not logged in
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Redirect if not a specific role
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? 0,
        'name'      => $_SESSION['full_name'] ?? '',
        'username'  => $_SESSION['username']  ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'dept_id'   => $_SESSION['dept_id']   ?? null,
    ];
}

function isAdmin(): bool     { return ($_SESSION['role'] ?? '') === 'admin'; }
function isTechnician(): bool{ return ($_SESSION['role'] ?? '') === 'technician'; }
function isStaff(): bool     { return ($_SESSION['role'] ?? '') === 'staff'; }

// Generate a unique ticket number  e.g. TKT-20250001
function generateTicketNo(): string {
    $db   = getDB();
    $year = date('Y');
    $res  = $db->query("SELECT COUNT(*) AS cnt FROM tickets WHERE YEAR(created_at) = $year");
    $row  = $res->fetch_assoc();
    $seq  = str_pad((int)$row['cnt'] + 1, 4, '0', STR_PAD_LEFT);
    return "TKT-{$year}{$seq}";
}

// Flash message helpers
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/ICT-HELPDESK');