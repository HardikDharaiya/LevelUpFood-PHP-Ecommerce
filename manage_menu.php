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

$pageTitle = "Manage Menu - Admin";

// --- Configuration for File Uploads ---
define('UPLOAD_DIR', 'Images/menu/'); // Define the upload directory relative to this script
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB limit

// Initialize variables for the form and messages
$item_id = 0;
$item_name = '';
$item_description = '';
$item_price = '';
$item_category_id = '';
$item_image_url = ''; // Store existing image URL for edit
$item_is_available = 1; // Default to available
$item_is_special = 0;  // Default to not special

$form_action = 'add';
$page_heading = 'Add New Menu Item';
$button_text = 'Add Item';

$error_message = '';
$success_message = '';

// --- Fetch Categories for Dropdown ---
$categories = [];
$stmt_cat = $con->prepare("SELECT id, name FROM categories ORDER BY name ASC");
if ($stmt_cat && $stmt_cat->execute()) {
    $result_cat = $stmt_cat->get_result();
    $categories = $result_cat->fetch_all(MYSQLI_ASSOC);
    $stmt_cat->close();
} else {
    $error_message = "Error fetching categories: " . ($stmt_cat ? $stmt_cat->error : $con->error);
}

// --- Handle POST Requests (Add/Update/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Delete Action ---
    if (isset($_POST['delete_item']) && isset($_POST['item_id'])) {
        $delete_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        if ($delete_id > 0) {
            // 1. Get the image URL before deleting the DB record
            $stmt_get_img = $con->prepare("SELECT image_url FROM menu_items WHERE id = ?");
            $old_image_path = null;
            if($stmt_get_img){
                $stmt_get_img->bind_param("i", $delete_id);
                $stmt_get_img->execute();
                $result_img = $stmt_get_img->get_result();
                if($row_img = $result_img->fetch_assoc()){
                    $old_image_path = $row_img['image_url'];
                }
                $stmt_get_img->close();
            }

            // 2. Delete the database record
            $stmt_delete = $con->prepare("DELETE FROM menu_items WHERE id = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $delete_id);
                if ($stmt_delete->execute()) {
                    $success_message = "Menu item deleted successfully.";
                    // 3. Delete the actual image file if it exists
                    if ($old_image_path && file_exists($old_image_path)) {
                        if (!unlink($old_image_path)) {
                           $error_message .= " (Warning: Could not delete image file: " . htmlspecialchars($old_image_path) . ")";
                        }
                    }
                } else {
                    $error_message = "Error deleting menu item: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                $error_message = "Error preparing delete statement: " . $con->error;
            }
        } else {
            $error_message = "Invalid item ID for deletion.";
        }
    }
    // --- Add/Update Action ---
    elseif (isset($_POST['save_item'])) {
        // Retrieve and sanitize data
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $item_name = trim($_POST['item_name'] ?? '');
        $item_description = trim($_POST['item_description'] ?? '');
        $item_price = filter_input(INPUT_POST, 'item_price', FILTER_VALIDATE_FLOAT);
        $item_category_id = filter_input(INPUT_POST, 'item_category_id', FILTER_VALIDATE_INT);
        $item_is_available = isset($_POST['item_is_available']) ? 1 : 0;
        $item_is_special = isset($_POST['item_is_special']) ? 1 : 0;
        $current_image_url = $_POST['current_image_url'] ?? null; // Get existing image path for update

        // Basic Server-Side Validation
        if (empty($item_name)) {
            $error_message = "Item Name is required.";
        } elseif ($item_price === false || $item_price < 0) {
            $error_message = "Valid Price is required (must be 0 or greater).";
        } elseif (empty($item_category_id) || $item_category_id <= 0) {
            $error_message = "A valid Category must be selected.";
        } else {
            // --- Handle File Upload ---
            $uploaded_image_path = $current_image_url; // Start with the current image path
            $file_upload_error = false;

            if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['item_image'];
                $file_type = mime_content_type($file['tmp_name']); // More reliable type check

                // Validate file type and size
                if (!in_array($file_type, ALLOWED_TYPES)) {
                    $error_message = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
                    $file_upload_error = true;
                } elseif ($file['size'] > MAX_FILE_SIZE) {
                    $error_message = "File is too large (Max: " . (MAX_FILE_SIZE / 1024 / 1024) . " MB).";
                    $file_upload_error = true;
                } else {
                    // Generate a unique filename to prevent overwrites
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid('item_', true) . '.' . strtolower($file_extension);
                    $destination = UPLOAD_DIR . $unique_filename;

                    // Create upload directory if it doesn't exist
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0775, true); // Create recursively with permissions
                    }

                    // Attempt to move the uploaded file
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $uploaded_image_path = $destination; // Set the new path

                        // Delete the old image if updating and a new one was uploaded successfully
                        if ($item_id > 0 && $current_image_url && $current_image_url !== $uploaded_image_path && file_exists($current_image_url)) {
                            unlink($current_image_url); // Attempt to delete old file
                        }
                    } else {
                        $error_message = "Failed to move uploaded file. Check permissions.";
                        $file_upload_error = true;
                        $uploaded_image_path = $current_image_url; // Revert to old path on error
                    }
                }
            } elseif (isset($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
                 // Handle other upload errors
                 $error_message = "File upload error: Code " . $_FILES['item_image']['error'];
                 $file_upload_error = true;
                 $uploaded_image_path = $current_image_url; // Keep old image on error
            }
            // --- End File Upload Handling ---

            // Proceed with DB operation only if file upload didn't fail (or wasn't attempted)
            if (!$file_upload_error && empty($error_message)) {

                // --- Update Logic ---
                if ($item_id > 0) {
                    $stmt_update = $con->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, is_available = ?, is_special = ? WHERE id = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("issdsiii", $item_category_id, $item_name, $item_description, $item_price, $uploaded_image_path, $item_is_available, $item_is_special, $item_id);
                        if ($stmt_update->execute()) {
                            $success_message = "Menu item updated successfully.";
                            // Reset form fields after successful update
                            $item_id = 0; $item_name = ''; $item_description = ''; $item_price = '';
                            $item_category_id = ''; $item_image_url = ''; $item_is_available = 1; $item_is_special = 0;
                            $form_action = 'add'; $page_heading = 'Add New Menu Item'; $button_text = 'Add Item';
                        } else {
                            $error_message = "Error updating menu item: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    } else {
                         $error_message = "Error preparing update statement: " . $con->error;
                    }
                }
                // --- Add Logic ---
                else {
                    $stmt_insert = $con->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, is_special) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("issdsii", $item_category_id, $item_name, $item_description, $item_price, $uploaded_image_path, $item_is_available, $item_is_special);
                        if ($stmt_insert->execute()) {
                            $success_message = "Menu item added successfully.";
                             // Reset form fields after successful add
                             $item_name = ''; $item_description = ''; $item_price = '';
                             $item_category_id = ''; $item_image_url = ''; $item_is_available = 1; $item_is_special = 0;
                        } else {
                            $error_message = "Error adding menu item: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                        $error_message = "Error preparing insert statement: " . $con->error;
                    }
                }
            }
        }
        // If there was an error (validation or file upload), keep the submitted values in the form
        if (!empty($error_message)) {
            // Keep submitted values
            $item_image_url = $current_image_url; // Keep the original image URL if update failed
            if ($item_id > 0) { // Stay in edit mode
                $form_action = 'edit';
                $page_heading = 'Edit Menu Item';
                $button_text = 'Update Item';
            }
        }
    }
}


