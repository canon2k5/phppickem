<?php
require_once('includes/application_top.php');

// Only show this page if donations are enabled
if (!isset($config['show_donation_button']) || !$config['show_donation_button']) {
    header('Location: index.php');
    exit;
}

require('includes/header.php');
?>

<div class="container py-4">
    <!-- Your beautiful donation card from before -->
<div class="card shadow-sm mx-auto donate-card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-heart text-warning me-2" viewBox="0 0 16 16">
                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
            </svg>
            <h5 class="card-title mb-0">Support This Project</h5>
        </div>

        <form action="https://www.paypal.com/donate" method="post" target="_top">
            <input type="hidden" name="business" value="<?php echo htmlspecialchars($config['paypal']['business_email'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($config['paypal']['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="item_number" value="<?php echo htmlspecialchars($config['paypal']['item_number'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($config['paypal']['currency_code'], ENT_QUOTES, 'UTF-8'); ?>">

            <div class="input-group mb-3">
                <span class="input-group-text">$</span>
                <input type="number" 
                       name="amount" 
                       class="form-control"
                       placeholder="Enter amount" 
                       min="<?php echo htmlspecialchars($config['paypal']['min_amount'], ENT_QUOTES, 'UTF-8'); ?>"
                       step="any" 
                       required>
            </div>

            <button type="submit" class="btn btn-warning w-100 mb-2">
                <i class="fa-brands fa-paypal me-1"></i> Donate Now via PayPal
            </button>

            <p class="text-center small mb-2">
                <i class="fa-solid fa-lock text-success me-1"></i>
                <span class="text-muted">Secure checkout powered by</span>
                <span style="color:#003087;font-weight:700;">Pay</span><span style="color:#009cde;font-weight:700;">Pal</span>
            </p>

            <p class="text-muted text-center small mb-0">
                Thanks for keeping the lights on. 🏈
            </p>
        </form>
    </div>
</div>
	<!-- Previous donation form code here -->
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
