<?php
// Start session only if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Access Control: Optional - Check if user is logged in (and NOT admin?) ---
// If you want ONLY regular users to see this, uncomment the role check.
// If admins can also see it, just check for $_SESSION['id'].
if (!isset($_SESSION['id'])) { // || $_SESSION['role'] !== 'user') {
    $_SESSION['login_error'] = "Please log in to view special offers.";
    header('Location: login.php');
    exit();
}
// --- End Access Control ---


$pageTitle = "Special Offer - LevelUpFood";
include 'header.php';
?>

<div class="section special-offer-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 col-md-7 text-center text-md-start">
                <h1 class="special-offer-heading">
                    Thank you for being a valuable customer to us.
                </h1>
                <p class="special-offer-description">
                    We have a surprise gift for you!
                </p>
                 <!-- Button trigger modal -->
                 <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#giftModal">
                    Redeem Gift
                 </button>
            </div>
            <div class="col-12 col-md-5 text-center mt-4 mt-md-0">
                 <img src="https://d1tgh8fmlzexmh.cloudfront.net/ccbp-responsive-website/thanking-customers-section-img.png" class="img-fluid special-offer-img" alt="Gift Box"/>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="giftModal" tabindex="-1" aria-labelledby="giftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                 <h5 class="modal-title special-title" id="giftModalLabel">
                     Your Surprise Gift Voucher!
                 </h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="modal-code">LEVELUP15</p> <!-- Example Code -->
                <p class="text-center">Use this code on your next order for a special discount!</p>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                     Close
                 </button>
            </div>
        </div>
    </div>
</div>


<?php include 'footar.php'; ?>