// --- Handle GET Request (for Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($edit_id > 0) {
        $stmt_get = $con->prepare("SELECT * FROM menu_items WHERE id = ?");
        if ($stmt_get) {
            $stmt_get->bind_param("i", $edit_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            if ($item_data = $result_get->fetch_assoc()) {
                // Populate form variables
                $item_id = $item_data['id'];
                $item_name = $item_data['name'];
                $item_description = $item_data['description'];
                $item_price = $item_data['price'];
                $item_category_id = $item_data['category_id'];
                $item_image_url = $item_data['image_url'];
                $item_is_available = $item_data['is_available'];
                $item_is_special = $item_data['is_special'];

                // Set form state to 'edit'
                $form_action = 'edit';
                $page_heading = 'Edit Menu Item';
                $button_text = 'Update Item';
            } else {
                $error_message = "Menu item not found.";
            }
            $stmt_get->close();
        } else {
             $error_message = "Error preparing select statement: " . $con->error;
        }
    } else {
        $error_message = "Invalid item ID for editing.";
    }
}


// --- Read: Fetch all menu items for display (Join with categories) ---
$menu_items = [];
$sql_read = "SELECT mi.*, c.name as category_name
             FROM menu_items mi
             LEFT JOIN categories c ON mi.category_id = c.id
             ORDER BY c.name ASC, mi.name ASC";
