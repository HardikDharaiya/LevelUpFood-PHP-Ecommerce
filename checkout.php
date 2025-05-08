<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Need DB connection to get item details for summary

$pageTitle = "Checkout - LevelUpFood";

// --- Access Control: Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    $_SESSION['login_error'] = "Please log in to proceed to checkout.";
    // Store the intended destination after login
    $_SESSION['redirect_url'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// --- Check if Cart is Empty ---
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
     $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Your cart is empty. Please add items before checking out.'];
    header('Location: dashboard.php'); // Redirect to menu if cart is empty
    exit();
}


// --- Fetch Detailed Cart Item Information for Summary ---
// (Similar logic as in cart.php, but we don't need update/remove forms here)
$cart_items_details = [];
$cart_total = 0.00;
$error_message = '';

$product_ids = array_keys($_SESSION['cart']);
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    $sql = "SELECT id, name, price, image_url FROM menu_items WHERE id IN ($placeholders) AND is_available = 1";

    if (isset($con)) {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$product_ids);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $db_items = $result->fetch_all(MYSQLI_ASSOC);
                $found_items_db = [];
                 foreach ($db_items as $item) {
                    $found_items_db[$item['id']] = $item;
                }

                foreach ($_SESSION['cart'] as $id => $quantity) {
                    if (isset($found_items_db[$id])) {
                        $item_data = $found_items_db[$id];
                        $subtotal = $item_data['price'] * $quantity;
                        $cart_items_details[] = [
                            'id' => $id,
                            'name' => $item_data['name'],
                            'price' => $item_data['price'],
                            'quantity' => $quantity,
                            'subtotal' => $subtotal
                        ];
                        $cart_total += $subtotal;
                    } else {
                        // Item became unavailable since adding to cart - handle silently or show msg later
                        unset($_SESSION['cart'][$id]); // Remove from cart if unavailable
                        // Consider adding a message here if needed
                    }
                }
                 // If all items became unavailable after loop, redirect back to cart
                 if (empty($cart_items_details) && !empty($_SESSION['cart'])) { // check session cart again
                     $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Some items in your cart are no longer available and have been removed.'];
                     header('Location: cart.php');
                     exit();
                 } elseif (empty($cart_items_details)) {
                     // Cart truly empty now
                     $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Your cart is empty.'];
                     header('Location: dashboard.php');
                     exit();
                 }


            } else { $error_message = "Error fetching cart details: " . $stmt->error; }
            $stmt->close();
        } else { $error_message = "Error preparing cart details statement: " . $con->error; }
    } else { $error_message = "Database connection error."; }
} else {
    // This case should have been caught by the initial empty cart check, but included for safety
    header('Location: dashboard.php');
    exit();
}


// Retrieve and clear any flash message from previous actions (like cart updates)
$flash_message_data = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);


include 'header.php'; // Include header
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Checkout</h1>

    <?php
    // Display Flash Message if set
    if ($flash_message_data):
    ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash_message_data['type']); ?> alert-dismissible fade show" role="alert">
             <?php echo $flash_message_data['message']; ?>
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

     <?php
    // Display general error if fetching failed
    if (!empty($error_message)):
    ?>
        <div class="alert alert-danger" role="alert">
            Could not load checkout details. <?php echo htmlspecialchars($error_message); ?> Please <a href="cart.php" class="alert-link">return to cart</a>.
        </div>
    <?php else: ?>

    <div class="row g-5">
        <!-- Order Summary Column -->
        <div class="col-md-5 col-lg-4 order-md-last">
            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-primary">Your cart</span>
                <span class="badge bg-primary rounded-pill"><?php echo count($cart_items_details); ?></span>
            </h4>
            <ul class="list-group mb-3 shadow-sm">
                <?php foreach ($cart_items_details as $item): ?>
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                    </div>
                    <span class="text-muted">₹<?php echo number_format($item['subtotal'], 2); ?></span>
                </li>
                 <?php endforeach; ?>

                <li class="list-group-item d-flex justify-content-between">
                    <span class="fw-bold">Total (INR)</span>
                    <strong class="fs-5">₹<?php echo number_format($cart_total, 2); ?></strong>
                </li>
            </ul>
            <p class="text-center text-muted small">Review your order before placing.</p>
        </div>

        <!-- Billing Address Column -->
        <div class="col-md-7 col-lg-8">
            <h4 class="mb-3">Delivery Details</h4>
            <!-- Create place_order.php next to handle this form -->
            <form action="place_order.php" method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="shipping_name" class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shipping_name" name="shipping_name" placeholder="" value="" required>
                        <div class="invalid-feedback">
                            Valid full name is required.
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="shipping_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" placeholder="e.g., 9876543210" required pattern="[0-9]{10}">
                         <div class="invalid-feedback">
                            Please enter a valid 10-digit phone number.
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="shipping_address_line1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shipping_address_line1" name="shipping_address_line1" placeholder="House No, Building, Street" required>
                        <div class="invalid-feedback">
                            Please enter your shipping address.
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="shipping_address_line2" class="form-label">Address Line 2 <span class="text-muted">(Optional)</span></label>
                        <input type="text" class="form-control" id="shipping_address_line2" name="shipping_address_line2" placeholder="Apartment, suite, landmark, etc.">
                    </div>

                    <div class="col-md-6">
                        <label for="shipping_city" class="form-label">City / Town <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shipping_city" name="shipping_city" required>
                        <div class="invalid-feedback">
                            City is required.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="shipping_postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code" required pattern="\d{6}">
                        <div class="invalid-feedback">
                           Valid 6-digit Postal code required.
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Payment</h4>

                <div class="my-3">
                     <div class="form-check">
                        <input id="cod" name="payment_method" type="radio" class="form-check-input" value="COD" checked required>
                        <label class="form-check-label" for="cod">Cash on Delivery (COD)</label>
                    </div>
                     <!-- Add other payment methods later if needed -->
                    <!--
                    <div class="form-check">
                        <input id="online" name="payment_method" type="radio" class="form-check-input" value="Online" required>
                        <label class="form-check-label" for="online">Online Payment (Coming Soon)</label>
                    </div>
                    -->
                </div>

                <hr class="my-4">

                <button class="w-100 btn btn-primary btn-lg" type="submit">Place Your Order</button>
            </form>
        </div>
    </div> <!-- End main row -->

    <?php endif; // End check for error message ?>

</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>

<!-- Optional: Add Bootstrap's validation script for client-side feedback -->
<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(() => {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }

      form.classList.add('was-validated')
    }, false)
  })
})()
</script>