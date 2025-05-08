<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Get and Validate Item ID ---
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$item_details = null;
$error_message = '';

if (!$item_id || $item_id <= 0) {
    $error_message = "Invalid Item ID specified.";
} else {
    // --- Fetch Item Details (Ensure it's available) ---
    if (isset($con)) {
        // Join with categories to get the category name
        $stmt = $con->prepare("SELECT mi.*, c.name as category_name
                               FROM menu_items mi
                               LEFT JOIN categories c ON mi.category_id = c.id
                               WHERE mi.id = ? AND mi.is_available = 1");
        if ($stmt) {
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $item_details = $result->fetch_assoc();
                } else {
                    // Item not found OR it's not available
                    $error_message = "Sorry, the requested item could not be found or is currently unavailable.";
                }
                $result->free();
            } else {
                $error_message = "Error fetching item details.";
                error_log("DB Error fetching item details for ID $item_id: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing item details statement.";
            error_log("DB Prepare Error fetching item details: " . $con->error);
        }
    } else {
        $error_message = "Database connection error.";
        error_log("DB connection error in view_item.php");
    }
}
// --- End Fetching Data ---

// Set page title dynamically
$pageTitle = $item_details ? htmlspecialchars($item_details['name']) . " - LevelUpFood" : "Item Details - LevelUpFood";
include 'header.php';
?>

<div class="container section item-detail-page"> <!-- Added specific class -->

    <?php
    // Display error message if any occurred
    if (!empty($error_message)):
    ?>
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars($error_message); ?> <br>
            <a href="dashboard.php" class="alert-link">Return to Menu</a>
        </div>
    <?php
    // Display item details if found
    elseif ($item_details):
        // Image path logic (same as dashboard)
        $image_path = (!empty($item_details['image_url']) && file_exists($item_details['image_url']))
                      ? htmlspecialchars($item_details['image_url']) . '?t=' . time()
                      : 'Images/menu/placeholder.png';
        $alt_text = !empty($item_details['image_url']) ? htmlspecialchars($item_details['name']) : "Placeholder image";
    ?>
        <div class="row g-4 g-lg-5">
            <!-- Image Column -->
            <div class="col-md-6 text-center">
                <img src="<?php echo $image_path; ?>" alt="<?php echo $alt_text; ?>" class="img-fluid rounded shadow-sm item-detail-image mb-3 mb-md-0">
            </div>

            <!-- Details Column -->
            <div class="col-md-6 d-flex flex-column item-detail-info">
                <?php if (!empty($item_details['category_name'])): ?>
                    <h6 class="text-muted text-uppercase small mb-1"><?php echo htmlspecialchars($item_details['category_name']); ?></h6>
                <?php endif; ?>

                <h1 class="item-detail-title mb-3"><?php echo htmlspecialchars($item_details['name']); ?></h1>

                <p class="item-detail-price fs-3 fw-bold mb-3">₹ <?php echo number_format($item_details['price'], 2); ?></p>

                <div class="item-detail-description mb-4 flex-grow-1">
                    <p><?php echo nl2br(htmlspecialchars($item_details['description'] ?? 'No description available.')); ?></p>
                    <!-- Add more details here if needed, e.g., ingredients, nutritional info -->
                </div>

                <!-- Add to Cart Button -->
                 <div class="mt-auto d-grid gap-2"> <!-- Use d-grid for full width button -->
                    <a href="add_to_cart.php?id=<?php echo $item_details['id']; ?>" class="btn btn-primary btn-lg add-to-cart-button">
                       <i class="bi bi-cart-plus-fill me-2"></i> Add to Cart
                    </a>
                 </div>
            </div>
        </div>

         <!-- Back Button -->
        <div class="text-center mt-5">
             <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Menu</a>
        </div>

    <?php else: ?>
        <!-- Fallback if $item_details is null but no specific $error_message was set -->
        <div class="alert alert-warning text-center" role="alert">
            Could not retrieve item details.
            <a href="dashboard.php" class="alert-link">Return to Menu</a>
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

<!-- Optional: Add Specific CSS for this page -->
<style>
    .item-detail-image {
        max-height: 500px;
        width: auto;
        max-width: 100%;
        object-fit: cover;
    }
    .item-detail-title {
        font-size: 2.2rem; /* Larger title */
        font-weight: 500;
        color: var(--dark-text);
    }
    .item-detail-price {
        color: var(--primary-color); /* Use theme color for price */
    }
    .item-detail-description {
        line-height: 1.6;
        color: var(--medium-text);
    }
    .add-to-cart-button {
         padding: 0.8rem 1.5rem; /* Larger button */
         font-size: 1.1rem;
    }
</style>