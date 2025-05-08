<?php
// Start session at the very beginning

$pageTitle = "Register - LevelUpFood";
include 'header.php'; // Includes session_start() again, which is harmless but redundant
?>

<div class="container section">
     <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="section-title text-center mb-4">Register</h1>

            <?php
            // Display potential errors or success messages from registerprocess.php
            if(isset($_SESSION['signup_error']) && !empty($_SESSION['signup_error'])){
                // Use htmlspecialchars to prevent XSS if error message contains user input potentially
                echo '<div class="alert alert-danger" role="alert">'.htmlspecialchars($_SESSION['signup_error']).'</div>';
                unset($_SESSION['signup_error']);
            }
            if(isset($_SESSION['signup_success']) && !empty($_SESSION['signup_success'])){
                 // Use htmlspecialchars for success message too, just in case
                 // Note: The success message allows HTML ('<a href...>'), so we don't escape it here.
                 // Be careful if the success message could ever contain user-generated content.
                echo '<div class="alert alert-success" role="alert">'.$_SESSION['signup_success'].'</div>';
                unset($_SESSION['signup_success']);
                // Optional: You might want to hide the form after successful registration
                // echo '</div></div></div>'; // Close divs
                // include 'footar.php';
                // exit(); // Stop further execution
            }
            ?>

            <!-- Add novalidate attribute to disable default browser validation -->
            <form action="registerprocess.php" method="POST" id="registrationform" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required minlength="2">
                    <!-- Validation message will be inserted here -->
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <!-- Validation message will be inserted here -->
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    <!-- Validation message will be inserted here -->
                </div>
                <div class="mb-3">
                    <label for="confirmpassword" class="form-label">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" required minlength="6" equalTo="#password">
                    <!-- Validation message will be inserted here -->
                </div>
                <button type="submit" class="btn btn-secondary w-100 mb-3" name="signup">Register</button>
                 <div class="text-center">
                     <p class="mb-1">Already have an account?</p>
                    <a href="login.php">Login now</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footar.php'; ?>

<!-- Validation Scripts -->
<script src="jquery-3.7.1.min.js"></script>
<script src="jquery.validate.js"></script>

<script>
    // Wait for the document to be fully loaded
    $(document).ready(function() {
        // Initialize validation on the registration form
        $("#registrationform").validate({
            // Define validation rules
            rules: {
                name: {
                    required: true,
                    minlength: 2 // Name must be at least 2 characters
                },
                email: {
                    required: true,
                    email: true // Must be a valid email format
                },
                password: {
                    required: true,
                    minlength: 6 // Password must be at least 6 characters
                },
                confirmpassword: {
                    required: true,
                    minlength: 6, // Confirm password must be at least 6 characters
                    equalTo: "#password" // Must match the value in the 'password' field
                }
            },
            // Define validation error messages
            messages: {
                name: {
                    required: "Please enter your full name.",
                    minlength: "Your name must be at least 2 characters long."
                },
                email: {
                    required: "Please enter your email address.",
                    email: "Please enter a valid email address."
                },
                password: {
                    required: "Please provide a password.",
                    minlength: "Your password must be at least 6 characters long."
                },
                confirmpassword: {
                    required: "Please confirm your password.",
                    minlength: "Your password must be at least 6 characters long.",
                    equalTo: "Passwords do not match. Please re-enter."
                }
            },
             // Specify where errors should appear and how they should look
            errorElement: "div", // Use a div element for the error message
            errorClass: "text-danger", // Apply Bootstrap's text-danger class for styling
            errorPlacement: function(error, element) {
                 // Place the error message immediately after the form element
                 error.insertAfter(element);
            },
            // Highlight and Unhighlight functions for Bootstrap 5 integration
             highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid').removeClass('is-valid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid').addClass('is-valid');
            },
            // Function to handle form submission if validation passes
            submitHandler: function(form) {
                // If the form is valid, submit it
                form.submit();
            }
        });
    });
</script>