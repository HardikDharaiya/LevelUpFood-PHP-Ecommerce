<?php
// Start session only if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Include database connection

// --- Access Control: Check if user is logged in and is an admin ---
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['login_error'] = "Access Denied. Admins only.";
    header("Location: login.php");
    exit();
}
// --- End Access Control ---

$pageTitle = "Manage Users - Admin";

// Initialize variables for the form and messages
$user_id = 0;
$user_name = '';
$user_email = '';
$user_role = 'user'; // Default role for new users
$password = ''; // Intentionally blank for security
$confirm_password = ''; // Intentionally blank

$form_action = 'add';
$page_heading = 'Add New User';
$button_text = 'Add User';

$error_message = '';
$success_message = '';

// --- Handle POST Requests (Add/Update/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Delete Action ---
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $delete_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        // Prevent admin from deleting their own account through this form
        if ($delete_id == $_SESSION['id']) {
            $error_message = "Error: You cannot delete your own account.";
        } elseif ($delete_id > 0) {
            $stmt_delete = $con->prepare("DELETE FROM users WHERE id = ?");
            if($stmt_delete) {
                $stmt_delete->bind_param("i", $delete_id);
                if ($stmt_delete->execute()) {
                    // Check if any row was actually deleted
                    if ($stmt_delete->affected_rows > 0) {
                        $success_message = "User deleted successfully.";
                    } else {
                        $error_message = "User not found or already deleted.";
                    }
                } else {
                    $error_message = "Error deleting user: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                 $error_message = "Error preparing delete statement: " . $con->error;
            }
        } else {
            $error_message = "Invalid user ID for deletion.";
        }
    }
    // --- Add/Update Action ---
    elseif (isset($_POST['save_user'])) {
        // Get data from POST
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $user_name = trim($_POST['user_name'] ?? '');
        $user_email = trim($_POST['user_email'] ?? '');
        $user_role = $_POST['user_role'] ?? 'user'; // Default to 'user' if not set
        $password = $_POST['password'] ?? ''; // Don't trim passwords
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate Role
        if (!in_array($user_role, ['user', 'admin'])) {
            $user_role = 'user'; // Force to default if invalid value submitted
        }

        // Basic Server-Side Validation
        if (empty($user_name)) {
            $error_message = "Name is required.";
        } elseif (empty($user_email)) {
            $error_message = "Email is required.";
        } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        }
        // Password validation (only if adding a new user OR if password fields are filled for update)
        elseif ($user_id == 0 && empty($password)) { // Password required for new users
            $error_message = "Password is required for new users.";
        } elseif (!empty($password) && strlen($password) < 6) { // Check length if password is provided
             $error_message = "Password must be at least 6 characters long.";
        } elseif (!empty($password) && $password !== $confirm_password) { // Check confirmation if password is provided
            $error_message = "Passwords do not match.";
        } else {
            // --- Update Logic ---
            if ($user_id > 0) {
                 // Check if the email being set already exists for ANOTHER user
                 $stmt_check_email = $con->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                 if (!$stmt_check_email) {
                      $error_message = "Error preparing email check: " . $con->error;
                 } else {
                    $stmt_check_email->bind_param("si", $user_email, $user_id);
                    $stmt_check_email->execute();
                    $stmt_check_email->store_result();

                    if ($stmt_check_email->num_rows > 0) {
                        $error_message = "Email address is already in use by another user.";
                    }
                    $stmt_check_email->close();
                 }

                // Proceed if email is unique
                 if (empty($error_message)) {
                    // Prepare the base update statement (without password initially)
                    $sql_update = "UPDATE users SET name = ?, email = ?, role = ?";
                    $types = "sss";
                    $params = [$user_name, $user_email, $user_role];

                    // Append password update if password was provided
                    $hashed_password = null;
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $sql_update .= ", password = ?";
                        $types .= "s";
                        $params[] = $hashed_password;
                    }

                    $sql_update .= " WHERE id = ?";
                    $types .= "i";
                    $params[] = $user_id;

                    $stmt_update = $con->prepare($sql_update);
                    if ($stmt_update) {
                        // Dynamically bind parameters
                        $stmt_update->bind_param($types, ...$params); // Use splat operator (...)

                        if ($stmt_update->execute()) {
                            $success_message = "User updated successfully.";
                            // Reset form fields after successful update
                            $user_id = 0; $user_name = ''; $user_email = ''; $user_role = 'user';
                            $password = ''; $confirm_password = '';
                            $form_action = 'add'; $page_heading = 'Add New User'; $button_text = 'Add User';
                        } else {
                            $error_message = "Error updating user: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    } else {
                         $error_message = "Error preparing update statement: " . $con->error;
                    }
                 }
            }
            // --- Add Logic ---
            else {
                // Check if email already exists
                $stmt_check_email = $con->prepare("SELECT id FROM users WHERE email = ?");
                 if (!$stmt_check_email) {
                     $error_message = "Error preparing email check: " . $con->error;
                 } else {
                    $stmt_check_email->bind_param("s", $user_email);
                    $stmt_check_email->execute();
                    $stmt_check_email->store_result();

                    if ($stmt_check_email->num_rows > 0) {
                        $error_message = "Email address is already registered.";
                    }
                    $stmt_check_email->close();
                }

                 // Proceed if email is unique
                 if (empty($error_message)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt_insert = $con->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("ssss", $user_name, $user_email, $hashed_password, $user_role);
                        if ($stmt_insert->execute()) {
                            $success_message = "User added successfully.";
                            // Reset form fields after successful add
                            $user_name = ''; $user_email = ''; $user_role = 'user';
                            $password = ''; $confirm_password = '';
                        } else {
                            $error_message = "Error adding user: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                         $error_message = "Error preparing insert statement: " . $con->error;
                    }
                 }
            }
        }
         // If there was an error, keep the submitted values in the form
         if (!empty($error_message)) {
             // Keep form state and values (password fields are intentionally cleared on error for security)
             $password = '';
             $confirm_password = '';
             if ($user_id > 0) { // Keep form in 'edit' mode if update failed
                 $form_action = 'edit';
                 $page_heading = 'Edit User';
                 $button_text = 'Update User';
             }
         }
    }
}

