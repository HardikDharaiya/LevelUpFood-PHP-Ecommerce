<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Access Control: Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    $_SESSION['login_error'] = "Please log in to view order details.";
    $redirect_url = 'order_details.php' . (isset($_GET['order_id']) ? '?order_id=' . urlencode($_GET['order_id']) : '');
    $_SESSION['redirect_url'] = $redirect_url;
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['id'];
// --- End Access Control ---

// --- Get and Validate Order ID ---
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$order_details = null; // Initialize as null
$order_items = [];
$error_message = '';

if (!$order_id || $order_id <= 0) {
    $error_message = "Invalid Order ID specified.";
} else {
    // --- Fetch Order Details (Verify Ownership) ---
    if (isset($con)) {
        // **Crucially, we fetch the data fresh *every time* this page loads**
        $stmt_order = $con->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        if ($stmt_order) {
            $stmt_order->bind_param("ii", $order_id, $user_id);
            if ($stmt_order->execute()) {
                $result_order = $stmt_order->get_result();
                if ($result_order->num_rows === 1) {
                    // Assign fetched data to $order_details
                    $order_details = $result_order->fetch_assoc();

                    // --- Fetch Order Items (Only if order was found) ---
                    // (Item fetching logic remains the same)
                    $stmt_items = $con->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    if ($stmt_items) {
                        $stmt_items->bind_param("i", $order_id);
                        if ($stmt_items->execute()) {
                            $result_items = $stmt_items->get_result();
                            $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
                            $result_items->free();
                        } else { $error_message = "Error fetching order items: " . $stmt_items->error; }
                        $stmt_items->close();
                    } else { $error_message = "Error preparing order items statement: " . $con->error; }
                    // --- End Fetch Order Items ---

                } else {
                    $error_message = "Order not found or you do not have permission to view it.";
                }
                $result_order->free();
            } else { $error_message = "Error fetching order details: " . $stmt_order->error; }
            $stmt_order->close();
        } else { $error_message = "Error preparing order details statement: " . $con->error; }
    } else { $error_message = "Database connection error."; }
}
// --- End Fetching Data ---


$pageTitle = $order_details ? "Order Details #" . htmlspecialchars($order_details['id']) : "Order Details";


include 'header.php';
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Order Details</h1>

    <?php
    // Display error message if any occurred
    if (!empty($error_message)):
    ?>
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars($error_message); ?> <br>
            <a href="order_history.php" class="alert-link">Return to Order History</a>
        </div>
    <?php
    // Display order details ONLY if $order_details is successfully populated
    elseif ($order_details): // Check if $order_details has data

        // **Determine status badge class based on the FRESHLY fetched data**
        $status = htmlspecialchars($order_details['status']); // Get status from fetched data
        $badge_class = 'bg-secondary'; // Default
        if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
        if ($status == 'Processing') $badge_class = 'bg-info text-dark';
        if ($status == 'Out for Delivery') $badge_class = 'bg-primary';
        if ($status == 'Delivered') $badge_class = 'bg-success';
        if ($status == 'Cancelled' || $status == 'Failed') $badge_class = 'bg-danger';
    ?>
        <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-2"> <!-- Slightly less padding -->
                 <div class="d-flex justify-content-between align-items-center flex-wrap"> <!-- Added flex-wrap -->
                    <!-- Order ID and Date on one line -->
                    <div class="me-3 mb-1 mb-md-0"> <!-- Margin end, bottom margin for wrap -->
                        <h5 class="mb-0 d-inline-block me-2">Order #<?php echo htmlspecialchars($order_details['id']); ?></h5>
                        <small class="text-muted">Placed: <?php echo date("d M Y, g:i A", strtotime($order_details['order_date'])); ?></small>
                    </div>
                     <!-- Status Badge - ensure this uses the correct variables -->
                    <span class="badge <?php echo $badge_class; ?> fs-6 py-2 px-3"><?php echo $status; ?></span> <!-- Adjusted padding -->
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- Shipping Details -->
                    <div class="col-md-6">
                        <h6>Delivery Address</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order_details['shipping_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_address_line1']); ?></p>
                        <?php if(!empty($order_details['shipping_address_line2'])): ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_address_line2']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_city']); ?> - <?php echo htmlspecialchars($order_details['shipping_postal_code']); ?></p>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($order_details['shipping_phone']); ?></p>
                    </div>
                    <!-- Payment & Summary -->
                    <div class="col-md-6">
                         <h6>Payment Information</h6>
                         <p class="mb-1">Method: <?php echo htmlspecialchars($order_details['payment_method']); ?></p>
                         <p class="mb-3">Status: <?php echo htmlspecialchars($order_details['payment_status']); ?></p>

                         <h6 class="mt-3">Order Summary</h6>
                         <ul class="list-group list-group-flush">
                            <!-- Summary items remain the same -->
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($order_details['total_amount'], 2); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Delivery Fee</span>
                                <span>₹0.00</span> <!-- Placeholder -->
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 fw-bold fs-5">
                                <span>Grand Total</span>
                                <span>₹<?php echo number_format($order_details['total_amount'], 2); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ordered Items (Check if $order_items is not empty) -->
        <?php if (!empty($order_items)): ?>
            <h4 class="mt-5 mb-3">Items Ordered</h4>
            <div class="table-responsive shadow-sm">
                <table class="table table-bordered table-hover align-middle">
                    <!-- Table Head remains the same -->
                     <thead class="table-light">
                        <tr>
                           <!-- Removed image column for simplicity, can add back if needed -->
                            <th>Item Name (at order)</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price Paid (Each)</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <!-- Removed image cell -->
                                <td><?php echo htmlspecialchars($item['item_name_at_order']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="text-end">₹ <?php echo number_format($item['price_at_order'], 2); ?></td>
                                <td class="text-end">₹ <?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                     <tfoot>
                        <tr>
                            <!-- Adjusted colspan -->
                            <td colspan="3" class="text-end fw-bold border-0">Grand Total:</td>
                            <td class="text-end fw-bold fs-5 border-0">₹ <?php echo number_format($order_details['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
             <div class="alert alert-warning">Order items could not be loaded for this order.</div>
        <?php endif; ?>


        <div class="mt-4 text-center">
             <a href="order_history.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Order History</a>
        </div>

    <?php else: ?>
        <!-- Fallback if $order_details is null but no specific $error_message was set -->
        <div class="alert alert-warning text-center" role="alert">
            Could not retrieve order details.
            <a href="order_history.php" class="alert-link">Return to Order History</a>
        </div>
    <?php endif; ?>


</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>