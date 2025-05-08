<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Need DB connection to verify item exists and get details

// Default redirect location (usually back to the menu or previous page)
$redirect_page = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php'; // Go back where user came from, or dashboard

// Initialize cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Use an array to store cart items: [product_id => quantity]
}

// Check if product ID is provided via GET
if (isset($_GET['id'])) {
    $product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $quantity = 1; // Add one item at a time for now

    if ($product_id && $product_id > 0) {
        // --- Validate Product ID against Database ---
        $stmt = $con->prepare("SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1");
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Product exists and is available, add/update cart
                if (isset($_SESSION['cart'][$product_id])) {
                    // Product already in cart, increment quantity
                    $_SESSION['cart'][$product_id] += $quantity;
                    $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Quantity updated in your cart.'];
                } else {
                    // Product not in cart, add it
                    $_SESSION['cart'][$product_id] = $quantity;
                     $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Item added to your cart!'];
                }

            } else {
                // Product not found or not available
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Sorry, this item cannot be added to the cart.'];
            }
            $stmt->close();
        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Database error checking item.'];
             error_log("DB Prepare Error in add_to_cart.php: " . $con->error);
        }
        $con->close();

    } else {
        // Invalid Product ID format
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid item specified.'];
    }
} else {
    // No product ID provided
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'No item specified to add.'];
}

// Redirect back to the previous page (or dashboard)
header("Location: " . $redirect_page);
exit();
?>