// --- Handle GET Requests (Edit Loading / Viewing) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($edit_id > 0) {
        // Prevent admin from editing their own account in this simplified form
        // A separate profile page is better for self-editing
        if ($edit_id == $_SESSION['id']) {
            $error_message = "Please use your profile page to edit your own account.";
        } else {
            $stmt_get = $con->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            if ($stmt_get) {
                $stmt_get->bind_param("i", $edit_id);
                $stmt_get->execute();
                $result_get = $stmt_get->get_result();
                if ($user_data = $result_get->fetch_assoc()) {
                    $user_id = $user_data['id'];
                    $user_name = $user_data['name'];
                    $user_email = $user_data['email'];
                    $user_role = $user_data['role'];
                    $form_action = 'edit';
                    $page_heading = 'Edit User';
                    $button_text = 'Update User';
                } else {
                    $error_message = "User not found.";
                }
                $stmt_get->close();
            } else {
                $error_message = "Error preparing select statement: " . $con->error;
            }
        }
    } else {
        $error_message = "Invalid user ID for editing.";
    }
}


// --- Read: Fetch all users for display ---
$users = [];
$stmt_read = $con->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY name ASC");
if ($stmt_read) {
    if ($stmt_read->execute()) {
        $result = $stmt_read->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    } else {
        $error_message = "Error fetching users: " . $stmt_read->error;
    }
    $stmt_read->close();
} else {
     $error_message = "Error preparing read statement: " . $con->error;
}

