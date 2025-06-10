<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events.php");
    exit();
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if (add_to_cart($event_id, $quantity)) {
        $_SESSION['success_message'] = 'Event added to cart!';
        redirect('cart.php');
    } else {
        $_SESSION['error_message'] = 'Failed to add to cart';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking - <?= htmlspecialchars($event['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        #eventMap { height: 300px; }
        .booking-options { margin-top: 30px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <!-- Back Button -->
        <a href="events.php" class="btn btn-outline-secondary mb-4">← Back to Events</a>
        
        <!-- Event Details -->
        <div class="row">
            <!-- Left Column (Image) -->
            <div class="col-md-6">
                <img src="assets/images/<?= htmlspecialchars($event['image']) ?>" 
                     class="img-fluid rounded shadow" 
                     alt="<?= htmlspecialchars($event['name']) ?>"
                     style="max-height: 500px; width: 100%; object-fit: cover;">
            </div>
            
            <!-- Right Column (Details) -->
            <div class="col-md-6">
                <h1><?= htmlspecialchars($event['name']) ?></h1>
                <p class="text-muted">Organized by <?= htmlspecialchars($event['organizer']) ?></p>
                
                <div class="mb-4">
                    <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($event['date'])) ?></p>
                    <p><strong>Time:</strong> <?= date('g:i A', strtotime($event['time'])) ?></p>
                    <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
                    <p><strong>Price:</strong> <span class="text-success fw-bold">$<?= number_format($event['price'], 2) ?></span></p>
                </div>
                
                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                
                <!-- Booking Options -->
                <div class="booking-options">
                    <form method="POST" action="event_details.php?id=<?= $event['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="number" name="quantity" value="1" min="1" class="form-control">
                                    <button type="submit" name="add_to_cart" value="<?= $event['id'] ?>" 
                                            class="btn btn-success">Add to Cart</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <a href="checkout.php?event_id=<?= $event['id'] ?>" 
                                   class="btn btn-primary w-100">Book Now</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Map Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h4>Location</h4>
                <div id="eventMap" class="rounded shadow"></div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    
    <!-- Map Script -->
    <script>
        // Initialize map (using dummy coordinates)
        const map = L.map('eventMap').setView([51.505, -0.09], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add marker for event venue
        L.marker([51.505, -0.09])
            .addTo(map)
            .bindPopup("<?= addslashes($event['venue']) ?>")
            .openPopup();
    </script>
</body>
</html>