<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = 'cart.php';
    redirect('login.php');
}

$cart = get_cart();
$cart_items = [];
$total = 0;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $event_id => $quantity) {
            update_cart_item($event_id, intval($quantity));
        }
        $_SESSION['success_message'] = 'Cart updated successfully!';
        redirect('cart.php');
    } elseif (isset($_POST['remove'])) {
        remove_from_cart($_POST['remove']);
        $_SESSION['success_message'] = 'Item removed from cart!';
        redirect('cart.php');
    } elseif (isset($_POST['clear'])) {
        clear_cart();
        $_SESSION['success_message'] = 'Cart cleared!';
        redirect('cart.php');
    }
}

// Get cart items with details
if (!empty($cart)) {
    $event_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id IN ($placeholders)");
    $stmt->execute($event_ids);
    $events = $stmt->fetchAll();
    
    foreach ($events as $event) {
        $cart_items[] = [
            'event' => $event,
            'quantity' => $cart[$event['id']],
            'subtotal' => $event['price'] * $cart[$event['id']]
        ];
        $total += $event['price'] * $cart[$event['id']];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking - Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Your Shopping Cart</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="events.php">Browse events</a> to get started!
            </div>
        <?php else: ?>
            <form action="cart.php" method="POST">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="assets/images/<?= htmlspecialchars($item['event']['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['event']['name']) ?>" 
                                             width="80" class="me-3">
                                        <?= htmlspecialchars($item['event']['name']) ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($item['event']['date'])) ?></td>
                                    <td>$<?= number_format($item['event']['price'], 2) ?></td>
                                    <td>
                                        <input type="number" name="quantity[<?= $item['event']['id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 70px;">
                                    </td>
                                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                    <td>
                                        <button type="submit" name="remove" value="<?= $item['event']['id'] ?>" 
                                                class="btn btn-sm btn-danger">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td colspan="2" class="fw-bold">$<?= number_format($total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" name="clear" class="btn btn-outline-danger">Clear Cart</button>
                    <div>
                        <button type="submit" name="update" class="btn btn-secondary me-2">Update Cart</button>
                        <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>