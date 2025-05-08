<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Need DB connection to get item details

// --- Access Control: Check if user is logged in ---
// This block redirects non-logged-in users to the login page.
if (!isset($_SESSION['id'])) {
    // Set a message to show on the login page
    $_SESSION['login_error'] = "Please log in to view your cart.";
    // Store the page the user was trying to access
    $_SESSION['redirect_url'] = 'cart.php';
    // Redirect to the login page
    header('Location: login.php');
    // Stop script execution immediately after redirection
    exit();
}
// --- End Access Control ---


$pageTitle = "Your Shopping Cart - LevelUpFood";

// Initialize cart variables
$cart_items_details = []; // Will hold detailed info fetched from DB
$cart_total = 0.00;
$error_message = '';
$success_message = ''; // For update/remove actions

// --- Handle POST Actions (Update Quantity, Remove Item, Clear Cart) ---
// (POST handling logic remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['cart'])) {

    // --- Update Quantity ---
    if (isset($_POST['update_quantity']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($product_id && isset($_SESSION['cart'][$product_id])) {
            if ($quantity !== false && $quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cart quantity updated.'];
            } elseif ($quantity !== false && $quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
                 $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Item removed from cart.'];
            } else {
                 $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid quantity specified.'];
            }
        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid item specified for update.'];
        }
        header("Location: cart.php");
        exit();
    }

    // --- Remove Item ---
    elseif (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($product_id && isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
             $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Item removed from cart.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid item specified for removal.'];
        }
        header("Location: cart.php");
        exit();
    }

     // --- Clear Cart ---
     elseif (isset($_POST['clear_cart'])) {
        unset($_SESSION['cart']);
        $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Your shopping cart has been cleared.'];
        header("Location: cart.php");
        exit();
    }
}

// --- Fetch Detailed Cart Item Information ---
// (Fetch logic remains the same)
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
                     foreach ($db_items as $item) { $found_items_db[$item['id']] = $item; }

                    foreach ($_SESSION['cart'] as $id => $quantity) {
                        if (isset($found_items_db[$id])) {
                            $item_data = $found_items_db[$id];
                            $subtotal = $item_data['price'] * $quantity;
                            $cart_items_details[] = [
                                'id' => $id, 'name' => $item_data['name'], 'price' => $item_data['price'],
                                'image_url' => $item_data['image_url'], 'quantity' => $quantity, 'subtotal' => $subtotal ];
                            $cart_total += $subtotal;
                        } else {
                            unset($_SESSION['cart'][$id]);
                            $error_message .= " Item ID $id was removed as it's no longer available.";
                        }
                    }
                     if (!empty($error_message) && empty($_SESSION['flash_message'])) {
                         $_SESSION['flash_message'] = ['type' => 'warning', 'message' => trim($error_message)];
                         header("Location: cart.php"); exit();
                     }
                     if (empty($cart_items_details) && !empty($_SESSION['cart'])) { // Double check if cart became empty
                         $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'All items previously in your cart are currently unavailable.'];
                         header("Location: dashboard.php"); exit(); // Redirect to menu if now empty
                     }

                } else { $error_message = "Error fetching cart details: " . $stmt->error; }
                $stmt->close();
            } else { $error_message = "Error preparing cart details statement: " . $con->error; }
        } else { $error_message = "Database connection error."; }
    }
}

// Retrieve and clear flash message (needs to be done AFTER potential redirects)
$flash_message_data = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

include 'header.php'; // Include header
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Your Shopping Cart</h1>

    <?php
    // Display Flash Message if set
    if ($flash_message_data):
    ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash_message_data['type']); ?> alert-dismissible fade show" role="alert">
             <?php echo $flash_message_data['message']; // Allow basic HTML maybe, otherwise escape ?>
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php
    // Display general error if fetching failed
    if (!empty($error_message) && empty($cart_items_details)):
    ?>
        <div class="alert alert-danger" role="alert">
            Could not load cart details. Please try again later. <br>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php
    // Display message if cart is empty (after potential removals or if never had items)
    // Adjusted the condition slightly to be more robust
    elseif (empty($_SESSION['cart']) || empty($cart_items_details)):
    ?>
         <div class="alert alert-info text-center" role="alert">
            Your shopping cart is currently empty. <br>
            <a href="dashboard.php" class="alert-link">Continue Shopping</a>
        </div>
    <?php
    // Display cart table if items exist
    else:
    ?>
        <!-- Cart table and actions HTML remains the same -->
        <div class="table-responsive shadow-sm mb-4">
            <table class="table table-bordered table-hover align-middle">
                 <thead class="table-light">
                    <tr>
                        <th colspan="2">Product</th>
                        <th class="text-center">Price</th>
                        <th class="text-center" style="min-width: 130px;">Quantity</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items_details as $item): ?>
                        <tr>
                            <td style="width: 80px;">
                                <?php
                                    $image_path = (!empty($item['image_url']) && file_exists($item['image_url']))
                                                  ? htmlspecialchars($item['image_url']) . '?t=' . time()
                                                  : 'Images/menu/placeholder.png';
                                    $alt_text = !empty($item['image_url']) ? htmlspecialchars($item['name']) : "Placeholder";
                                ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo $alt_text; ?>" class="img-thumbnail" style="width: 70px; height: 70px; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-center">₹ <?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-center">
                                <form action="cart.php" method="POST" class="d-inline-flex align-items-center">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="form-control form-control-sm" style="width: 65px;" required>
                                    <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-secondary ms-2" title="Update Quantity"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg></button>
                                </form>
                            </td>
                            <td class="text-end">₹ <?php echo number_format($item['subtotal'], 2); ?></td>
                            <td class="text-center">
                                <form action="cart.php" method="POST" onsubmit="return confirm('Remove this item from your cart?');">
                                     <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                     <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger" title="Remove Item"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16"><path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/></svg></button>
                                 </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                        <td class="text-end fw-bold fs-5">₹ <?php echo number_format($cart_total, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

         <!-- Cart Actions -->
        <div class="d-flex justify-content-between align-items-center flex-wrap">
             <a href="dashboard.php" class="btn btn-outline-secondary mb-2"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
             <form action="cart.php" method="POST" onsubmit="return confirm('Are you sure you want to empty your entire cart?');" class="mb-2">
                 <button type="submit" name="clear_cart" class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-x-fill me-1" viewBox="0 0 16 16"><path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m-2.97-5.97a.75.75 0 0 0-1.06-1.06L8 8.94 6.53 7.47a.75.75 0 0 0-1.06 1.06L6.94 10l-1.47 1.47a.75.75 0 1 0 1.06 1.06L8 11.06l1.47 1.47a.75.75 0 1 0 1.06-1.06L9.06 10z"/></svg> Clear Cart</button>
             </form>
            <a href="checkout.php" class="btn btn-success btn-lg mb-2">Proceed to Checkout<i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    <?php endif; // End check for cart items ?>

</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>