include 'header.php'; // Include header after all PHP logic
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Manage Users</h1>

    <?php
    // Display Success or Error Messages
    if (!empty($success_message)) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($success_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    if (!empty($error_message)) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($error_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <!-- Add/Edit User Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo htmlspecialchars($page_heading); ?></h5>
        </div>
        <div class="card-body">
            <form action="manage_users.php" method="POST" id="userForm" novalidate> <!-- Added novalidate for JS validation -->
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="user_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="user_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="user_email" name="user_email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <?php echo ($form_action == 'add') ? '<span class="text-danger">*</span>' : '(Leave blank to keep current)'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo ($form_action == 'add') ? 'required' : ''; ?> minlength="6">
                        <div class="form-text"><?php echo ($form_action == 'edit') ? 'Only enter if you want to change the password.' : 'Minimum 6 characters.'; ?></div>
                    </div>
                     <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password <?php echo ($form_action == 'add') ? '<span class="text-danger">*</span>' : ''; ?></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo ($form_action == 'add') ? 'required' : ''; ?> minlength="6" equalTo="#password">
                         <div class="form-text"><?php echo ($form_action == 'edit') ? 'Required only if changing the password.' : ''; ?></div>
                    </div>
                     <div class="col-md-6">
                        <label for="user_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="user_role" name="user_role" required>
                            <option value="user" <?php echo ($user_role == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($user_role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success" name="save_user"><?php echo htmlspecialchars($button_text); ?></button>
                        <?php if ($form_action == 'edit'): ?>
                            <a href="manage_users.php" class="btn btn-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </div> <!-- End row -->
            </form>
        </div>
    </div>

    <!-- User List Table -->
    <h2 class="section-subtitle text-center mt-5">Existing Users</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered On</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No users found. Add one using the form above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo ($user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'); ?>"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span></td>
                            <td><?php echo date("d M Y, H:i", strtotime($user['created_at'])); ?></td>
                            <td class="text-center">
                                <?php if ($user['id'] != $_SESSION['id']): // Prevent actions on self ?>
                                    <!-- Edit Link -->
                                    <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning mb-1 me-1" title="Edit User">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/></svg>
                                    </a>
                                    <!-- Delete Form -->
                                    <form action="manage_users.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger mb-1" title="Delete User">
                                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/></svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">(Current Admin)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footar.php'; ?>

<!-- Optional: Add jQuery Validate for client-side form validation -->
<script src="jquery-3.7.1.min.js"></script>
<script src="jquery.validate.js"></script>
<script>
$(document).ready(function() {
    $("#userForm").validate({
        rules: {
            user_name: {
                required: true,
                minlength: 2
            },
            user_email: {
                required: true,
                email: true
            },
            password: {
                // Required only when adding a new user
                 required: function(element) {
                    return $("#user_id").val() == 0; // Check hidden field value
                 },
                minlength: 6
            },
            confirm_password: {
                // Required only if password field is filled
                required: function(element) {
                    return $("#password").val().length > 0;
                },
                minlength: 6,
                equalTo: "#password" // Matches the password field
            },
             user_role: {
                required: true
             }
        },
        messages: {
            user_name: {
                required: "Please enter the user's name.",
                minlength: "Name must be at least 2 characters."
            },
            user_email: {
                required: "Please enter a valid email address.",
                email: "Please enter a valid email format."
            },
            password: {
                required: "Password is required for new users.",
                minlength: "Password must be at least 6 characters long."
            },
            confirm_password: {
                required: "Please confirm the password.",
                minlength: "Password must be at least 6 characters long.",
                equalTo: "Passwords do not match."
            },
             user_role: {
                required: "Please select a role for the user."
            }
        },
        errorElement: "div",
        errorClass: "text-danger",
        errorPlacement: function(error, element) {
            if (element.attr("type") == "checkbox" || element.attr("type") == "radio") {
                 error.insertAfter(element.closest('.form-check'));
             } else {
                 error.insertAfter(element);
             }
        },
         highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        }
        // No submitHandler needed, standard form submission works
    });
});
</script>

<?php
// Close connection at the very end
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>