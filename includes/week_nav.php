<?php
// Get the current page name to adjust the target dynamically
$current_page = basename($_SERVER['PHP_SELF']);

// Week navigation query
$sql = "SELECT DISTINCT weekNum FROM " . DB_PREFIX . "schedule ORDER BY weekNum";
$query = $mysqli->query($sql);

// Start with a container that provides better spacing and organization
 echo '<div class="week-navigation mb-4">';
    // Title row with collapsible functionality for mobile
    echo '<div class="d-flex align-items-center justify-content-between mb-2">';
        echo '<h5 class="mb-0">Week Selection</h5>';

        echo '<button class="btn btn-link d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#weekNavCollapse" aria-expanded="false" aria-controls="weekNavCollapse">';
            echo '<i class="fas fa-chevron-down"></i>';
        echo '</button>';
 echo '</div>';
    
    // Collapsible content
    echo '<div class="collapse show" id="weekNavCollapse">';
        echo '<div class="week-buttons d-flex flex-wrap justify-content-start align-items-center gap-2">';
            
            while ($row = $query->fetch_assoc()) {
                $rowWeek = (int)$row['weekNum'];
                $isActive = ($week === $rowWeek) ? ' active' : '';
                
                // Enhanced button styling with better spacing and visual hierarchy
                echo sprintf(
                    '<a href="%s?week=%d" class="btn %s">%d</a>',
                    $current_page,
                    $rowWeek,
                    $isActive ? 'btn-primary' : 'btn-outline-primary',
                    $rowWeek
                );
            }
            
        echo '</div>';
    echo '</div>';
echo '</div>';

$query->free_result();

// Add required JavaScript for the collapse functionality
?>

<script>
// Add smooth collapse animation for mobile
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Add smooth scrolling when week is selected on mobile
    const weekButtons = document.querySelectorAll('.week-buttons .btn');
    weekButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                const collapse = document.getElementById('weekNavCollapse');
                if (collapse.classList.contains('show')) {
                    bootstrap.Collapse.getInstance(collapse).hide();
                }
            }
        });
    });
});
</script>
