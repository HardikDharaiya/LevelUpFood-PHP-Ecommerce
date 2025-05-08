<?php
// Start session only if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine the current page to set the active class
$currentPage = basename($_SERVER['PHP_SELF']);

// --- Calculate Cart Item Count ---
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_item_count = array_sum($_SESSION['cart']);
}
// --- End Cart Item Count ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'LevelUpFood'; ?></title>
    <link rel="icon" type="image/png" href="Images/LevelUpFood_Logo.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons (for cart icon) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts (Optional) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="styles.css">

</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
             <!-- Updated Brand Section -->
             <a class="navbar-brand" href="index.php" title="LevelUpFood Home">
                <img src="Images/LevelUpFood_Logo.png" alt="LevelUpFood Logo" class="navbar-brand-logo" />
                <span class="navbar-brand-text">LevelUpFood</span>
             </a>
             <!-- End Updated Brand Section -->

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Main Nav Links (PHP logic based on role remains same) -->
                     <?php if (!isset($_SESSION['email']) || $_SESSION['email'] == 'unset'): ?>
                        <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>" href="about.php">About us</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'special.php') ? 'active' : ''; ?>" href="special.php">Specials</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact</a></li>
                    <?php else: ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">Admin Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'manage_menu.php') ? 'active' : ''; ?>" href="manage_menu.php">Manage Menu</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'manage_categories.php') ? 'active' : ''; ?>" href="manage_categories.php">Manage Categories</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'manage_users.php') ? 'active' : ''; ?>" href="manage_users.php">Manage Users</a></li>
                             <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'view_messages.php') ? 'active' : ''; ?>" href="view_messages.php">View Messages</a></li>
                             <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'manage_orders.php') ? 'active' : ''; ?>" href="manage_orders.php">Manage Orders</a></li> <!-- Added Manage Orders Link -->
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">Food Menu</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>" href="about.php">About us</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'special.php') ? 'active' : ''; ?>" href="special.php">Specials</a></li>
                             <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- Right Side Nav -->
                <ul class="navbar-nav ms-auto align-items-center">

                    <!-- Cart Link -->
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo ($currentPage == 'cart.php') ? 'active' : ''; ?>" href="cart.php" title="View Shopping Cart">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if ($cart_item_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_item_count; ?>
                                    <span class="visually-hidden">items in cart</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if (!isset($_SESSION['email']) || $_SESSION['email'] == 'unset'): ?>
                         <!-- Login/Register for logged out users -->
                         <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>" href="login.php">Login</a></li>
                         <li class="nav-item"><a class="nav-link <?php echo ($currentPage == 'register.php') ? 'active' : ''; ?>" href="register.php">Register</a></li>
                    <?php else: ?>
                        <!-- Profile Avatar Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="profile-avatar me-2"><?php echo strtoupper(htmlspecialchars($_SESSION['email'][0])); ?></div>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                 <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">Admin Home</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'manage_orders.php') ? 'active' : ''; ?>" href="manage_orders.php">Manage Orders</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>" href="profile.php">My Profile</a></li> <!-- Admins might also have a profile -->
                                <?php else: ?>
                                    <!-- Regular User Dropdown Links -->
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">Menu</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>" href="profile.php">My Profile</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage == 'order_history.php') ? 'active' : ''; ?>" href="order_history.php">Order History</a></li> <!-- ADDED THIS LINE -->
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main>
         <?php
        // Display flash message if it exists in the session
        if (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])):
            $flash_type = $_SESSION['flash_message']['type'] ?? 'info';
            $flash_message = $_SESSION['flash_message']['message'] ?? '';
        ?>
            <div class="container pt-3">
                <div class="alert alert-<?php echo htmlspecialchars($flash_type); ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php
            unset($_SESSION['flash_message']); // Clear flash message
        endif;
        ?>
        <!-- Page content starts after this -->