<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bankshot Tournament Display</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
            background: #1a1a1a;
        }

        /* Loading screen styles */
        #loadingScreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e7e34 0%, #0d4d1f 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s;
        }

        #loadingScreen.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 2px;
        }

        .split {
            height: 100%;
            position: fixed;
            top: 0;
            overflow: hidden;
        }

        .left {
            left: 0;
            width: 20vw;
            background: linear-gradient(180deg, #1e7e34 0%, #0d4d1f 100%);
            padding: 1.5vh 1vw;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            align-items: center;
            box-shadow: 5px 0 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.5s ease-in-out;
            overflow: hidden;
        }

        .left.hidden {
            transform: translateX(-100%);
        }

        .right {
            right: 0;
            width: 80vw;
            background: #000;
            transition: width 0.5s ease-in-out, left 0.5s ease-in-out;
        }

        .right.fullscreen {
            width: 100vw;
            left: 0;
        }

        .qr-container {
            text-align: center;
            margin: 1vh 0;
            background: white;
            padding: 1.5vh;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            width: 95%;
        }

        .qr-container img {
            width: 100%;
            max-width: 220px;
            border-radius: 8px;
        }

        .qr-label {
            font-family: 'Oswald', sans-serif;
            font-size: 1.6vw;
            color: #1e7e34;
            margin-top: 1vh;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .player-count {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 4.2vw;
            color: #ffffff;
            text-align: center;
            margin: 1.2vh 0;
            font-weight: bold;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5);
            background: rgba(255, 255, 255, 0.15);
            padding: 1.2vh;
            border-radius: 10px;
            width: 100%;
        }

        .entry-fee {
            font-family: 'Oswald', sans-serif;
            font-size: 2vw;
            color: #ffffff;
            text-align: center;
            margin: 0.8vh 0;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .payouts-header {
            font-family: 'Oswald', sans-serif;
            font-size: 2vw;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 1vh 0 0.5vh 0;
            font-weight: 700;
            text-align: center;
            border-bottom: 3px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 0.5vh;
            width: 100%;
        }

        .payouts {
            font-family: 'Roboto', sans-serif;
            font-size: 1.8vw;
            color: #ffffff;
            text-align: center;
            line-height: 1.2;
            width: 100%;
        }

        .payouts div {
            margin: 0.3vh 0;
            padding: 0.6vh;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            font-weight: 600;
        }

        .payouts .first-place {
            background: rgba(255, 215, 0, 0.3);
            font-weight: 700;
            font-size: 2.4vw;
            border: 2px solid rgba(255, 215, 0, 0.6);
        }

        /* Dynamic scaling for many payouts */
        .payouts.compact {
            font-size: 1.5vw;
            line-height: 1.1;
        }

        .payouts.compact div {
            margin: 0.2vh 0;
            padding: 0.4vh;
        }

        .payouts.compact .first-place {
            font-size: 2vw;
        }

        iframe {
            border: none;
            width: 100%;
            height: 100%;
            display: none;
            overflow: hidden;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        iframe.active {
            display: block;
            opacity: 1;
        }

        /* Animated gradient background */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .left {
            background: linear-gradient(180deg, #1e7e34, #0d4d1f, #1e7e34);
            background-size: 200% 200%;
            animation: gradientShift 10s ease infinite;
        }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div id="loadingScreen">
    <div class="spinner"></div>
    <div class="loading-text">LOADING TOURNAMENT...</div>
</div>

<?php 
set_time_limit(60);
require_once 'tournament_payout_calculator.php';

// Helper function to convert class output to display format
function calculatePayouts($player_count, $entry_fee) {
    if ($player_count < 8) {
        return []; // Minimum 8 players
    }
    
    $calculator = new TournamentPayoutCalculator($entry_fee, $player_count);
    $payouts_array = $calculator->getPayoutsArray();
    
    // Convert to format expected by formatPayoutsHTML()
    $formatted_payouts = [];
    foreach ($payouts_array as $place => $amount) {
        $place_label = getPlaceLabel($place);
        $formatted_payouts[] = [
            'place' => $place_label,
            'amount' => '$' . number_format($amount, 2)
        ];
    }
    
    return $formatted_payouts;
}

function getPlaceLabel($place) {
    $labels = [
        1 => '1st',
        2 => '2nd', 
        3 => '3rd',
        4 => '4th',
        5 => '5th-6th',
        6 => '5th-6th',
        7 => '7th-8th',
        8 => '7th-8th',
        9 => '9th-12th',
        10 => '9th-12th',
        11 => '9th-12th',
        12 => '9th-12th'
    ];
    return $labels[$place] ?? $place . 'th';
}

function formatPayoutsHTML($payouts) {
    if (empty($payouts)) {
        return '<div>Calculating...</div>';
    }
    
    $html = '';
    $seen_places = []; // Track which tied places we've already shown
    
    foreach ($payouts as $payout) {
        $place = $payout['place'] ?? '';
        $amount = $payout['amount'] ?? '';
        
        // Skip duplicate tied places (e.g., show "5th-6th" once, not twice)
        if (in_array($place, $seen_places)) {
            continue;
        }
        $seen_places[] = $place;
        
        $class = ($place === '1st') ? 'first-place' : '';
        $html .= "<div class='$class'>$place: $amount</div>\n";
    }
    
    return $html;
}

$tournament_data_file = '/var/www/html/tournament_data.json';
$tournament_found = false;
$tournament_name = '';
$entry_fee = 15;
$player_count = 0;

if (file_exists($tournament_data_file)) {
    $file_contents = file_get_contents($tournament_data_file);
    $tournament_data = json_decode($file_contents, true);
    
    if ($tournament_data && 
        isset($tournament_data['tournament_url']) && 
        !empty($tournament_data['tournament_url']) &&
        isset($tournament_data['display_tournament']) && 
        $tournament_data['display_tournament'] === true) {
        
        $tournament_found = true;
        $tournament_name = $tournament_data['tournament_name'] ?? 'Pool Tournament';
        $entry_fee = $tournament_data['entry_fee'] ?? 15;
        $player_count = $tournament_data['player_count'] ?? 0;
    }
}

if (!$tournament_found) {
    echo "<!-- No tournament found, showing media only -->\n";
}
?>

<!-- GLOBAL SCRIPT - Available to all sections -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

function shouldDisplayMedia(m, now, currentDay, currentTime, currentDate) {
    // Check if media has expired based on end date
    if (m.hasEndDate && m.endDate && currentDate > m.endDate) {
        return false;
    }
    
    // Per-day schedule mode (each day has its own start/end time)
    if (m.usePerDaySchedule && m.daySchedules) {
        var daySchedule = m.daySchedules[currentDay];
        if (!daySchedule || !daySchedule.enabled) {
            return false;
        }
        
        if (daySchedule.startTime && daySchedule.endTime) {
            var startParts = daySchedule.startTime.split(':');
            var endParts = daySchedule.endTime.split(':');
            var startTime = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
            var endTime = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);
            
            // Handle midnight crossover (e.g., 22:00 to 02:00)
            if (endTime < startTime) {
                // Schedule spans midnight - show if AFTER start OR BEFORE end
                // Example: 22:00 to 02:00
                // Show if: currentTime >= 22:00 (1320) OR currentTime <= 02:00 (120)
                // Hide only if: currentTime is between 02:00 and 22:00
                if (currentTime < startTime && currentTime > endTime) {
                    return false;
                }
            } else {
                // Normal schedule - show if BETWEEN start and end
                if (currentTime < startTime || currentTime > endTime) {
                    return false;
                }
            }
        }
        return true;
    }
    
    // Check if current day is in the selected days list
    if (m.scheduleDays && m.scheduleDays.length > 0 && m.scheduleDays.indexOf(currentDay) === -1) {
        return false;
    }
    
    // Simple schedule mode (same time range for all selected days)
    if (m.scheduleStartTime && m.scheduleEndTime) {
        var startParts = m.scheduleStartTime.split(':');
        var endParts = m.scheduleEndTime.split(':');
        var startTime = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
        var endTime = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);
        
        // Handle midnight crossover
        if (endTime < startTime) {
            // Schedule spans midnight
            if (currentTime < startTime && currentTime > endTime) {
                return false;
            }
        } else {
            // Normal schedule
            if (currentTime < startTime || currentTime > endTime) {
                return false;
            }
        }
    }
    
    return true;
}

var Dash = {
    dashboards: [],
    nextIndex: 0,
    mediaItems: [],
    totalCycleDuration: 0,
    cycleStartTime: null,
    
    createIframes: function() {
        var frameContainer = document.getElementById('frameContainer');
        
        for (var index = 0; index < this.dashboards.length; index++) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('id', index.toString());
            iframe.setAttribute('scrolling', 'no');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', 'true');
            frameContainer.appendChild(iframe);
        }
    },
    
    initializeDashboards: function() {
        console.log('Loading media rotation...');
        
        fetch('/load_media.php')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                <?php if ($tournament_found): ?>
                // Tournament active - show 'tournament' media
                var allMediaItems = data.filter(function(m) { 
                    return m.active === true && m.displayOnTournaments === true;
                });
                console.log('Tournament mode: ' + allMediaItems.length + ' tournament media items');
                <?php else: ?>
                // No tournament - show 'ad' media
                var allMediaItems = data.filter(function(m) { 
                    return m.active === true && m.displayOnAds === true;
                });
                console.log('Ad mode: ' + allMediaItems.length + ' ad media items');
                <?php endif; ?>
                
                // Sort by order
                allMediaItems.sort(function(a, b) { return (a.order || 0) - (b.order || 0); });
                
                // Apply schedule filtering
                var now = new Date();
                var currentDay = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][now.getDay()];
                var currentTime = now.getHours() * 60 + now.getMinutes();
                var currentDate = now.toISOString().split('T')[0];
                
                var filteredMedia = allMediaItems.filter(function(m) {
                    return shouldDisplayMedia(m, now, currentDay, currentTime, currentDate);
                });
                
                console.log('After schedule filter: ' + filteredMedia.length + ' items to display');
                
                Dash.mediaItems = filteredMedia;
                
                // Calculate total cycle duration
                Dash.totalCycleDuration = 0;
                for (var i = 0; i < filteredMedia.length; i++) {
                    Dash.totalCycleDuration += (filteredMedia[i].duration || 20);
                }
                console.log('Total media cycle duration: ' + Dash.totalCycleDuration + ' seconds');
                
                // Build dashboards
                for (var i = 0; i < filteredMedia.length; i++) {
                    var mediaItem = filteredMedia[i];
                    
                    if (mediaItem.type === 'url') {
                        Dash.dashboards.push({
                            url: mediaItem.url,
                            time: mediaItem.duration || 20,
                            refresh: true
                        });
                    } else {
                        Dash.dashboards.push({
                            url: 'data:text/html;charset=utf-8,' + encodeURIComponent(Dash.createMediaHTML(mediaItem)),
                            time: mediaItem.duration || 20,
                            refresh: false
                        });
                    }
                }
                
                if (Dash.dashboards.length === 0) {
                    console.warn('No media items to display!');
                }
                
                Dash.continueStartup();
            })
            .catch(function(err) {
                console.error('Error loading media:', err);
                Dash.continueStartup();
            });
    },
    
    continueStartup: function() {
        if (Dash.dashboards.length === 0) {
            console.log('No dashboards to display');
            return;
        }
        
        Dash.createIframes();
        
        for (var index = 0; index < Dash.dashboards.length; index++) {
            Dash.loadFrame(index);
        }
        
        if (Dash.dashboards.length > 0) {
            Dash.cycleStartTime = Date.now();
            Dash.showFrame(0);
            setTimeout(function() {
                Dash.display();
            }, Dash.dashboards[0].time * 1000);
        }
    },
    
    createMediaHTML: function(mediaItem) {
        var mediaSrc = mediaItem.path || mediaItem.data;
        
        if (mediaSrc && mediaSrc.indexOf('/') === 0) {
            mediaSrc = window.location.origin + mediaSrc;
        }
        
        var mediaElement = '';
        if (mediaItem.type === 'image') {
            mediaElement = '<img src="' + mediaSrc + '" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#000;">';
        } else {
            mediaElement = '<video src="' + mediaSrc + '" autoplay muted loop style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#000;"></video>';
        }
        
        return '<!DOCTYPE html><html><head><style>html,body{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:#000;position:relative;}</style></head><body>' + mediaElement + '</body></html>';
    },

    startup: function() {
        Dash.initializeDashboards();
    },

    loadFrame: function(index) {
        var iframe = document.getElementById(index.toString());
        if (iframe) {
            iframe.src = Dash.dashboards[index].url;
        }
    },

    display: function() {
        if (Dash.dashboards.length === 0) return;
        
        var currentDashboard = Dash.dashboards[this.nextIndex];
        
        Dash.hideFrame(this.nextIndex - 1);
        
        if (currentDashboard.refresh) {
            Dash.loadFrame(this.nextIndex);
        }
        
        Dash.showFrame(this.nextIndex);
        
        this.nextIndex = (this.nextIndex + 1) % Dash.dashboards.length;
        
        // Check if we've completed a full cycle
        if (this.nextIndex === 0) {
            var cycleEndTime = Date.now();
            var actualCycleDuration = (cycleEndTime - Dash.cycleStartTime) / 1000;
            console.log('Completed media cycle: ' + actualCycleDuration + ' seconds');
            Dash.cycleStartTime = Date.now(); // Reset cycle timer
        }
        
        setTimeout(function() {
            Dash.display();
        }, currentDashboard.time * 1000);
    },

    hideFrame: function(index) {
        if (index < 0) {
            index = Dash.dashboards.length - 1;
        }
        var frame = $('#' + index);
        frame.animate({opacity: 0}, 500, function() {
            frame.removeClass('active');
        });
    },

    showFrame: function(index) {
        var frame = $('#' + index);
        frame.addClass('active').css({opacity: 0}).animate({opacity: 1}, 500);
    }
};

