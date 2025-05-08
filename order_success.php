<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Access Control: Check if user is logged in ---
// Although unlikely to reach here without login, it's good practice
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL, validate it
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

// Retrieve flash message if set (from place_order.php)
$flash_message_data = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']); // Clear it after retrieving

$pageTitle = "Order Confirmation - LevelUpFood";
include 'header.php';
?>

<div class="container section text-center">

    <?php if ($flash_message_data && $flash_message_data['type'] === 'success'): ?>
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Order Placed Successfully!</h4>
            <p><?php echo htmlspecialchars($flash_message_data['message']); ?></p>
            <hr>
            <p class="mb-0">Thank you for choosing LevelUpFood. You will receive updates regarding your order shortly (if applicable).</p>
        </div>
    <?php elseif ($order_id): // Show generic message if flash message missing but order ID present ?>
         <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Order Placed Successfully!</h4>
            <p>Your Order ID is: <?php echo htmlspecialchars($order_id); ?></p>
            <hr>
            <p class="mb-0">Thank you for choosing LevelUpFood.</p>
        </div>
    <?php else: ?>
        <!-- Should not happen if redirected correctly, but handle it -->
        <div class="alert alert-warning" role="alert">
           Order confirmation details are missing. Please check your order history or contact support if you believe you placed an order.
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-primary me-2">Continue Shopping</a>
        <!-- Link to Order History page (to be created later) -->
        <a href="order_history.php" class="btn btn-outline-secondary">View Order History</a>
    </div>

</div>

<?php include 'footar.php'; ?>