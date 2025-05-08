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

$pageTitle = "Manage Orders - Admin";

// Define possible order statuses (should match ENUM in DB)
$possible_statuses = ['Pending', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled', 'Failed'];

// --- Handle POST Action (Update Status) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id_to_update = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = trim($_POST['new_status'] ?? ''); // The status selected in the dropdown
    $error_message = ''; // Local error message for this block
    $success_message = ''; // Local success message

    // Validate status
    if ($order_id_to_update > 0 && in_array($new_status, $possible_statuses)) {
        if (isset($con)) {

            $sql_update = ""; // Initialize SQL update string
            $types = "";      // Initialize types string for bind_param
            $params = [];     // Initialize parameters array

            $current_payment_method = ''; // To store the payment method

            // Check if we need to update payment status (only for COD going to Delivered)
            $update_payment = false;
            if ($new_status === 'Delivered') {
                $stmt_check_method = $con->prepare("SELECT payment_method FROM orders WHERE id = ?");
                if ($stmt_check_method) {
                    $stmt_check_method->bind_param("i", $order_id_to_update);
                    $stmt_check_method->execute();
                    $result_method = $stmt_check_method->get_result();
                    if ($row_method = $result_method->fetch_assoc()) {
                        $current_payment_method = $row_method['payment_method'];
                        if ($current_payment_method === 'COD') {
                            $update_payment = true;
                        }
                    } else { $error_message = "Order not found for status check."; }
                    $stmt_check_method->close();
                } else { $error_message = "Error preparing payment method check."; }
            }

            // Proceed only if there wasn't an error checking the payment method
            if (empty($error_message)) {
                // Build the SQL statement and parameters array
                if ($update_payment) {
                    // Update both order status and payment status
                    $sql_update = "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?";
                    $types = "ssi"; // string (status), string (payment_status), integer (id)
                    $params = [$new_status, 'Completed', $order_id_to_update];
                } else {
                    // Update only the order status
                    $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
                    $types = "si"; // string (status), integer (id)
                    $params = [$new_status, $order_id_to_update];
                }

                // Prepare and execute the final update statement
                $stmt_update = $con->prepare($sql_update);
                if ($stmt_update) {
                    // Bind parameters using the splat operator
                    $stmt_update->bind_param($types, ...$params);

                    if ($stmt_update->execute()) {
                        $success_message = "Order #{$order_id_to_update} status updated to '{$new_status}'";
                        if ($update_payment) {
                            $success_message .= " and payment status updated to 'Completed'.";
                        } else {
                             $success_message .= ".";
                        }
                        $_SESSION['flash_message'] = ['type' => 'success', 'message' => $success_message];
                    } else {
                        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Error executing order status update: " . $stmt_update->error];
                        error_log("DB Execute Error updating order status: " . $stmt_update->error);
                    }
                    $stmt_update->close();
                } else {
                    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Error preparing final status update statement: " . $con->error];
                     error_log("DB Prepare Error updating order status: " . $con->error);
                }
            } else {
                // Error occurred while checking payment method
                 $_SESSION['flash_message'] = ['type' => 'danger', 'message' => $error_message];
            }

        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Database connection error during status update."];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Invalid data provided for status update."];
    }
    // Redirect back to the same page to prevent form resubmission and show flash message
    header("Location: manage_orders.php");
    exit();
}
// --- End Handle POST Action ---

// --- Fetch All Orders ---
$orders = [];
$error_message = '';

if (isset($con)) {
    // Select orders, join with users to get email, newest first
    $stmt = $con->prepare("SELECT o.*, u.email as user_email
                           FROM orders o
                           JOIN users u ON o.user_id = u.id
                           ORDER BY o.order_date DESC");

    if ($stmt) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        } else {
            $error_message = "Error fetching orders: " . $stmt->error;
            error_log("DB Error fetching all orders: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Error preparing orders statement: " . $con->error;
        error_log("DB Prepare Error fetching all orders: " . $con->error);
    }
} else {
    $error_message = "Database connection error.";
    error_log("DB connection error in manage_orders.php");
}
// --- End Fetch Orders ---

// Retrieve and clear flash message
$flash_message_data = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

include 'header.php';
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Manage Customer Orders</h1>

     <?php
    // Display Flash Message if set
    if ($flash_message_data):
    ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash_message_data['type']); ?> alert-dismissible fade show" role="alert">
             <?php echo htmlspecialchars($flash_message_data['message']); ?>
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php
    // Display error if fetching failed
    if (!empty($error_message)):
    ?>
        <div class="alert alert-danger" role="alert">
            Could not load order information. <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php
    // Display message if no orders found
    elseif (empty($orders)):
    ?>
         <div class="alert alert-info text-center" role="alert">
            There are currently no orders to display.
        </div>
    <?php
    // Display order table if orders exist
    else:
    ?>
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">All Orders</h5>
            </div>
            <div class="card-body p-0"> <!-- Remove padding for full-width table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0"> <!-- Remove bottom margin -->
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="min-width: 200px;">Update Status</th> <!-- Wider column -->
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo date("d M Y, g:i a", strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['shipping_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                    </td>
                                    <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($order['status']);
                                            $badge_class = 'bg-secondary'; // Default
                                            if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
                                            if ($status == 'Processing') $badge_class = 'bg-info text-dark';
                                            if ($status == 'Out for Delivery') $badge_class = 'bg-primary';
                                            if ($status == 'Delivered') $badge_class = 'bg-success';
                                            if ($status == 'Cancelled' || $status == 'Failed') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td>
                                        <!-- Status Update Form -->
                                        <form action="manage_orders.php" method="POST" class="d-flex align-items-center">
                                             <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                             <select name="new_status" class="form-select form-select-sm me-2" required>
                                                 <?php foreach ($possible_statuses as $stat): ?>
                                                     <option value="<?php echo $stat; ?>" <?php echo ($order['status'] == $stat) ? 'selected' : ''; ?>>
                                                         <?php echo $stat; ?>
                                                     </option>
                                                 <?php endforeach; ?>
                                             </select>
                                             <button type="submit" name="update_status" class="btn btn-sm btn-outline-success flex-shrink-0" title="Update Status">
                                                <i class="bi bi-check-lg"></i> Update
                                             </button>
                                         </form>
                                    </td>
                                    <td class="text-center">
    <!-- Link to admin_order_details.php -->
    <a href="admin_order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Order Details">
        <i class="bi bi-eye-fill"></i> View
    </a>
</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- End card body -->
        </div> <!-- End card -->
    <?php endif; // End check for orders ?>

</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>