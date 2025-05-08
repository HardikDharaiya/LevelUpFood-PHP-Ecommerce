<?php
// Start session to access feedback messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Contact Us - LevelUpFood";

// Get feedback and submitted values from session if they exist
$feedback = $_SESSION['contact_feedback'] ?? null;
$form_values = $feedback['values'] ?? ['name' => '', 'email' => '', 'message' => ''];

// Clear the session feedback variable after retrieving it
unset($_SESSION['contact_feedback']);

include 'header.php';
?>

 <div class="container section">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="section-title text-center mb-4">Contact Us</h1>

            <?php
            // Display feedback message if it exists
            if ($feedback):
            ?>
                <div class="alert alert-<?php echo htmlspecialchars($feedback['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo $feedback['message']; // Allow basic HTML like <ul> from error list ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Add novalidate for potential JS validation later -->
            <form action="sendmessage.php" method="post" novalidate>
                <div class="mb-3">
                  <label for="name" class="form-label">Name</label>
                  <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($form_values['name']); ?>">
               </div>
               <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($form_values['email']); ?>">
               </div>
               <div class="mb-3">
                  <label for="message" class="form-label">Message</label>
                  <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($form_values['message']); ?></textarea>
               </div>
               <div class="text-center">
                    <button type="submit" class="btn btn-primary">Send Message</button>
               </div>
            </form>
        </div>
    </div>
</div>

 <?php
 include 'footar.php';
 // Close connection if opened by header/config (though sendmessage.php closes its own)
 if (isset($con) && $con instanceof mysqli) {
    $con->close();
 }
 ?>