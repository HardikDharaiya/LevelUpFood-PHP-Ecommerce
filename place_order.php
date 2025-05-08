<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Security Checks ---

// 1. Ensure accessed via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not POST, redirect away (e.g., to cart or dashboard)
    header('Location: cart.php');
    exit();
}

// 2. Ensure user is logged in
if (!isset($_SESSION['id'])) {
    // Should ideally not happen if checkout.php has access control, but double-check
    $_SESSION['login_error'] = "Please log in to place an order.";
    $_SESSION['redirect_url'] = 'checkout.php';
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['id']; // Get logged-in user's ID

// 3. Ensure cart is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Your cart is empty. Cannot place order.'];
    header('Location: dashboard.php'); // Redirect to menu
    exit();
}

// --- Data Retrieval & Sanitization from POST ---
$shipping_name = trim(filter_input(INPUT_POST, 'shipping_name', FILTER_SANITIZE_STRING));
$shipping_phone = trim(filter_input(INPUT_POST, 'shipping_phone', FILTER_SANITIZE_STRING));
$shipping_address_line1 = trim(filter_input(INPUT_POST, 'shipping_address_line1', FILTER_SANITIZE_STRING));
$shipping_address_line2 = trim(filter_input(INPUT_POST, 'shipping_address_line2', FILTER_SANITIZE_STRING)) ?: null; // Allow null if empty
$shipping_city = trim(filter_input(INPUT_POST, 'shipping_city', FILTER_SANITIZE_STRING));
$shipping_postal_code = trim(filter_input(INPUT_POST, 'shipping_postal_code', FILTER_SANITIZE_STRING));
$payment_method = trim(filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING)); // Currently expecting 'COD'

// --- Server-Side Validation ---
$errors = [];
if (empty($shipping_name)) $errors[] = "Full name is required.";
if (empty($shipping_phone)) $errors[] = "Phone number is required.";
// Basic phone validation (10 digits) - adjust regex if needed for international
elseif (!preg_match('/^\d{10}$/', $shipping_phone)) $errors[] = "Invalid phone number format (10 digits required).";
if (empty($shipping_address_line1)) $errors[] = "Address Line 1 is required.";
if (empty($shipping_city)) $errors[] = "City is required.";
if (empty($shipping_postal_code)) $errors[] = "Postal code is required.";
// Basic postal code validation (6 digits) - adjust regex if needed
elseif (!preg_match('/^\d{6}$/', $shipping_postal_code)) $errors[] = "Invalid postal code format (6 digits required).";
if (empty($payment_method) || $payment_method !== 'COD') $errors[] = "Invalid payment method selected."; // For now only COD


// If validation errors, redirect back to checkout with errors and form data
if (!empty($errors)) {
    $_SESSION['checkout_error'] = 'Please fix the following errors:<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
    // Store submitted values to repopulate form (optional, but good UX)
    $_SESSION['checkout_data'] = $_POST;
    header('Location: checkout.php');
    exit();
}

// --- Prepare for Database Operations ---
$order_items_to_save = []; // Array to hold items verified against DB
$calculated_total = 0.00;
$db_error = false;
$order_id = null; // To store the new order ID

