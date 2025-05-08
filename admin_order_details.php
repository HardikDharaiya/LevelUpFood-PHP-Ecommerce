<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Access Control: Check if user is logged in and is an admin ---
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['login_error'] = "Access Denied. Admins only.";
    header("Location: login.php");
    exit();
}
// --- End Access Control ---

// Define possible order statuses (should match ENUM in DB and manage_orders.php)
$possible_statuses = ['Pending', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled', 'Failed'];

// --- Get and Validate Order ID ---
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$order_details = null;
$order_items = [];
$customer_details = null;
$error_message = '';
$success_message = ''; // For status update

// --- Handle POST Action (Update Status - optional, can be done here too) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id_to_update = filter_input(INPUT_POST, 'order_id_hidden', FILTER_VALIDATE_INT);
    $new_status = trim($_POST['new_status'] ?? '');

    // Validate status and ensure order ID matches the one being viewed
    if ($order_id_to_update > 0 && $order_id_to_update == $order_id && in_array($new_status, $possible_statuses)) {
        if (isset($con)) {

             // --- Determine if payment status should also be updated ---
            $update_payment_sql = "";
            $types = "si"; // Types for status, id
            $params = [$new_status, $order_id_to_update]; // Params for status, id

            if ($new_status === 'Delivered') {
                 $stmt_check_method = $con->prepare("SELECT payment_method FROM orders WHERE id = ?");
                if($stmt_check_method){
                    $stmt_check_method->bind_param("i", $order_id_to_update);
                    $stmt_check_method->execute();
                    $result_method = $stmt_check_method->get_result();
                    if($row_method = $result_method->fetch_assoc()){
                        if ($row_method['payment_method'] === 'COD') {
                             $update_payment_sql = ", payment_status = ?";
                             $types .= "s";
                             // Reorder params for bind_param: status, payment_status, id
                             $params = [$new_status, 'Completed', $order_id_to_update];
                        }
                    }
                    $stmt_check_method->close();
                }
            }
             // --- End Payment Status Logic ---

            $sql_update = "UPDATE orders SET status = ? {$update_payment_sql} WHERE id = ?";
            $stmt_update = $con->prepare($sql_update);

            if ($stmt_update) {
                $stmt_update->bind_param($types, ...$params);
                if ($stmt_update->execute()) {
                    $success_message = "Order #{$order_id_to_update} status updated to '{$new_status}'.";
                     // **Important: Re-fetch order details after update to show changes**
                    // Re-running the fetch logic here
                    $stmt_refetch = $con->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                    if ($stmt_refetch) {
                        $stmt_refetch->bind_param("i", $order_id_to_update);
                        $stmt_refetch->execute();
                        $result_refetch = $stmt_refetch->get_result();
                        $order_details = $result_refetch->fetch_assoc(); // Overwrite with fresh data
                        $result_refetch->close();
                        $stmt_refetch->close();
                    }
                    // End re-fetch
                } else {
                    $error_message = "Error updating order status: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else { $error_message = "Error preparing status update statement: " . $con->error; }
        } else { $error_message = "Database connection error during status update."; }
    } else { $error_message = "Invalid data provided for status update."; }
    // Let the page re-load below to show updated status or error
}


// --- Fetch Data if Order ID is Valid ---
if (!$order_id || $order_id <= 0) {
    $error_message = "Invalid Order ID specified.";
} else {
    if (isset($con)) {
        // --- Fetch Order Details and Customer Info ---
        $stmt_order = $con->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email
                                     FROM orders o
                                     JOIN users u ON o.user_id = u.id
                                     WHERE o.id = ?");
        if ($stmt_order) {
            $stmt_order->bind_param("i", $order_id);
            if ($stmt_order->execute()) {
                $result_order = $stmt_order->get_result();
                if ($result_order->num_rows === 1) {
                    $order_details = $result_order->fetch_assoc();

                    // --- Fetch Order Items (Only if order was found) ---
                    $stmt_items = $con->prepare("SELECT oi.*, mi.image_url
                                                 FROM order_items oi
                                                 LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                                                 WHERE oi.order_id = ?");
                    if ($stmt_items) {
                        $stmt_items->bind_param("i", $order_id);
                        if ($stmt_items->execute()) {
                            $result_items = $stmt_items->get_result();
                            $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
                            $result_items->free();
                        } else { $error_message .= " Error fetching order items: " . $stmt_items->error; }
                        $stmt_items->close();
                    } else { $error_message .= " Error preparing items statement: " . $con->error; }
                    // --- End Fetch Order Items ---

                } else {
                    $error_message = "Order #{$order_id} not found."; // Order doesn't exist
                }
                $result_order->free();
            } else { $error_message = "Error fetching order details: " . $stmt_order->error; }
            $stmt_order->close();
        } else { $error_message = "Error preparing order details statement: " . $con->error; }
    } else { $error_message = "Database connection error."; }
}
// --- End Fetching Data ---

$pageTitle = $order_details ? "Admin - Order #" . htmlspecialchars($order_details['id']) : "Admin - Order Details";
include 'header.php';
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Order Details</h1>

    <?php if (!empty($success_message)): // Display status update success ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): // Display any errors ?>
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars(trim($error_message)); ?> <br>
            <a href="manage_orders.php" class="alert-link">Return to Manage Orders</a>
        </div>
    <?php elseif ($order_details && !empty($order_items)): // Display order details if found
        // Determine status badge class
        $status = htmlspecialchars($order_details['status']);
        $badge_class = 'bg-secondary';
        if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
        if ($status == 'Processing') $badge_class = 'bg-info text-dark';
        if ($status == 'Out for Delivery') $badge_class = 'bg-primary';
        if ($status == 'Delivered') $badge_class = 'bg-success';
        if ($status == 'Cancelled' || $status == 'Failed') $badge_class = 'bg-danger';
    ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                 <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0 me-3">Order #<?php echo htmlspecialchars($order_details['id']); ?></h5>
                    <span class="badge <?php echo $badge_class; ?> fs-6 me-3"><?php echo $status; ?></span>
                     <small class="text-muted">Placed: <?php echo date("d M Y, g:i A", strtotime($order_details['order_date'])); ?></small>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                     <!-- Customer Details -->
                     <div class="col-lg-4">
                        <h6>Customer Information</h6>
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
                        <p class="mb-0"><strong>User ID:</strong> <?php echo htmlspecialchars($order_details['user_id']); ?></p>
                    </div>
                    <!-- Shipping Details -->
                    <div class="col-lg-4">
                        <h6>Delivery Address</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order_details['shipping_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_address_line1']); ?></p>
                        <?php if(!empty($order_details['shipping_address_line2'])): ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_address_line2']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order_details['shipping_city']); ?> - <?php echo htmlspecialchars($order_details['shipping_postal_code']); ?></p>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($order_details['shipping_phone']); ?></p>
                    </div>
                    <!-- Payment & Status Update -->
                    <div class="col-lg-4">
                         <h6>Payment & Status</h6>
                         <p class="mb-1">Method: <?php echo htmlspecialchars($order_details['payment_method']); ?></p>
                         <p class="mb-3">Status: <?php echo htmlspecialchars($order_details['payment_status']); ?></p>
                         <?php if (!empty($order_details['transaction_id'])): ?>
                         <p class="mb-3 small text-muted">Transaction ID: <?php echo htmlspecialchars($order_details['transaction_id']); ?></p>
                         <?php endif; ?>

                         <!-- Status Update Form -->
                         <form action="admin_order_details.php?order_id=<?php echo $order_id; ?>" method="POST">
                             <input type="hidden" name="order_id_hidden" value="<?php echo $order_id; ?>"> <!-- Use hidden field for ID -->
                             <label for="status-update-<?php echo $order_id; ?>" class="form-label fw-bold">Update Order Status:</label>
                             <div class="input-group">
                                 <select name="new_status" id="status-update-<?php echo $order_id; ?>" class="form-select" required>
                                     <?php foreach ($possible_statuses as $stat): ?>
                                         <option value="<?php echo $stat; ?>" <?php echo ($order_details['status'] == $stat) ? 'selected' : ''; ?>>
                                             <?php echo $stat; ?>
                                         </option>
                                     <?php endforeach; ?>
                                 </select>
                                 <button type="submit" name="update_status" class="btn btn-success" title="Update Status">
                                     <i class="bi bi-check-lg"></i> Save
                                 </button>
                             </div>
                         </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ordered Items Table -->
        <h4 class="mt-5 mb-3">Items in this Order</h4>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Item Name (at order)</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price (Each)</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                             <td>
                                <?php
                                    // Use placeholder if original menu item image deleted/path changed
                                    $item_image_path = (!empty($item['image_url']) && file_exists($item['image_url']))
                                                  ? htmlspecialchars($item['image_url']) . '?t=' . time()
                                                  : 'Images/menu/placeholder.png';
                                    $item_alt_text = htmlspecialchars($item['item_name_at_order']);
                                ?>
                                <img src="<?php echo $item_image_path; ?>" alt="<?php echo $item_alt_text; ?>" class="img-thumbnail" style="width: 70px; height: 70px; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($item['item_name_at_order']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td class="text-end">₹ <?php echo number_format($item['price_at_order'], 2); ?></td>
                            <td class="text-end">₹ <?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                 <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold border-0">Grand Total:</td>
                        <td class="text-end fw-bold fs-5 border-0">₹ <?php echo number_format($order_details['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 text-center">
             <a href="manage_orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to All Orders</a>
        </div>

    <?php else: ?>
        <!-- Fallback if $order_details is null/empty but no specific $error_message was set -->
        <div class="alert alert-warning text-center" role="alert">
            Could not retrieve details for this order.
            <a href="manage_orders.php" class="alert-link">Return to Manage Orders</a>
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