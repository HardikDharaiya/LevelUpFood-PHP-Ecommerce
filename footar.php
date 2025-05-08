</main> <!-- End main content wrapper from header -->

<footer class="footer">
	<div class="container">
		<!-- Removed logo from footer to avoid redundancy, focus on info -->
		<!-- <img src="Images/LevelUpFood_Logo.png" alt="LevelUpFood Logo" class="footer-logo" /> -->

		<p class="footer-email">
			<a href="mailto:orderfood@levelupfood.com">orderfood@levelupfood.com</a>
		</p>
		<p class="footer-address">
			360022 near <span class="footer-address-highlight">RK University</span> Rajkot, Gujarat.
		</p>

		 <!-- Optional: Add footer navigation links here -->
		 <!--
		 <ul class="list-inline footer-links mt-3 mb-3">
			 <li class="list-inline-item"><a href="about.php">About</a></li>
			 <li class="list-inline-item"><a href="contact.php">Contact</a></li>
			 <li class="list-inline-item"><a href="#">Terms</a></li>
			 <li class="list-inline-item"><a href="#">Privacy</a></li>
		 </ul>
		 -->

		 <!-- Optional: Add Social Media Icons -->
		  <!--
		 <div class="footer-social mt-3 mb-4">
			<a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
			<a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
			<a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
		 </div>
		 -->


		<p class="footer-copyright">
			 © <?php echo date("Y"); ?> LevelUpFood. All Rights Reserved.
		</p>
	</div>
</footer>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- jQuery (Required for specific Bootstrap components if used, and validate.js) -->
<!-- Make sure path is correct -->
<script src="jquery-3.7.1.min.js"></script>

<!-- jQuery Validate (Only include on pages that use it e.g., login, register, maybe forms in admin) -->
<?php /*
if (in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])) {
	echo '<script src="jquery.validate.js"></script>';
	// Add page-specific validation init script here or in the respective files
}
*/?>

<!-- Font Awesome for social icons (example) - Add if you uncomment social icons -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" /> -->


<!-- Custom Page-Specific Scripts (Optional) -->
<!-- Placed here, after library scripts -->
<?php
	// Example: include validation init only on specific pages
	if (in_array($currentPage, ['login.php', 'register.php', 'manage_users.php'])) { // Add other pages needing validation
		 // The actual <script> block with $(document).ready()... should be in the respective files (login.php, register.php etc.)
		 // This check is just conceptual if you were loading the main validate script here.
	}
?>
<!-- <script src="scripts.js"></script> -->

</body>
</html>