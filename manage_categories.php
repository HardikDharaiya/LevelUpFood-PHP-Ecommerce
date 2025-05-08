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

$pageTitle = "Manage Categories - Admin";

// Initialize variables for the form and messages
$category_id = 0; // 0 indicates adding a new category
$category_name = '';
$category_description = '';
$form_action = 'add'; // Default action
$page_heading = 'Add New Category'; // Default heading
$button_text = 'Add Category';

$error_message = '';
$success_message = '';

// --- Handle POST Requests (Add/Update/Delete) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Delete Action ---
    if (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
        $delete_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if ($delete_id > 0) {
            // Before deleting, consider checking if menu items use this category
            // For now, we proceed directly. Use prepared statements for safety.
            $stmt_delete = $con->prepare("DELETE FROM categories WHERE id = ?");
            if($stmt_delete) {
                $stmt_delete->bind_param("i", $delete_id);
                if ($stmt_delete->execute()) {
                    $success_message = "Category deleted successfully.";
                } else {
                    $error_message = "Error deleting category: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                 $error_message = "Error preparing delete statement: " . $con->error;
            }
        } else {
            $error_message = "Invalid category ID for deletion.";
        }
    }
    // --- Add/Update Action ---
    elseif (isset($_POST['save_category'])) {
        // Get data from POST
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT); // Get ID for update
        $category_name = trim($_POST['category_name'] ?? ''); // Use null coalescing operator
        $category_description = trim($_POST['category_description'] ?? '');

        // Basic Server-Side Validation
        if (empty($category_name)) {
            $error_message = "Category Name is required.";
        } else {
            // --- Update Logic ---
            if ($category_id > 0) {
                 $stmt_update = $con->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                 if ($stmt_update) {
                    $stmt_update->bind_param("ssi", $category_name, $category_description, $category_id);
                    if ($stmt_update->execute()) {
                        $success_message = "Category updated successfully.";
                         // Reset form fields after successful update (optional)
                         $category_id = 0;
                         $category_name = '';
                         $category_description = '';
                    } else {
                        // Check for duplicate name error (MySQL error code 1062)
                        if ($con->errno == 1062) {
                             $error_message = "Error updating category: Category name already exists.";
                        } else {
                             $error_message = "Error updating category: " . $stmt_update->error;
                        }
                    }
                    $stmt_update->close();
                 } else {
                    $error_message = "Error preparing update statement: " . $con->error;
                 }
            }
            // --- Add Logic ---
            else {
                $stmt_insert = $con->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                 if ($stmt_insert) {
                    $stmt_insert->bind_param("ss", $category_name, $category_description);
                    if ($stmt_insert->execute()) {
                        $success_message = "Category added successfully.";
                        // Reset form fields after successful add
                        $category_name = '';
                        $category_description = '';
                    } else {
                         // Check for duplicate name error
                        if ($con->errno == 1062) {
                             $error_message = "Error adding category: Category name already exists.";
                        } else {
                            $error_message = "Error adding category: " . $stmt_insert->error;
                        }
                    }
                    $stmt_insert->close();
                } else {
                     $error_message = "Error preparing insert statement: " . $con->error;
                }
            }
        }
         // If there was an error, keep the submitted values in the form
         if (!empty($error_message)) {
            // No need to re-assign $category_name and $category_description as they already hold the submitted values
             if ($category_id > 0) { // Keep form in 'edit' mode if update failed
                 $form_action = 'edit';
                 $page_heading = 'Edit Category';
                 $button_text = 'Update Category';
             }
         }
    }
}

// --- Handle GET Requests (Edit Loading / Viewing) ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($edit_id > 0) {
        $stmt_get = $con->prepare("SELECT id, name, description FROM categories WHERE id = ?");
        if ($stmt_get) {
            $stmt_get->bind_param("i", $edit_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            if ($result_get->num_rows === 1) {
                $category_data = $result_get->fetch_assoc();
                $category_id = $category_data['id'];
                $category_name = $category_data['name'];
                $category_description = $category_data['description'];
                $form_action = 'edit'; // Set form action
                $page_heading = 'Edit Category'; // Change heading
                $button_text = 'Update Category'; // Change button text
            } else {
                $error_message = "Category not found.";
            }
            $stmt_get->close();
        } else {
             $error_message = "Error preparing select statement: " . $con->error;
        }
    } else {
        $error_message = "Invalid category ID for editing.";
    }
}


// --- Read: Fetch all categories for display ---
$categories = []; // Initialize empty array
$stmt_read = $con->prepare("SELECT id, name, description FROM categories ORDER BY name ASC");
if ($stmt_read) {
    if ($stmt_read->execute()) {
        $result = $stmt_read->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        $result->free(); // Free result set
    } else {
        $error_message = "Error fetching categories: " . $stmt_read->error;
    }
    $stmt_read->close();
} else {
    $error_message = "Error preparing read statement: " . $con->error;
}

include 'header.php'; // Include header after all PHP logic
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Manage Food Categories</h1>

    <?php
    // Display Success or Error Messages
    if (!empty($success_message)) {
        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($success_message) . '</div>';
    }
    if (!empty($error_message)) {
        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <!-- Add/Edit Category Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo htmlspecialchars($page_heading); ?></h5>
        </div>
        <div class="card-body">
            <form action="manage_categories.php" method="POST">
                <!-- Hidden field to store category ID for updates -->
                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">

                <div class="mb-3">
                    <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category_description" class="form-label">Description (Optional)</label>
                    <textarea class="form-control" id="category_description" name="category_description" rows="3"><?php echo htmlspecialchars($category_description); ?></textarea>
                </div>
                <button type="submit" class="btn btn-success" name="save_category"><?php echo htmlspecialchars($button_text); ?></button>
                <?php if ($form_action == 'edit'): ?>
                    <a href="manage_categories.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Category List Table -->
     <h2 class="section-subtitle text-center">Existing Categories</h2>
     <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No categories found. Add one using the form above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($category['description'] ?? '')); ?></td>
                            <td class="text-center">
                                <!-- Edit Link -->
                                <a href="manage_categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning mb-1 me-1" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                      <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                      <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                                    </svg> Edit
                                </a>
                                <!-- Delete Form -->
                                <form action="manage_categories.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category? This might affect menu items linked to it.');">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger mb-1" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                                          <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
                                        </svg> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
     </div>
</div>

<?php
include 'footar.php';
// Close connection at the very end
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>