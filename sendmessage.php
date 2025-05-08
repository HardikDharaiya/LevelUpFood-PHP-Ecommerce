<?php
// Start session to store feedback messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Include database connection

// Default redirection location
$redirect_location = 'contact.php';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize and retrieve form data
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

    // Server-side validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "A valid Email is required.";
    }
    if (empty($message)) {
        $errors[] = "Message cannot be empty.";
    }

    // If no validation errors, proceed to insert into database
    if (empty($errors)) {
        if (isset($con)) {
            $stmt = $con->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $message);
                if ($stmt->execute()) {
                    // Success: Set success message in session
                    $_SESSION['contact_feedback'] = [
                        'type' => 'success',
                        'message' => 'Thank you! Your message has been sent successfully.'
                    ];
                } else {
                    // Database execution error
                    $_SESSION['contact_feedback'] = [
                        'type' => 'danger',
                        'message' => 'Error sending message. Please try again later. (DB Error)'
                    ];
                    error_log("Error inserting contact message: " . $stmt->error); // Log the error
                }
                $stmt->close();
            } else {
                 // Database prepare error
                 $_SESSION['contact_feedback'] = [
                     'type' => 'danger',
                     'message' => 'Error sending message. Please try again later. (Prepare Error)'
                 ];
                 error_log("Error preparing contact message statement: " . $con->error); // Log the error
            }
            $con->close();
        } else {
            // Database connection error
             $_SESSION['contact_feedback'] = [
                 'type' => 'danger',
                 'message' => 'Error sending message. Database connection failed.'
             ];
             error_log("Database connection failed in sendmessage.php");
        }
    } else {
        // Validation errors occurred: Store errors in session
        $_SESSION['contact_feedback'] = [
            'type' => 'danger',
            'message' => 'Please fix the following errors:<ul><li>' . implode('</li><li>', $errors) . '</li></ul>',
            'values' => ['name' => $name, 'email' => $email, 'message' => $message] // Store values to repopulate form
        ];
    }

} else {
    // If accessed directly without POST, just redirect
    $_SESSION['contact_feedback'] = [
        'type' => 'warning',
        'message' => 'Invalid request method.'
    ];
}

// Redirect back to the contact page
header("Location: " . $redirect_location);
exit(); // Ensure script stops after redirect
?>