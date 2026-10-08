<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_logged_in()) {
    redirect('dashboard.php');
}

$booking_id = intval($_GET['id']);

// Verify booking belongs to user
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if ($booking) {
    // Soft delete (change status)
    $update_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $update_stmt->execute([$booking_id]);
    
    $_SESSION['success_message'] = 'Booking cancelled successfully!';
} else {
    $_SESSION['error_message'] = 'Booking not found or access denied!';
}

redirect('dashboard.php');