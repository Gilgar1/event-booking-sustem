<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_logged_in()) {
    redirect('dashboard.php');
}

$booking_id = intval($_GET['id']);

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, e.name as event_name, e.date, e.time, e.venue, e.description, e.image
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error_message'] = 'Booking not found!';
    redirect('dashboard.php');
}

// Get attendees
$attendees_stmt = $pdo->prepare("SELECT * FROM attendees WHERE booking_id = ?");
$attendees_stmt->execute([$booking_id]);
$attendees = $attendees_stmt->fetchAll();

// Generate QR Code (requires phpqrcode library)
$qr_data = "EVENT-BOOKING:" . $booking['booking_reference'];
$qr_file = "../assets/qrcodes/" . $booking['booking_reference'] . ".png";
if (!file_exists($qr_file)) {
    require_once 'includes/phpqrcode/qrlib.php';
    QRcode::png($qr_data, $qr_file, QR_ECLEVEL_L, 10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details | Event Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ticket {
            border: 2px dashed #ccc;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Booking Details</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
        </div>
        
        <div class="row">
            <!-- Booking Info -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>Event Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="../assets/images/<?= htmlspecialchars($booking['image']) ?>" 
                                 class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                        <h4><?= htmlspecialchars($booking['event_name']) ?></h4>
                        <p><?= nl2br(htmlspecialchars($booking['description'])) ?></p>
                        <hr>
                        <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($booking['date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['time'])) ?></p>
                        <p><strong>Venue:</strong> <?= htmlspecialchars($booking['venue']) ?></p>
                        <p><strong>Reference:</strong> <code><?= $booking['booking_reference'] ?></code></p>
                    </div>
                </div>
            </div>
            
            <!-- Tickets -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Your Tickets (<?= $booking['tickets'] ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($attendees as $attendee): ?>
                            <div class="ticket p-4 mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5><?= htmlspecialchars($attendee['full_name']) ?></h5>
                                        <p class="mb-1"><?= htmlspecialchars($attendee['email']) ?></p>
                                        <?php if (!empty($attendee['phone'])): ?>
                                            <p class="mb-1"><?= htmlspecialchars($attendee['phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center">
                                        <img src="<?= $qr_file ?>" width="80" alt="QR Code">
                                        <small class="d-block mt-1">Ticket #<?= $attendee['id'] ?></small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                                        Print Ticket
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>