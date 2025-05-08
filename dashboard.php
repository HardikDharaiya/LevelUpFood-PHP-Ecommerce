<?php
// Start session only if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection needed now

// --- Access Control: Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    $_SESSION['login_error'] = "Please log in to view the menu.";
    header('Location: login.php');
    exit();
}
// --- End Access Control ---

$pageTitle = "Food Menu - LevelUpFood";

// --- Fetch Available Menu Items ---
$menu_items = [];
$error_message = ''; // To store potential DB errors

// Select only available items, join with category, order by category then name
$sql = "SELECT mi.*, c.name as category_name
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE mi.is_available = 1
        ORDER BY c.name ASC, mi.name ASC";

if (isset($con)) {
    $stmt = $con->prepare($sql);
    if ($stmt) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $menu_items = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        } else {
            $error_message = "Error fetching menu items: " . $stmt->error;
            error_log("DB Error fetching menu items: " . $stmt->error);
        }
        $stmt->close();
    } else {
         $error_message = "Error preparing menu item statement: " . $con->error;
         error_log("DB Prepare Error fetching menu items: " . $con->error);
    }
    // $con->close(); // Keep connection open for footer if needed, close it there.
} else {
    $error_message = "Database connection not available.";
    error_log("DB connection error in dashboard.php");
}
// --- End Fetch Menu Items ---


include 'header.php';
?>

<!-- Dashboard Content: Explore Menu Section -->
<div class="section section-bg-white" id="sectionPagemenu">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h1 class="section-title">Explore Our Menu</h1>
            </div>
        </div>

        <?php
        // Display error if fetching failed
        if (!empty($error_message)):
        ?>
            <div class="alert alert-danger" role="alert">
                Could not load menu items. Please try again later. <br>
                <?php echo htmlspecialchars($error_message); // Show specific error for debugging if needed ?>
            </div>
        <?php
        // Display message if no items are available
        elseif (empty($menu_items)):
        ?>
             <div class="alert alert-info text-center" role="alert">
                No menu items are currently available. Please check back soon!
            </div>
        <?php
        // Display menu items if available
        else:
        ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">

<?php foreach ($menu_items as $item): ?>
    <div class="col">
        <div class="card menu-item-card shadow-sm h-100">
            <?php
                $image_path = (!empty($item['image_url']) && file_exists($item['image_url']))
                              ? htmlspecialchars($item['image_url']) . '?t=' . time()
                              : 'Images/menu/placeholder.png';
                $alt_text = !empty($item['image_url']) ? htmlspecialchars($item['name']) : "Placeholder image";
            ?>
            <!-- Link the image to the detail page -->
            <a href="view_item.php?id=<?php echo $item['id']; ?>">
                <img src="<?php echo $image_path; ?>" class="card-img-top card-image" alt="<?php echo $alt_text; ?>" style="height: 200px; object-fit: cover;">
            </a>
            <div class="card-body d-flex flex-column">
                <!-- Link the title to the detail page -->
                <h2 class="card-title">
                    <a href="view_item.php?id=<?php echo $item['id']; ?>" class="text-decoration-none stretched-link item-title-link">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                </h2>
                <p class="card-text small text-muted flex-grow-1">
                    <?php echo nl2br(htmlspecialchars($item['description'] ?? 'Delicious item.')); ?>
                </p>
                <p class="fw-bold mb-2">₹ <?php echo number_format($item['price'], 2); ?></p>
                <!-- Keep direct add to cart button -->
                <a href="add_to_cart.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary mt-auto add-to-cart-quick">
                    Add to Cart
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
            </div> <!-- End row -->
        <?php endif; // End check for menu items ?>

    </div> <!-- End container -->
</div> <!-- End section -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>