window.onload = function() {
    Dash.startup();
};
</script>

<?php if ($tournament_found): ?>

<div class="split left <?php echo ($player_count == 0) ? 'hidden' : ''; ?>" id="leftPanel">
    <div class="qr-container">
        <img src="tournament_qr.png?t=<?php echo time(); ?>" alt="Tournament Bracket QR Code">
        <div class="qr-label">Scan for Bracket</div>
    </div>
    
    <div class="player-count" id="playerCount"><?php echo $player_count; ?> PLAYERS</div>
    
    <div class="entry-fee" id="entryFee">
        <?php 
        // Get entry fee label and value
        $fee_label = $tournament_data['entry_fee_label'] ?? 'Entry:';
        $fee_value = $tournament_data['entry_fee'] ?? 15;
        
        // ALWAYS ensure dollar sign is present
        if (is_numeric($fee_value)) {
            $fee_value = '$' . $fee_value;
        } elseif (!str_starts_with($fee_value, '$')) {
            $fee_value = '$' . $fee_value;
        }
        
        echo $fee_label . ' ' . $fee_value;
        ?>
    </div>
    
    <div class="payouts-header">PAYOUTS</div>
    <div class="payouts" id="payouts">
        <?php 
        // Check if Digital Pool has payouts
        $has_digital_pool = isset($tournament_data['has_digital_pool_payouts']) 
                            && $tournament_data['has_digital_pool_payouts'] === true;
        
        if ($has_digital_pool && isset($tournament_data['payouts']) && is_array($tournament_data['payouts'])) {
            // Use Digital Pool payouts from JSON
            $payouts = $tournament_data['payouts'];
        } else {
            // Calculate payouts based on player count and entry fee
            $entry_fee_num = $tournament_data['entry_fee'] ?? 15;
            if (is_string($entry_fee_num)) {
                $entry_fee_num = str_replace('$', '', $entry_fee_num);
            }
            $payouts = calculatePayouts($player_count, $entry_fee_num);
        }
        
        // Display payouts
        echo formatPayoutsHTML($payouts);
        ?>
    </div>
