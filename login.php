<?php
// Start session at the very beginning

$pageTitle = "Login - LevelUpFood";
include 'header.php'; // Includes session_start() again, which is harmless but redundant
?>

<div class="container section">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h1 class="section-title text-center mb-4">Login</h1>
             <?php
                // Display errors from loginprocess.php if any
                if(isset($_SESSION['login_error']) && !empty($_SESSION['login_error'])){
                    // Use htmlspecialchars to prevent XSS if error message contains user input potentially
                    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                    unset($_SESSION['login_error']); // Clear the error after displaying
                }
             ?>
             <!-- Add novalidate attribute to disable default browser validation -->
            <form action="loginprocess.php" method="POST" id="loginform" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <!-- Validation message will be inserted here by jQuery Validate -->
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                     <!-- Validation message will be inserted here by jQuery Validate -->
                </div>
                <button type="submit" class="btn btn-secondary w-100 mb-3" name="login">Login</button>
                <div class="text-center">
                    <p class="mb-1">Don't have an account?</p>
                    <a href="register.php">Register now</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footar.php'; ?>

<!-- Validation Scripts -->
<!-- Placed after footar.php's content but before closing body tag is generally better -->
<!-- However, footar.php already includes jQuery, so placing it here is fine too -->
<!-- Ensure jQuery is loaded BEFORE validate.js -->
<script src="jquery-3.7.1.min.js"></script>
<script src="jquery.validate.js"></script>

<script>
    // Wait for the document to be fully loaded
    $(document).ready(function() {
        // Initialize validation on the login form
        $("#loginform").validate({
            // Define validation rules
            rules: {
                email: {
                    required: true, // Email is required
                    email: true     // Must be a valid email format
                },
                password: {
                    required: true // Password is required
                    // minlength: 6 // You can uncomment this if you enforce a min length on login
                }
            },
            // Define validation error messages
            messages: {
                email: {
                    required: "Please enter your email address.",
                    email: "Please enter a valid email address."
                },
                password: {
                    required: "Please enter your password."
                    // minlength: "Password must be at least 6 characters long." // Corresponding message
                }
            },
            // Specify where errors should appear and how they should look
            errorElement: "div", // Use a div element for the error message
            errorClass: "text-danger", // Apply Bootstrap's text-danger class for styling
            errorPlacement: function(error, element) {
                 // Place the error message immediately after the form element
                 error.insertAfter(element);
            },
            // Highlight and Unhighlight functions to add/remove error classes (optional but good for styling)
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid').removeClass('is-valid'); // Add Bootstrap's is-invalid class
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid').addClass('is-valid'); // Add Bootstrap's is-valid class on success
            },
            // Function to handle form submission if validation passes
            submitHandler: function(form) {
                // If the form is valid, submit it
                form.submit();
            }
        });
    });
</script>