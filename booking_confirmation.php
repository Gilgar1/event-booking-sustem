<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_logged_in()) {
    redirect('events.php');
}

$booking_id = intval($_GET['id']);

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, e.name as event_name, e.date, e.time, e.venue
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error_message'] = 'Booking not found!';
    redirect('events.php');
}

// Get attendees
$attendees_stmt = $pdo->prepare("SELECT * FROM attendees WHERE booking_id = ?");
$attendees_stmt->execute([$booking_id]);
$attendees = $attendees_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | Event Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="text-center mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#28a745" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <h1 class="mt-3">Booking Confirmed!</h1>
            <p class="lead">Your booking reference: <strong>#<?= $booking_id ?></strong></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Event:</strong> <?= htmlspecialchars($booking['event_name']) ?></p>
                                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($booking['date'])) ?></p>
                                <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['time'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Venue:</strong> <?= htmlspecialchars($booking['venue']) ?></p>
                                <p><strong>Tickets:</strong> <?= $booking['tickets'] ?></p>
                                <p><strong>Total Paid:</strong> $<?= number_format($booking['total_price'], 2) ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Attendees</h5>
                        <ul class="list-group">
                            <?php foreach ($attendees as $attendee): ?>
                                <li class="list-group-item">
                                    <?= htmlspecialchars($attendee['full_name']) ?> 
                                    (<?= htmlspecialchars($attendee['email']) ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="dashboard.php" class="btn btn-primary">View All Bookings</a>
                        <button onclick="window.print()" class="btn btn-outline-secondary ms-2">Print Ticket</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>