$stmt_read = $con->prepare($sql_read);
if ($stmt_read) {
    if ($stmt_read->execute()) {
        $result = $stmt_read->get_result();
        $menu_items = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    } else {
        $error_message = "Error fetching menu items: " . $stmt_read->error;
    }
    $stmt_read->close();
} else {
     $error_message = "Error preparing read statement: " . $con->error;
}


include 'header.php'; // Include header after all PHP logic
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Manage Menu Items</h1>

    <?php
    // Display Success or Error Messages
    if (!empty($success_message)) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($success_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    if (!empty($error_message)) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($error_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <!-- Add/Edit Menu Item Form -->
    <div class="card shadow-sm mb-4">
         <div class="card-header">
            <h5 class="mb-0"><?php echo htmlspecialchars($page_heading); ?></h5>
        </div>
        <div class="card-body">
            <!-- Use enctype for file uploads -->
            <form action="manage_menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <!-- Store current image path for updates -->
                <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($item_image_url ?? ''); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="item_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="item_category_id" name="item_category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($item_category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="item_price" class="form-label">Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="item_price" name="item_price" value="<?php echo htmlspecialchars($item_price); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="item_image" class="form-label">Image (Optional)</label>
                        <input class="form-control" type="file" id="item_image" name="item_image" accept="image/jpeg, image/png, image/gif, image/webp">
                        <?php if ($form_action == 'edit' && !empty($item_image_url) && file_exists($item_image_url)): ?>
                             <div class="mt-2">
                                <small>Current Image:</small><br>
                                <img src="<?php echo htmlspecialchars($item_image_url); ?>?t=<?php echo time(); // Cache buster ?>" alt="Current Image" height="60" class="img-thumbnail">
                                <small class="d-block">Uploading a new image will replace the current one.</small>
                            </div>
                        <?php elseif ($form_action == 'edit' && !empty($item_image_url)): ?>
                             <div class='mt-2'><small class='text-danger'>Current image file not found (<?php echo htmlspecialchars($item_image_url); ?>). Upload a new one if needed.</small></div>
                        <?php endif; ?>
                    </div>
                     <div class="col-12">
                        <label for="item_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="item_description" name="item_description" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
                    </div>
                     <div class="col-md-6">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" role="switch" id="item_is_available" name="item_is_available" value="1" <?php echo ($item_is_available == 1) ? 'checked' : ''; ?>>
                           <label class="form-check-label" for="item_is_available">Available for Ordering</label>
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" role="switch" id="item_is_special" name="item_is_special" value="1" <?php echo ($item_is_special == 1) ? 'checked' : ''; ?>>
                           <label class="form-check-label" for="item_is_special">Mark as Special/Featured</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success" name="save_item"><?php echo htmlspecialchars($button_text); ?></button>
                        <?php if ($form_action == 'edit'): ?>
                            <a href="manage_menu.php" class="btn btn-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </div> <!-- End row -->
            </form>
        </div>
    </div>


    <!-- Menu Item List Table -->
    <h2 class="section-subtitle text-center mt-5">Existing Menu Items</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (₹)</th>
                    <th>Available</th>
                    <th>Special</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (empty($menu_items)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No menu items found. Add one using the form above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                            <td>
                                <?php if (!empty($item['image_url']) && file_exists($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>?t=<?php echo time(); // Cache buster ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" height="50" width="50" class="img-thumbnail">
                                <?php elseif (!empty($item['image_url'])): ?>
                                    <span class="text-danger small">Missing</span>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['is_available'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                            <td><?php echo $item['is_special'] ? '<span class="badge bg-info">Yes</span>' : '<span class="badge bg-light text-dark">No</span>'; ?></td>
                            <td class="text-center">
                                <!-- Edit Link -->
                                <a href="manage_menu.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning mb-1 me-1" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/></svg>
                                </a>
                                <!-- Delete Form -->
                                <form action="manage_menu.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_item" class="btn btn-sm btn-danger mb-1" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/></svg>
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