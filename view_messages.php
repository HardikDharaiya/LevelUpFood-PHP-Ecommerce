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

$pageTitle = "Contact Messages - Admin";

$error_message = '';
$success_message = '';

// --- Handle POST Actions (Toggle Read Status / Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Toggle Read/Unread Status ---
    if (isset($_POST['toggle_read_status']) && isset($_POST['message_id']) && isset($_POST['new_status'])) {
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $new_status = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT); // Should be 0 or 1

        if ($message_id > 0 && ($new_status === 0 || $new_status === 1)) {
            $stmt_toggle = $con->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
            if ($stmt_toggle) {
                $stmt_toggle->bind_param("ii", $new_status, $message_id);
                if ($stmt_toggle->execute()) {
                    $success_message = "Message status updated successfully.";
                } else {
                    $error_message = "Error updating message status: " . $stmt_toggle->error;
                }
                $stmt_toggle->close();
            } else {
                $error_message = "Error preparing update statement: " . $con->error;
            }
        } else {
             $error_message = "Invalid data for updating status.";
        }
    }

    // --- Delete Message ---
    elseif (isset($_POST['delete_message']) && isset($_POST['message_id'])) {
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

        if ($message_id > 0) {
            $stmt_delete = $con->prepare("DELETE FROM contact_messages WHERE id = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $message_id);
                 if ($stmt_delete->execute()) {
                    if ($stmt_delete->affected_rows > 0) {
                        $success_message = "Message deleted successfully.";
                    } else {
                         $error_message = "Message not found or already deleted.";
                    }
                } else {
                    $error_message = "Error deleting message: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                 $error_message = "Error preparing delete statement: " . $con->error;
            }
        } else {
            $error_message = "Invalid message ID for deletion.";
        }
    }
}


// --- Fetch Messages from Database ---
$messages = [];
$sql = "SELECT id, name, email, message, is_read, submitted_at
        FROM contact_messages
        ORDER BY submitted_at DESC"; // Show newest first

if (isset($con)) {
    $stmt = $con->prepare($sql);
    if ($stmt) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        } else {
            $error_message .= " Error fetching messages: " . $stmt->error; // Append error
            error_log("DB Error fetching contact messages: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $error_message .= " Error preparing message statement: " . $con->error; // Append error
        error_log("DB Prepare Error fetching messages: " . $con->error);
    }
} else {
     $error_message .= " Database connection not available."; // Append error
     error_log("DB connection error in view_messages.php");
}
// --- End Fetch Messages ---

include 'header.php';
?>

<div class="container section">
    <h1 class="section-title text-center mb-4">Contact Form Messages</h1>

    <?php
    // Display Success or Error Messages from POST actions
    if (!empty($success_message)) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($success_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    if (!empty($error_message)) {
        // Display any general fetch errors or POST action errors
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars(trim($error_message)) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <!-- Display Messages -->
    <div class="list-group">
        <?php if (empty($messages) && empty($error_message)): ?>
            <div class="list-group-item">
                 <p class="text-center text-muted mb-0">No contact messages found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="list-group-item <?php echo $msg['is_read'] ? 'list-group-item-light' : 'list-group-item-warning'; ?> mb-2 shadow-sm">
                     <div class="d-flex w-100 justify-content-between mb-1">
                        <h5 class="mb-1">
                            <?php echo htmlspecialchars($msg['name']); ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($msg['email']); ?>)</small>
                             <?php if (!$msg['is_read']): ?>
                                <span class="badge bg-warning text-dark ms-2">New</span>
                             <?php endif; ?>
                        </h5>
                        <small title="<?php echo htmlspecialchars($msg['submitted_at']); ?>">
                            <?php echo date("d M Y, g:i A", strtotime($msg['submitted_at'])); ?>
                        </small>
                    </div>
                    <p class="mb-2 message-content"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <div class="message-actions text-end">
                        <!-- Toggle Read Status Form -->
                        <form action="view_messages.php" method="POST" style="display: inline;">
                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $msg['is_read'] ? '0' : '1'; ?>">
                            <button type="submit" name="toggle_read_status" class="btn btn-sm <?php echo $msg['is_read'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $msg['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>">
                                <?php if ($msg['is_read']): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-open-fill me-1" viewBox="0 0 16 16"><path d="M8.941.435a2 2 0 0 0-1.882 0l-6 3.2A2 2 0 0 0 0 5.4v.314l6.709 3.932L8 8.928l1.291.718L16 5.714V5.4a2 2 0 0 0-1.059-1.765zM16 6.873l-5.693 3.337L16 13.372v-6.5zM0 13.373l5.693-3.163L0 6.873zm.059 2.311A2 2 0 0 0 2 16h12a2 2 0 0 0 1.941-1.316l-7.47-4.124z"/></svg> Mark Unread
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-check-fill me-1" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.026A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.026L8 9.586zm3.436-.586L16 11.801V4.697z"/><path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-1.993-1.679a.5.5 0 0 0-.686.172l-1.17 1.95-.547-.547a.5.5 0 0 0-.708.708l.774.773a.75.75 0 0 0 1.174-.144l1.335-2.226a.5.5 0 0 0-.172-.686"/></svg> Mark Read
                                <?php endif; ?>
                            </button>
                        </form>
                        <!-- Delete Form -->
                        <form action="view_messages.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message permanently?');">
                             <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                             <button type="submit" name="delete_message" class="btn btn-sm btn-danger" title="Delete Message">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/></svg> Delete
                             </button>
                         </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div> <!-- End list-group -->

</div> <!-- End container -->

<?php
include 'footar.php';
// Close connection if it's still open
if (isset($con) && $con instanceof mysqli) {
    $con->close();
}
?>