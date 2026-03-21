<div class="sidebar-panel">
    <div class="sidebar-panel-title">Current Time &mdash; <?php echo getFriendlyTimezoneName(SERVER_TIMEZONE); ?></div>
    <span id="jclock1" style="font-variant-numeric:tabular-nums;font-size:1.05rem;font-weight:600;"></span>

<script>
    function updateClock() {
        const clockElement = document.getElementById('jclock1');
        if (!clockElement) return;

        // Get the current time in Eastern Time (ET) with automatic DST handling
        const now = new Date();
        const options = {
            timeZone: '<?php echo SERVER_TIMEZONE; ?>', // Use server timezone setting
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true // 12-hour format with AM/PM
        };

        // Format the time using Intl.DateTimeFormat
        clockElement.textContent = new Intl.DateTimeFormat('en-US', options).format(now);
    }

    // Update clock every second
    setInterval(updateClock, 1000);

    // Run immediately when the page loads
    document.addEventListener("DOMContentLoaded", updateClock);
</script>

</div>

<!-- Countdown Timers -->
<?php if ($firstGameTime !== $cutoffDateTime && !$firstGameExpired): ?>
    <div id="firstGame" class="countdown bg-success p-4 mb-3 rounded text-white">
        <strong>Loading countdown...</strong>
    </div>
<?php endif; ?>

<?php if (!$weekExpired): ?>
    <div id="picksLocked" class="countdown bg-danger p-4 mb-3 rounded text-white">
        <strong>Loading countdown...</strong>
    </div>
<?php endif; ?>

<?php
$weekStats = [];
$playerTotals = [];
$possibleScoreTotal = 0;
calculateStats();

function displayLeaders($playerTotals, $metric, $limit) {
    global $possibleScoreTotal;

    if (empty($playerTotals)) {
        return;
    }

    // Create a copy of array for sorting
    $sorted = $playerTotals;

    // Sort the player totals
    if ($metric == 'score') {
        usort($sorted, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
    } else {
        usort($sorted, function($a, $b) {
            return $b['wins'] <=> $a['wins'];
        });
    }

    echo '<div class="sidebar-panel">';
    echo '<div class="sidebar-panel-title">Leaders &mdash; ' . ($metric === 'score' ? 'Pick Ratio' : 'Wins') . '</div>';
    
    $rank = 1;
    $lastValue = null;
    $displayed = 0;

    foreach ($sorted as $stats) {
        $value = $stats[$metric] ?? 0;
        
        // Skip if no value
        if ($value == 0) {
            continue;
        }

        // Only increment rank if value is different from previous
        if ($lastValue !== null && $value < $lastValue) {
            $rank = $displayed + 1;
        }

        // Break if we've shown enough
        if ($displayed >= $limit) {
            break;
        }

        if ($metric === 'wins') {
            // Handle wins display
            echo '<div>' . $rank . '. ' . htmlspecialchars($stats['name']) . ' - ' . 
                 $value . ' ' . ($value === 1 ? 'win' : 'wins') . '</div>';
        } else {
            // Handle pick ratio display
            $percentage = number_format(($value / $possibleScoreTotal) * 100, 2);
            echo '<div>' . $rank . '. ' . htmlspecialchars($stats['name']) . ' - ' . 
                 $value . '/' . $possibleScoreTotal . ' (' . $percentage . '%)</div>';
        }
        
        $lastValue = $value;
        $displayed++;
    }
    echo '</div>';
}

// Then in column_right.php use them:
displayLeaders($playerTotals, 'wins', DISPLAY_TOP_WINNERS);
displayLeaders($playerTotals, 'score', DISPLAY_TOP_RATIOS);
?>

<!-- JavaScript Countdown Timer -->
<script>
   function parseDateFromPHP(dateString) {
       const dateObj = new Date(dateString);
       if (isNaN(dateObj.getTime())) {
           console.error("Invalid Date from PHP:", dateString);
           return null;
       }
       return dateObj;
   }

   function startCountdown(elementId, targetTime, serverTime) {
       // Calculate the offset once when starting the countdown
       const initialTimeOffset = new Date().getTime() - serverTime.getTime();

       function updateCountdown() {
           const now = new Date();
           // Use the initial time offset instead of recalculating
           const adjustedNow = new Date(now.getTime() - initialTimeOffset);
           const diff = targetTime - adjustedNow;
           const element = document.getElementById(elementId);

           if (!element) {
               console.error(`Element with ID '${elementId}' not found.`);
               clearInterval(interval);
               return;
           }

           if (diff <= 0) {
               element.innerHTML = `<div class="countdown-title">Time Expired</div>`;
               clearInterval(interval);
               return;
           }

           const days = Math.floor(diff / (1000 * 60 * 60 * 24));
           const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
           const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
           const seconds = Math.floor((diff % (1000 * 60)) / 1000);

           if (!element.querySelector('.countdown-title')) {
               const title = elementId === 'firstGame' ? 'Until first game is locked' : 'Until week is locked';
               element.innerHTML = `
                   <div class="countdown-title">${title}</div>
                   <div class="countdown-timer">
                       <span class="countdown-number" id="${elementId}-days"></span>
                       <span class="countdown-number" id="${elementId}-hours"></span>
                       <span class="countdown-number" id="${elementId}-minutes"></span>
                       <span class="countdown-number" id="${elementId}-seconds"></span>
                   </div>
               `;
           }

           document.getElementById(`${elementId}-days`).textContent = `${days}d`;
           document.getElementById(`${elementId}-hours`).textContent = `${hours}h`;
           document.getElementById(`${elementId}-minutes`).textContent = `${minutes}m`;
           document.getElementById(`${elementId}-seconds`).textContent = `${seconds}s`;
       }

       let interval;
       updateCountdown();
       interval = setInterval(updateCountdown, 1000);
   }

   document.addEventListener("DOMContentLoaded", function () {
       // Get the server's current time in UTC and adjust the client time accordingly
       const serverTime = new Date("<?php echo gmdate('Y-m-d H:i:s'); ?> UTC");

       <?php if ($firstGameTime !== $cutoffDateTime && !$firstGameExpired): ?>
           const firstGameTime = parseDateFromPHP("<?php echo date('Y-m-d H:i:s', strtotime($firstGameTime)); ?>");
           if (firstGameTime) startCountdown("firstGame", firstGameTime, serverTime);
       <?php endif; ?>

       <?php if (!$weekExpired): ?>
           const picksLockedTime = parseDateFromPHP("<?php echo date('Y-m-d H:i:s', strtotime($cutoffDateTime)); ?>");
           if (picksLockedTime) startCountdown("picksLocked", picksLockedTime, serverTime);
       <?php endif; ?>
   });
</script>
