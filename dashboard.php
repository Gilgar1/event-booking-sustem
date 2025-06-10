<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Get user's bookings
$stmt = $pdo->prepare("
    SELECT b.*, e.name as event_name, e.date, e.time, e.venue, e.image
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
    ORDER BY e.date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Separate upcoming/past bookings
$upcoming = [];
$past = [];
$current_date = new DateTime();

foreach ($bookings as $booking) {
    $event_date = new DateTime($booking['date']);
    if ($event_date >= $current_date) {
        $upcoming[] = $booking;
    } else {
        $past[] = $booking;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Event Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">My Bookings</h1>
        
        <!-- Upcoming Events -->
        <div class="card mb-5">
            <div class="card-header bg-primary text-white">
                <h5>Upcoming Events</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming)): ?>
                    <div class="alert alert-info">No upcoming events booked.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Tickets</th>
                                    <th>Reference</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $booking): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/<?= htmlspecialchars($booking['image']) ?>" 
                                                 width="60" class="me-2">
                                            <?= htmlspecialchars($booking['event_name']) ?>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($booking['date'])) ?><br>
                                            <small><?= date('g:i A', strtotime($booking['time'])) ?></small>
                                        </td>
                                        <td><?= $booking['tickets'] ?></td>
                                        <td>
                                            <code><?= $booking['booking_reference'] ?></code>
                                        </td>
                                        <td>
                                            <a href="view_booking.php?id=<?= $booking['id'] ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmCancel(<?= $booking['id'] ?>)">Cancel</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Past Events -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5>Past Events</h5>
            </div>
            <div class="card-body">
                <?php if (empty($past)): ?>
                    <div class="alert alert-info">No past events found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Tickets</th>
                                    <th>Reference</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past as $booking): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/<?= htmlspecialchars($booking['image']) ?>" 
                                                 width="60" class="me-2">
                                            <?= htmlspecialchars($booking['event_name']) ?>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($booking['date'])) ?></td>
                                        <td><?= $booking['tickets'] ?></td>
                                        <td><code><?= $booking['booking_reference'] ?></code></td>
                                        <td>
                                            <a href="view_booking.php?id=<?= $booking['id'] ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Cancellation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <p class="fw-bold">Refund policy: Full refund if cancelled 7+ days before event.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="cancelBookingBtn" class="btn btn-danger">Confirm Cancel</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancel(bookingId) {
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            const cancelBtn = document.getElementById('cancelBookingBtn');
            cancelBtn.href = `cancel_booking.php?id=${bookingId}`;
            modal.show();
        }
    </script>
</body>
</html>