</div>

<div class="split right <?php echo ($player_count == 0) ? 'fullscreen' : ''; ?>" id="frameContainer"></div>

<script>
// ============================================================================
// TOURNAMENT CHANGE DETECTION - Only checks after completing media cycle
// ============================================================================
let lastTournamentState = {
    display: null,
    playerCount: null,
    entryFee: null,
    tournamentUrl: null,
    tournamentName: null,
    status: null,
    hasDigitalPoolPayouts: null,
    initialized: false
};

let lastCheckTime = Date.now();
let minimumCheckInterval = Dash.totalCycleDuration * 1000; // Convert to milliseconds

// Ensure we wait at least one full media cycle before first check
setTimeout(function() {
    console.log('🔍 Starting change detection after initial media cycle');
    checkForChanges();
    
    // Then check every cycle completion
    setInterval(function() {
        var now = Date.now();
        var timeSinceLastCheck = now - lastCheckTime;
        
        // Only check if we've waited at least one full cycle
        if (timeSinceLastCheck >= minimumCheckInterval) {
            checkForChanges();
        }
    }, 10000); // Check every 10 seconds, but respect cycle duration
    
}, Dash.totalCycleDuration * 1000);

function checkForChanges() {
    fetch('/get_tournament_data.php?nocache=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            
            lastCheckTime = Date.now();
            
            // Current state (WITHOUT last_updated)
            const current = {
                display: data.display_tournament || false,
                playerCount: data.player_count || 0,
                entryFee: data.entry_fee || 15,
                tournamentUrl: data.tournament_url || '',
                tournamentName: data.tournament_name || '',
                status: data.status || '',
                hasDigitalPoolPayouts: data.has_digital_pool_payouts || false
            };
            
            // First run - initialize state
            if (!lastTournamentState.initialized) {
                lastTournamentState = {...current, initialized: true};
                console.log('🎯 Initial tournament state:', current);
                updatePlayerData(data);
                return;
            }
            
            // Check for ANY meaningful change
            let changed = false;
            let changeReasons = [];
            
            if (current.display !== lastTournamentState.display) {
                changed = true;
                changeReasons.push(`display: ${lastTournamentState.display} → ${current.display}`);
            }
            
            if (current.playerCount !== lastTournamentState.playerCount) {
                changed = true;
                changeReasons.push(`players: ${lastTournamentState.playerCount} → ${current.playerCount}`);
            }
            
            if (current.entryFee !== lastTournamentState.entryFee) {
                changed = true;
                changeReasons.push(`entry fee: $${lastTournamentState.entryFee} → $${current.entryFee}`);
            }
            
            if (current.tournamentUrl !== lastTournamentState.tournamentUrl) {
                changed = true;
                changeReasons.push('tournament URL changed');
            }
            
            if (current.tournamentName !== lastTournamentState.tournamentName) {
                changed = true;
                changeReasons.push('tournament name changed');
            }
            
            if (current.status !== lastTournamentState.status) {
                changed = true;
                changeReasons.push(`status: ${lastTournamentState.status} → ${current.status}`);
            }
            
            if (current.hasDigitalPoolPayouts !== lastTournamentState.hasDigitalPoolPayouts) {
                changed = true;
                changeReasons.push(`payouts: ${lastTournamentState.hasDigitalPoolPayouts ? 'Digital Pool' : 'calculated'} → ${current.hasDigitalPoolPayouts ? 'Digital Pool' : 'calculated'}`);
            }
            
            if (changed) {
                console.log('🔄 TOURNAMENT DATA CHANGED - RELOADING');
                console.log('Changes:', changeReasons.join(', '));
                console.log('Old:', lastTournamentState);
                console.log('New:', current);
                location.reload();
            } else {
                // No changes, but update display in case of time-based changes
                updatePlayerData(data);
            }
        })
        .catch(err => console.error('❌ Error checking tournament data:', err));
}

