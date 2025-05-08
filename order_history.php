<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Access Control: Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    $_SESSION['login_error'] = "Please log in to view your order history.";
    $_SESSION['redirect_url'] = 'order_history.php';
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['id']; // Get logged-in user's ID
// --- End Access Control ---

$pageTitle = "My Order History - LevelUpFood";

// --- Fetch User's Orders ---
$orders = [];
$error_message = '';

if (isset($con)) {
    // Select orders for the current user, newest first
    $stmt = $con->prepare("SELECT id, order_date, total_amount, status
                           FROM orders
                           WHERE user_id = ?
                           ORDER BY order_date DESC");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        } else {
            $error_message = "Error fetching order history: " . $stmt->error;
            error_log("DB Error fetching order history for user $user_id: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Error preparing order history statement: " . $con->error;
        error_log("DB Prepare Error fetching order history: " . $con->error);
    }
    // Keep connection open for footer if needed
} else {
    $error_message = "Database connection error.";
    error_log("DB connection error in order_history.php");
}
// --- End Fetch Orders ---


include 'header.php';
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">My Order History</h1>

    <?php
    // Display error if fetching failed
    if (!empty($error_message)):
    ?>
        <div class="alert alert-danger" role="alert">
            Could not load your order history. Please try again later. <br>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php
    // Display message if no orders found
    elseif (empty($orders)):
    ?>
         <div class="alert alert-info text-center" role="alert">
            You haven't placed any orders yet. <br>
            <a href="dashboard.php" class="alert-link">Start Shopping Now!</a>
        </div>
    <?php
    // Display order history table if orders exist
    else:
    ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Date Placed</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo date("d M Y, g:i A", strtotime($order['order_date'])); ?></td>
                            <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                    // Add badges for status (customize colors as needed)
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
                            <td class="text-center">
                                <!-- Link to order_details.php (to be created later) -->
                                <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Order Details">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; // End check for orders ?>

</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>
