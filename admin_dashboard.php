<?php
// Start session only if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Access Control: Check if user is logged in and is an admin ---
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['login_error'] = "Access Denied. You must be an admin to view this page.";
    header("Location: login.php");
    exit();
}
// --- End Access Control ---


$pageTitle = "Admin Dashboard - LevelUpFood";
include 'header.php'; // Use the main header (it will show admin nav)
?>

<div class="container section">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 text-center mb-4">Admin Dashboard</h1> <!-- Adjusted heading class -->
            <p class="text-center text-muted mb-5">Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>! Select an option below to manage the website.</p>
        </div>
    </div>

    <div class="row g-4 mt-3 justify-content-center">
         <!-- Manage Orders Card -->
        <div class="col-md-6 col-lg-4"> <!-- Adjusted column size -->
            <div class="card text-center h-100 shadow-sm admin-dashboard-card">
                <div class="card-body d-flex flex-column"> <!-- Flex column for alignment -->
                     <h5 class="card-title"><i class="bi bi-receipt-cutoff me-2"></i>Manage Orders</h5>
                    <p class="card-text flex-grow-1">View customer orders and update their status.</p>
                    <a href="manage_orders.php" class="btn btn-primary mt-auto">Go to Orders</a>
                </div>
            </div>
        </div>
         <!-- Manage Menu Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 shadow-sm admin-dashboard-card">
                <div class="card-body d-flex flex-column">
                     <h5 class="card-title"><i class="bi bi-card-list me-2"></i>Manage Menu Items</h5>
                    <p class="card-text flex-grow-1">Add, edit, or remove food items from the menu.</p>
                    <a href="manage_menu.php" class="btn btn-primary mt-auto">Go to Menu Management</a>
                </div>
            </div>
        </div>
         <!-- Manage Categories Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 shadow-sm admin-dashboard-card">
                 <div class="card-body d-flex flex-column">
                     <h5 class="card-title"><i class="bi bi-tags-fill me-2"></i>Manage Categories</h5>
                    <p class="card-text flex-grow-1">Add, edit, or remove food categories.</p>
                    <a href="manage_categories.php" class="btn btn-primary mt-auto">Go to Category Management</a>
                </div>
            </div>
        </div>
         <!-- Manage Users Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 shadow-sm admin-dashboard-card">
                 <div class="card-body d-flex flex-column">
                     <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Manage Users</h5>
                    <p class="card-text flex-grow-1">View and manage registered user accounts.</p>
                    <a href="manage_users.php" class="btn btn-primary mt-auto">Go to User Management</a>
                </div>
            </div>
        </div>
         <!-- View Messages Card -->
         <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 shadow-sm admin-dashboard-card">
                <div class="card-body d-flex flex-column">
                     <h5 class="card-title"><i class="bi bi-envelope-paper-fill me-2"></i>View Contact Messages</h5>
                    <p class="card-text flex-grow-1">Read messages submitted through the contact form.</p>
                    <a href="view_messages.php" class="btn btn-primary mt-auto">Go to Messages</a>
                </div>
            </div>
        </div>
         <!-- Add more cards as needed -->

    </div>
</div>

<?php include 'footar.php'; // Use the main footer ?>