// --- Fetch Item Details from DB (CRITICAL: Re-verify price and availability) ---
$cart_product_ids = array_keys($_SESSION['cart']);
if (!empty($cart_product_ids)) {
    $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));
    $types = str_repeat('i', count($cart_product_ids));
    $sql = "SELECT id, name, price FROM menu_items WHERE id IN ($placeholders) AND is_available = 1";

    if (isset($con)) {
        $stmt_items = $con->prepare($sql);
        if ($stmt_items) {
            $stmt_items->bind_param($types, ...$cart_product_ids);
            if ($stmt_items->execute()) {
                $result = $stmt_items->get_result();
                $db_items = [];
                while($row = $result->fetch_assoc()) { $db_items[$row['id']] = $row; } // Key by ID
                $result->free();

                // Verify session cart against current DB data and calculate total
                foreach ($_SESSION['cart'] as $id => $quantity) {
                    if (isset($db_items[$id])) { // Check if item still exists and is available
                        $item_data = $db_items[$id];
                        $price_at_order = $item_data['price']; // Use current DB price
                        $order_items_to_save[] = [
                            'menu_item_id' => $id,
                            'item_name_at_order' => $item_data['name'], // Store name
                            'quantity' => $quantity,
                            'price_at_order' => $price_at_order
                        ];
                        $calculated_total += ($price_at_order * $quantity);
                    }
                    // If item not in $db_items, it's unavailable/deleted; silently ignore for order placement
                }

                // If after checking availability, the effective cart is empty
                if (empty($order_items_to_save)) {
                    $db_error = true;
                    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'The items in your cart are no longer available. Please update your cart.'];
                    header('Location: cart.php');
                    exit();
                }

            } else { $db_error = true; $error_message = "Error fetching item details: " . $stmt_items->error; }
            $stmt_items->close();
        } else { $db_error = true; $error_message = "Error preparing item statement: " . $con->error; }
    } else { $db_error = true; $error_message = "Database connection error."; }
} else {
    // Cart was technically not empty before, but became empty after validation? Highly unlikely.
     $db_error = true; $error_message = "Cart appears empty after validation.";
}


// --- Proceed only if item fetching and validation were successful ---
if (!$db_error) {

    // --- Start Database Transaction ---
    $con->begin_transaction();

    try {
        // 1. Insert into 'orders' table
        $stmt_order = $con->prepare("INSERT INTO orders
            (user_id, total_amount, status, shipping_name, shipping_address_line1, shipping_address_line2, shipping_city, shipping_postal_code, shipping_phone, payment_method, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt_order) throw new Exception("Prepare failed (orders): " . $con->error);

        $order_status = 'Pending';
        $payment_status = 'Pending'; // For COD

        $stmt_order->bind_param("idsssssssss",
            $user_id, $calculated_total, $order_status, $shipping_name,
            $shipping_address_line1, $shipping_address_line2, $shipping_city,
            $shipping_postal_code, $shipping_phone, $payment_method, $payment_status
        );

        if (!$stmt_order->execute()) throw new Exception("Execute failed (orders): " . $stmt_order->error);

        $order_id = $con->insert_id; // Get the ID of the order just inserted
        $stmt_order->close();

        if (!$order_id) throw new Exception("Failed to retrieve new order ID.");


        // 2. Insert into 'order_items' table for each item
        $stmt_item = $con->prepare("INSERT INTO order_items
            (order_id, menu_item_id, item_name_at_order, quantity, price_at_order)
            VALUES (?, ?, ?, ?, ?)");

        if (!$stmt_item) throw new Exception("Prepare failed (order_items): " . $con->error);

        foreach ($order_items_to_save as $item) {
            $stmt_item->bind_param("iisid",
                $order_id,
                $item['menu_item_id'],
                $item['item_name_at_order'],
                $item['quantity'],
                $item['price_at_order']
            );
            if (!$stmt_item->execute()) throw new Exception("Execute failed (order_items): " . $stmt_item->error . " for item ID " . $item['menu_item_id']);
        }
        $stmt_item->close();

        // 3. If all inserts successful, commit the transaction
        $con->commit();

        // 4. Clear the shopping cart from the session
        unset($_SESSION['cart']);

        // 5. Set success message and redirect to a confirmation page
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Your order has been placed successfully! Order ID: ' . $order_id];
        header('Location: order_success.php?order_id=' . $order_id); // Redirect to success page
        exit();

    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $con->rollback();

        // Log the detailed error
        error_log("Order placement failed: " . $e->getMessage());

        // Set a user-friendly error message and redirect back to checkout
        $_SESSION['checkout_error'] = "We encountered an error while placing your order. Please try again. If the problem persists, contact support.";
        // Keep submitted data to repopulate form
        $_SESSION['checkout_data'] = $_POST;
        header('Location: checkout.php');
        exit();
    }

} else {
    // An error occurred before starting the transaction (e.g., fetching items)
    $_SESSION['checkout_error'] = "Could not process order due to an error: " . htmlspecialchars($error_message);
     // Keep submitted data to repopulate form
    $_SESSION['checkout_data'] = $_POST;
    header('Location: checkout.php');
    exit();
}

// Close connection if still open (should be closed after commit/rollback or error)
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>