function updatePlayerData(data) {
    // Update player count display without reloading
    if (data.display_tournament) {
        var playerCount = data.player_count || 0;
        var entryFee = data.entry_fee || 15;
        
        var leftPanel = document.getElementById('leftPanel');
        var rightPanel = document.getElementById('frameContainer');
        
        if (leftPanel && rightPanel) {
            if (playerCount > 0) {
                leftPanel.classList.remove('hidden');
                rightPanel.classList.remove('fullscreen');
            } else {
                leftPanel.classList.add('hidden');
                rightPanel.classList.add('fullscreen');
            }
        }
        
        if (document.getElementById('playerCount')) {
            document.getElementById('playerCount').textContent = playerCount + ' PLAYERS';
        }
        
        if (document.getElementById('entryFee')) {
            var feeLabel = data.entry_fee_label || 'Entry:';
            var feeValue = data.entry_fee || 15;
            
            // ALWAYS ensure dollar sign
            if (typeof feeValue === 'number' || !isNaN(feeValue)) {
                feeValue = '$' + feeValue;
            } else if (!String(feeValue).startsWith('$')) {
                feeValue = '$' + feeValue;
            }
            
            document.getElementById('entryFee').textContent = feeLabel + ' ' + feeValue;
        }
    }
}
</script>

<?php else: ?>

<!-- No tournament, show media only -->
<div class="split right" id="frameContainer" style="width: 100vw; left: 0;"></div>

<script>
console.log('No tournament today - showing media only');
</script>

<?php endif; ?>

<script>
(function() {
    var loadingHidden = false;
    
    function hideLoading() {
        if (loadingHidden) return;
        
        var loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.style.display = 'none';
            loadingScreen.classList.add('hidden');
            loadingHidden = true;
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideLoading);
    } else {
        hideLoading();
    }
    
    setTimeout(hideLoading, 800);
    setTimeout(function() {
        if (!loadingHidden) {
            hideLoading();
        }
    }, 2000);
})();
</script>

</body>
</html>
