<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------
// AUTHENTICATION FUNCTIONS
// --------------------------

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// --------------------------
// CART FUNCTIONS
// --------------------------

/**
 * Get or initialize shopping cart
 */
function get_cart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Add item to cart
 * @param int $event_id - Event ID to add
 * @param int $quantity - Quantity (default 1)
 * @return bool - True on success
 */
function add_to_cart($event_id, $quantity = 1) {
    if ($quantity < 1) return false;
    
    $event_id = (int)$event_id;
    $quantity = (int)$quantity;
    $cart = get_cart();
    
    if (isset($cart[$event_id])) {
        $cart[$event_id] += $quantity;
    } else {
        $cart[$event_id] = $quantity;
    }
    
    $_SESSION['cart'] = $cart;
    return true;
}

/**
 * Remove item from cart
 * @param int $event_id - Event ID to remove
 * @return bool - True if item was removed
 */
function remove_from_cart($event_id) {
    $cart = get_cart();
    $event_id = (int)$event_id;
    
    if (isset($cart[$event_id])) {
        unset($cart[$event_id]);
        $_SESSION['cart'] = $cart;
        return true;
    }
    
    return false;
}

/**
 * Update item quantity in cart
 * @param int $event_id - Event ID to update
 * @param int $quantity - New quantity
 * @return bool - True on success
 */
function update_cart_item($event_id, $quantity) {
    $quantity = (int)$quantity;
    $event_id = (int)$event_id;
    
    if ($quantity <= 0) {
        return remove_from_cart($event_id);
    }
    
    $cart = get_cart();
    $cart[$event_id] = $quantity;
    $_SESSION['cart'] = $cart;
    return true;
}

/**
 * Empty the cart completely
 * @return bool - Always returns true
 */
function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

/**
 * Count total items in cart
 * @return int - Total quantity of all items
 */
function count_cart_items() {
    return array_sum(get_cart());
}

/**
 * Calculate total cart price
 * @param PDO $pdo - Database connection
 * @return float - Total price (rounded to 2 decimals)
 */
function calculate_cart_total($pdo) {
    $total = 0.00;
    $cart = get_cart();
    
    if (empty($cart)) return $total;
    
    try {
        $event_ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
        
        $stmt = $pdo->prepare("
            SELECT id, price 
            FROM events 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($event_ids);
        
        while ($event = $stmt->fetch(PDO::FETCH_OBJ)) {
            if (isset($cart[$event->id])) {
                $total += ($event->price * $cart[$event->id]);
            }
        }
        
        return round($total, 2);
        
    } catch (PDOException $e) {
        error_log("Cart Calculation Error: " . $e->getMessage());
        return 0.00;
    }
}

function is_admin() {
    return is_logged_in() && $_SESSION['is_admin'] == 1;
}