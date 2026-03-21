<?php
require_once('includes/application_top.php');
require_once('includes/header.php');
?>

<div class="container my-4">
    <h1 class="mb-4"><i class="fa-solid fa-book me-2"></i>Rules / Help</h1>

    <div class="row g-4">

        <!-- Basics -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header card-header-accent">
                    <i class="fa-solid fa-football me-2"></i>Basics
                </div>
                <div class="card-body">
                    <p><?php echo APP_NAME; ?> is simple: pick the winners of each game each week. Fill in the entry form by selecting the outcome of each game.</p>
                    <ul class="mb-0 ps-3">
                        <li>The player with the most correct picks each week earns a win.</li>
                        <li>In the event of a tie, both players receive a win.</li>
                        <li>At the end of the season, the overall winner has the most weekly wins.</li>
                        <li>Tiebreaker: overall pick ratio (correct picks &divide; total picks).</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Making & Changing Entries -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header card-header-accent">
                    <i class="fa-solid fa-clipboard-list me-2"></i>Making &amp; Changing Entries
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fa-solid fa-clock me-1"></i>
                        <strong>All game times are displayed in Eastern Time.</strong>
                    </div>
                    <p>You don't need to pick every game at once. Submit early game picks (Thursday / Saturday) in advance and return later for the rest.</p>

                    <h6 class="fw-bold mt-3 mb-2">
                        <i class="fa-solid fa-lock me-1" style="color:var(--gold)"></i>Lockout Rules
                    </h6>
                    <ul class="mb-3 ps-3">
                        <li>Games lock automatically at their scheduled start time.</li>
                        <li>Early games lock individually at kickoff.</li>
                        <li>All remaining games lock at the first Sunday kickoff.</li>
                    </ul>

                    <h6 class="fw-bold mb-2">
                        <i class="fa-solid fa-triangle-exclamation me-1" style="color:var(--gold)"></i>Important
                    </h6>
                    <ul class="mb-0 ps-3">
                        <li>Picks may be changed until a game locks.</li>
                        <li>Locked picks cannot be changed.</li>
                        <li>A missing pick counts as a loss.</li>
                        <li>Unpicked games on a partial entry count as losses.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Technical Difficulties -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header card-header-accent">
                    <i class="fa-solid fa-circle-question me-2"></i>Technical Difficulties
                </div>
                <div class="card-body d-flex flex-column">
                    <p>If you have trouble accessing the site, logging in, or completing your entry, contact the Administrator for assistance.</p>
                    <p>If picks lock before you can submit, the Administrator may enter them after the fact — provided you communicate your choices in advance.</p>
                    <div class="mt-auto">
                        <a href="mailto:<?php echo htmlspecialchars($adminUser->email); ?>" class="btn btn-outline-success">
                            <i class="fa-solid fa-envelope me-1"></i>Contact Administrator
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Winner -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header card-header-accent">
                    <i class="fa-solid fa-trophy me-2"></i>Winner, Winner Chicken Dinner
                </div>
                <div class="card-body">
                    <p>The overall winner is the player with the most weekly wins at the end of the season.</p>
                    <p class="mb-0">In addition to bragging rights, the winner receives a chicken dinner — courtesy of the remaining participants.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require('includes/footer.php'); ?>
