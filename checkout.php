<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    redirect('login.php');
}

$cart = get_cart();
if (empty($cart)) {
    $_SESSION['error_message'] = 'Your cart is empty!';
    redirect('events.php');
}

// Calculate total
$total = calculate_cart_total($pdo);

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Create booking record
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, event_id, tickets, total_price, payment_status)
            VALUES (?, ?, ?, ?, 'paid')
        ");
        
        foreach ($cart as $event_id => $quantity) {
            $event_stmt = $pdo->prepare("SELECT price FROM events WHERE id = ?");
            $event_stmt->execute([$event_id]);
            $event_price = $event_stmt->fetchColumn();
            
            $stmt->execute([
                $_SESSION['user_id'],
                $event_id,
                $quantity,
                $event_price * $quantity
            ]);
            
            $booking_id = $pdo->lastInsertId();
            
            // 2. Save attendee details
            for ($i = 0; $i < $quantity; $i++) {
                $attendee_stmt = $pdo->prepare("
                    INSERT INTO attendees (booking_id, full_name, email, phone)
                    VALUES (?, ?, ?, ?)
                ");
                
                $attendee_stmt->execute([
                    $booking_id,
                    sanitize_input($_POST["attendee_{$event_id}_name"][$i]),
                    sanitize_input($_POST["attendee_{$event_id}_email"][$i]),
                    sanitize_input($_POST["attendee_{$event_id}_phone"][$i] ?? '')
                ]);
            }
        }
        
        $pdo->commit();
        clear_cart();
        $_SESSION['success_message'] = 'Booking confirmed!';
        redirect('booking_confirmation.php?id=' . $booking_id);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Checkout failed: ' . $e->getMessage();
    }
}

// Get cart items with details
$event_ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($event_ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM events WHERE id IN ($placeholders)");
$stmt->execute($event_ids);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Event Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-md-8">
                <h2 class="mb-4">Checkout</h2>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Payment Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" placeholder="4242 4242 4242 4242" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiration Date</label>
                                    <input type="text" class="form-control" placeholder="MM/YY" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" placeholder="123" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendee Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5>Attendee Information</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($events as $event): ?>
                                <h6 class="mt-3"><?= htmlspecialchars($event['name']) ?> (<?= $cart[$event['id']] ?> ticket(s))</h6>
                                
                                <?php for ($i = 0; $i < $cart[$event['id']]; $i++): ?>
                                    <div class="attendee-form border p-3 mb-3">
                                        <h6>Attendee <?= $i + 1 ?></h6>
                                        <div class="mb-3">
                                            <label class="form-label">Full Name*</label>
                                            <input type="text" name="attendee_<?= $event['id'] ?>_name[]" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email*</label>
                                            <input type="email" name="attendee_<?= $event['id'] ?>_email[]" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" name="attendee_<?= $event['id'] ?>_phone[]" class="form-control">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100">Complete Booking</button>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($events as $event): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= htmlspecialchars($event['name']) ?> × <?= $cart[$event['id']] ?></span>
                                <span>$<?= number_format($event['price'] * $cart[$event['id']], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>