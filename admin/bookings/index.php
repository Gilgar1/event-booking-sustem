<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!is_admin()) {
    redirect('../../login.php');
}

// Filter handling
$where = [];
$params = [];

if (!empty($_GET['event_id'])) {
    $where[] = "b.event_id = ?";
    $params[] = (int)$_GET['event_id'];
}

if (!empty($_GET['user_email'])) {
    $where[] = "u.email LIKE ?";
    $params[] = '%' . $_GET['user_email'] . '%';
}

$sql = "
    SELECT b.*, e.name as event_name, u.email as user_email
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "") . "
ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!-- Display bookings with filter form -->