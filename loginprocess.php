<?php
session_start(); // Ensure session is started
require 'config.php';
$error = ""; // Initialize error variable

// Clear previous login errors
unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Email and Password are required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        if (isset($con)) {
            // Prepare statement to select id, password, and role
            $stmt = $con->prepare("SELECT id, password, role FROM users WHERE email=?");
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Bind result variables
                    $stmt->bind_result($id, $hashed_password, $role);
                    $stmt->fetch();

                    if (password_verify($password, $hashed_password)) {
                        // Regenerate session ID upon login for security
                        session_regenerate_id(true);

                        // Store user data in session
                        $_SESSION['id'] = $id;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role; // Store the role

                        // Redirect based on role
                        if ($role === 'admin') {
                            header("Location: admin_dashboard.php");
                        } else {
                            header("Location: dashboard.php"); // Regular users go to user dashboard (menu)
                        }
                        $stmt->close();
                        $con->close();
                        exit(); // Important: exit after header redirect
                    } else {
                        $error = "Incorrect email or password."; // Generic error message
                    }
                } else {
                    $error = "Incorrect email or password."; // Generic error message
                }
                $stmt->close();
            } else {
                $error = "Database statement preparation error: " . $con->error;
                error_log("DB Prepare Error: " . $con->error); // Log the actual error
            }
        } else {
            $error = "Database connection error!";
            error_log("DB Connection Error: Failed to connect."); // Log connection error
        }
    }
    // Close connection if it was opened and is still active
    if (isset($con) && $con->ping()) {
        $con->close();
    }

    // If we reached here, login failed. Store error in session and redirect back.
    $_SESSION['login_error'] = $error;
    header("Location: login.php");
    exit(); // Important: exit after header redirect

} else {
    // Redirect if accessed directly without POST
    header("Location: login.php");
    exit(); // Important: exit after header redirect
}
?>