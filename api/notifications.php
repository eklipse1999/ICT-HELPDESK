<?php
// ============================================================
//  Notifications API
//  Returns unassigned open tickets as JSON for the bell dropdown
// ============================================================
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

// Must be logged in and be admin or technician
if (!isLoggedIn() || isStaff()) {
    echo json_encode(['count' => 0, 'tickets' => []]);
    exit;
}

$db = getDB();

// Tickets that are Open and have never been assigned
$result = $db->query(
    "SELECT t.id, t.ticket_no, t.title, t.category, t.priority, t.created_at,
            u.full_name AS reporter, d.name AS dept
     FROM tickets t
     LEFT JOIN users u ON t.created_by = u.id
     LEFT JOIN departments d ON t.department_id = d.id
     LEFT JOIN assignments a ON a.ticket_id = t.id
     WHERE t.status = 'Open'
       AND a.id IS NULL
     ORDER BY
       FIELD(t.priority, 'Critical','High','Medium','Low'),
       t.created_at ASC
     LIMIT 15"
);

$tickets = [];
while ($row = $result->fetch_assoc()) {
    // Human-readable time ago
    $created  = strtotime($row['created_at']);
    $diff     = time() - $created;
    if ($diff < 60)          $ago = 'Just now';
    elseif ($diff < 3600)    $ago = floor($diff/60)   . 'm ago';
    elseif ($diff < 86400)   $ago = floor($diff/3600)  . 'h ago';
    else                     $ago = floor($diff/86400) . 'd ago';

    $tickets[] = [
        'id'       => $row['id'],
        'ticket_no'=> $row['ticket_no'],
        'title'    => $row['title'],
        'category' => $row['category'],
        'priority' => $row['priority'],
        'reporter' => $row['reporter'],
        'dept'     => $row['dept'] ?? 'N/A',
        'ago'      => $ago,
    ];
}

echo json_encode([
    'count'   => count($tickets),
    'tickets' => $tickets,
]);