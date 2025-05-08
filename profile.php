<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Database connection

// --- Access Control: Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    $_SESSION['login_error'] = "Please log in to view your profile.";
    $_SESSION['redirect_url'] = 'profile.php';
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['id'];
// --- End Access Control ---

$pageTitle = "My Profile - LevelUpFood";

// --- Initialize variables ---
$current_name = '';
$current_email = '';
$profile_error_message = '';
$profile_success_message = '';
$password_error_message = '';
$password_success_message = '';

// --- Fetch Current User Data ---
// (This PHP logic block remains the same as the previous working version)
// --- Start Fetch/Update/Password Logic ---
if (isset($con)) { // Fetch User Data
    $stmt_user = $con->prepare("SELECT name, email FROM users WHERE id = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $user_id);
        if ($stmt_user->execute()) { $result_user = $stmt_user->get_result(); if ($user_data = $result_user->fetch_assoc()) { $current_name = $user_data['name']; $current_email = $user_data['email']; $_SESSION['email'] = $current_email; } else { session_unset(); session_destroy(); header('Location: login.php?error=session_mismatch'); exit(); } $result_user->free();
        } else { $profile_error_message = "Error fetching profile data."; error_log("Profile fetch exec error: ".$stmt_user->error); } $stmt_user->close();
    } else { $profile_error_message = "Error preparing profile statement."; error_log("Profile prepare error: ".$con->error); }
} else { $profile_error_message = "Database connection error."; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) { // Handle Profile Update
    $new_name = trim(filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING)); $new_email = trim(filter_input(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL)); $profile_errors = [];
    if (empty($new_name)) $profile_errors[] = "Name cannot be empty."; if (empty($new_email)) $profile_errors[] = "A valid Email is required.";
    if (empty($profile_errors)) { if ($new_email !== $current_email) { $stmt_check = $con->prepare("SELECT id FROM users WHERE email = ? AND id != ?"); if ($stmt_check) { $stmt_check->bind_param("si", $new_email, $user_id); $stmt_check->execute(); $stmt_check->store_result(); if ($stmt_check->num_rows > 0) { $profile_errors[] = "The new email address is already in use."; } $stmt_check->close(); } else { $profile_errors[] = "Error checking email uniqueness."; } }
        if (empty($profile_errors)) { $stmt_update = $con->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?"); if ($stmt_update) { $stmt_update->bind_param("ssi", $new_name, $new_email, $user_id); if ($stmt_update->execute()) { $profile_success_message = "Profile updated successfully!"; $current_name = $new_name; $current_email = $new_email; $_SESSION['email'] = $new_email; } else { $profile_error_message = "Error updating profile: " . $stmt_update->error; } $stmt_update->close(); } else { $profile_error_message = "Error preparing profile update: " . $con->error; } } }
    if (!empty($profile_errors)) { $profile_error_message = 'Could not update profile:<ul><li>' . implode('</li><li>', $profile_errors) . '</li></ul>'; $current_name = $new_name; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) { // Handle Password Change
    $current_password = $_POST['current_password'] ?? ''; $new_password = $_POST['new_password'] ?? ''; $confirm_new_password = $_POST['confirm_new_password'] ?? ''; $password_errors = [];
    if (empty($current_password)) $password_errors[] = "Current password is required."; if (empty($new_password)) $password_errors[] = "New password is required."; elseif (strlen($new_password) < 6) $password_errors[] = "New password must be at least 6 characters."; if ($new_password !== $confirm_new_password) $password_errors[] = "New passwords do not match.";
    if (empty($password_errors)) { $stmt_pass = $con->prepare("SELECT password FROM users WHERE id = ?"); if ($stmt_pass) { $stmt_pass->bind_param("i", $user_id); $stmt_pass->execute(); $result_pass = $stmt_pass->get_result(); if ($user_pass_data = $result_pass->fetch_assoc()) { if (password_verify($current_password, $user_pass_data['password'])) { $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT); $stmt_update_pass = $con->prepare("UPDATE users SET password = ? WHERE id = ?"); if ($stmt_update_pass) { $stmt_update_pass->bind_param("si", $new_hashed_password, $user_id); if ($stmt_update_pass->execute()) { $password_success_message = "Password changed successfully!"; } else { $password_error_message = "Error updating password: " . $stmt_update_pass->error; } $stmt_update_pass->close(); } else { $password_error_message = "Error preparing password update: " . $con->error; } } else { $password_error_message = "Incorrect current password."; } } else { $password_error_message = "Could not retrieve current user data."; } $result_pass->free(); $stmt_pass->close(); } else { $password_error_message = "Error preparing password check: " . $con->error; } }
    if (!empty($password_errors)) { $password_error_message = 'Could not change password:<ul><li>' . implode('</li><li>', $password_errors) . '</li></ul>'; }
}
// --- End Fetch/Update/Password Logic ---

include 'header.php';
?>

<div class="container section profile-page">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <h1 class="display-6 text-center mb-5">My Profile</h1> <!-- Use display heading class -->

            <!-- Combined Card for better flow, or keep separate if preferred -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4 p-md-5"> <!-- More padding -->

                    <!-- Update Profile Sub-Section -->
                    <h5 class="card-title mb-4">Update Profile Information</h5>
                     <?php if (!empty($profile_success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show small py-2" role="alert"><?php echo htmlspecialchars($profile_success_message); ?><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php endif; ?>
                    <?php if (!empty($profile_error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert"><?php echo $profile_error_message; ?><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" novalidate class="mb-5"> <!-- Add margin bottom -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="user_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo htmlspecialchars($current_name); ?>" required>
                                <div class="form-text">Your public display name.</div>
                            </div>
                             <div class="col-md-6">
                                <label for="user_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="user_email" name="user_email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                                 <div class="form-text">Used for login and communication.</div>
                            </div>
                        </div>
                         <button type="submit" name="update_profile" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-person-check-fill me-1"></i>Update Profile
                        </button>
                    </form>

                    <hr class="my-4"> <!-- Separator -->

                    <!-- Change Password Sub-Section -->
                    <h5 class="card-title mb-4">Change Password</h5>
                     <?php if (!empty($password_success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show small py-2" role="alert"><?php echo htmlspecialchars($password_success_message); ?><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php endif; ?>
                    <?php if (!empty($password_error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert"><?php echo $password_error_message; ?><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php endif; ?>

                     <form action="profile.php" method="POST" novalidate>
                         <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required aria-describedby="currentPassHelp">
                             <div id="currentPassHelp" class="form-text">Required to change your password.</div>
                        </div>
                         <div class="row g-3 mb-3">
                             <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" aria-describedby="newPassHelp">
                                 <div id="newPassHelp" class="form-text">Minimum 6 characters.</div>
                            </div>
                             <div class="col-md-6">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-secondary px-4 py-2">
                            <i class="bi bi-key-fill me-1"></i>Change Password
                        </button>
                    </form>

                </div> <!-- End Card Body -->
            </div> <!-- End Card -->

        </div>
    </div>
</div>


<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>