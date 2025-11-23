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
            padding: 1vh 1vw;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            box-shadow: 5px 0 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.5s ease-in-out;
            overflow-y: auto;
        }

        .qr-container img {
            width: 100%;
            max-width: 150px;
            border-radius: 6px;
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
            margin: 2vh 0;
            background: white;
            padding: 1.5vh;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        .qr-container img {
            width: 100%;
            max-width: 200px;
            border-radius: 6px;
        }

        .qr-label {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5vw;
            color: #1e7e34;
            margin-top: 1vh;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .player-count {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 4vw;
            color: #ffffff;
            text-align: center;
            margin: 1vh 0;
            font-weight: bold;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5);
            background: rgba(255, 255, 255, 0.15);
            padding: 1vh;
            border-radius: 10px;
            width: 100%;
            flex-shrink: 0;
        }

        .entry-fee {
            font-family: 'Oswald', sans-serif;
            font-size: 2.2vw;
            color: #ffffff;
            text-align: center;
            margin: 1.5vh 0;
            font-weight: 500;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .payouts-header {
            font-family: 'Oswald', sans-serif;
            font-size: 1.6vw;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 1vh 0 0.5vh 0;
            font-weight: 600;
            text-align: center;
            border-bottom: 3px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 0.5vh;
            width: 100%;
            flex-shrink: 0;
        }

        .payouts {
            font-family: 'Roboto', sans-serif;
            font-size: 1.8vw;
            color: #ffffff;
            text-align: center;
            line-height: 1.2;
            width: 100%;
            flex-shrink: 1;
            min-height: 0;
        }
        
        .payouts div {
            margin: 0.3vh 0;
            padding: 0.5vh;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            font-weight: 500;
        }
        
        .payouts .first-place {
            background: rgba(255, 215, 0, 0.3);
            font-weight: 700;
            font-size: 2.2vw;
            border: 2px solid rgba(255, 215, 0, 0.6);
        }

        /* Dynamic scaling for many payouts */
        .payouts.compact {
            font-size: 1.9vw;
            line-height: 1.2;
        }

        .payouts.compact div {
            margin: 0.3vh 0;
            padding: 0.5vh;
        }

        .payouts.compact .first-place {
            font-size: 2.4vw;
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
require_once 'payout_calculator.php';

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
    if (m.hasEndDate && m.endDate && currentDate > m.endDate) {
        return false;
    }
    
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
            
            if (currentTime < startTime || currentTime > endTime) {
                return false;
            }
        }
        return true;
    }
    
    if (m.scheduleDays && m.scheduleDays.length > 0 && m.scheduleDays.indexOf(currentDay) === -1) {
        return false;
    }
    
    if (m.scheduleStartTime && m.scheduleEndTime) {
        var startParts = m.scheduleStartTime.split(':');
        var endParts = m.scheduleEndTime.split(':');
        var startTime = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
        var endTime = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);
        
        if (currentTime < startTime || currentTime > endTime) {
            return false;
        }
    }
    
    return true;
}

var Dash = {
    dashboards: [],
    nextIndex: 0,
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
        <img src="qr_code.png?t=<?php echo time(); ?>" alt="Tournament Bracket QR Code">
        <div class="qr-label">Scan for Bracket</div>
    </div>
    
    <div class="player-count" id="playerCount"><?php echo $player_count; ?> PLAYERS</div>
    
    <div class="entry-fee" id="entryFee">Entry: $<?php echo $entry_fee; ?></div>
    
    <div class="payouts-header">PAYOUTS</div>
    <div class="payouts" id="payouts">
        <?php 
        $payout_html = formatPayoutsHTML($player_count, $entry_fee);
        $payout_html = str_replace('<div>1st:', '<div class="first-place">1st:', $payout_html);
        echo $payout_html;
        ?>
    </div>
</div>

<div class="split right <?php echo ($player_count == 0) ? 'fullscreen' : ''; ?>" id="frameContainer"></div>

<script>
// IMPROVED: Track detailed state to detect player count drops to zero
let lastTournamentState = {
    display: null,
    playerCount: null,
    initialized: false
};

function updatePlayerData() {
    fetch('/get_tournament_data.php?nocache=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.display_tournament) {
                var playerCount = data.player_count || 0;
                var entryFee = data.entry_fee || 15;
                
                // CRITICAL: Detect if player count dropped to zero
                if (lastTournamentState.initialized && 
                    lastTournamentState.playerCount > 0 && 
                    playerCount === 0) {
                    console.log('Player count dropped to zero - reloading page');
                    location.reload();
                    return;
                }
                
                // Update state
                lastTournamentState.playerCount = playerCount;
                lastTournamentState.display = data.display_tournament;
                lastTournamentState.initialized = true;
                
                // Show/hide left panel based on player count
                var leftPanel = document.getElementById('leftPanel');
                var rightPanel = document.getElementById('frameContainer');
                
                if (playerCount > 0) {
                    leftPanel.classList.remove('hidden');
                    rightPanel.classList.remove('fullscreen');
                } else {
                    leftPanel.classList.add('hidden');
                    rightPanel.classList.add('fullscreen');
                }
                
                document.getElementById('playerCount').textContent = playerCount + ' PLAYERS';
                document.getElementById('entryFee').textContent = 'Entry: $' + entryFee;
                
                fetch('/calculate_payouts.php?players=' + playerCount + '&entry=' + entryFee)
                    .then(response => response.json())
                    .then(payouts => {
                        var payoutDiv = document.getElementById('payouts');
                        var html = '';
                        var payoutCount = 0;
                        
                        if (!payouts.error) {
                            if (payouts['1st']) {
                                payoutCount++;
                                html += '<div class="first-place">1st: ' + payouts['1st'] + '</div>';
                            }
                            
                            if (payouts['2nd']) {
                                payoutCount++;
                                html += '<div>2nd: ' + payouts['2nd'] + '</div>';
                            }
                            
                            if (payouts['3rd']) {
                                payoutCount++;
                                html += '<div>3rd: ' + payouts['3rd'] + '</div>';
                            }
                            
                            if (payouts['4th']) {
                                payoutCount++;
                                html += '<div>4th: ' + payouts['4th'] + '</div>';
                            }
                            
                            if (payouts['5th']) {
                                payoutCount++;
                                html += '<div>5/6: ' + payouts['5th'] + '</div>';
                            }
                            
                            if (payouts['7th']) {
                                payoutCount++;
                                html += '<div>7/8: ' + payouts['7th'] + '</div>';
                            }
                        } else {
                            html = '<div>Payouts TBD</div>';
                        }
                        
                        // Add compact class if more than 4 payout places
                        if (payoutCount > 4) {
                            payoutDiv.classList.add('compact');
                        } else {
                            payoutDiv.classList.remove('compact');
                        }
                        
                        payoutDiv.innerHTML = html;
                    })
                    .catch(err => console.error('Error loading payouts:', err));
            }
        })
        .catch(err => console.error('Error fetching tournament data:', err));
}

// Update player data every 30 seconds
updatePlayerData();
setInterval(updatePlayerData, 30000);
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

<script>
// IMPROVED: More aggressive tournament state checking
let lastTournamentCheck = {
    display: null,
    playerCount: null,
    tournamentUrl: null
};

function checkTournamentChanges() {
    fetch('/get_tournament_data.php?nocache=' + Date.now())
        .then(response => response.json())
        .then(data => {
            var currentDisplay = data.display_tournament || false;
            var currentPlayerCount = data.player_count || 0;
            var currentUrl = data.tournament_url || '';
            
            // First run - just store state
            if (lastTournamentCheck.display === null) {
                lastTournamentCheck.display = currentDisplay;
                lastTournamentCheck.playerCount = currentPlayerCount;
                lastTournamentCheck.tournamentUrl = currentUrl;
                console.log('Initial state:', lastTournamentCheck);
                return;
            }
            
            // Check for state changes that require reload
            var shouldReload = false;
            var reloadReason = '';
            
            // Tournament display flag changed
            if (currentDisplay !== lastTournamentCheck.display) {
                shouldReload = true;
                reloadReason = 'display_tournament changed from ' + lastTournamentCheck.display + ' to ' + currentDisplay;
            }
            
            // Player count dropped to zero (tournament ended/reset)
            else if (lastTournamentCheck.playerCount > 0 && currentPlayerCount === 0) {
                shouldReload = true;
                reloadReason = 'player count dropped to zero';
            }
            
            // Player count changed significantly (new tournament or major update)
            else if (Math.abs(currentPlayerCount - lastTournamentCheck.playerCount) >= 3) {
                shouldReload = true;
                reloadReason = 'player count changed by ' + Math.abs(currentPlayerCount - lastTournamentCheck.playerCount);
            }
            
            // Tournament URL changed (different tournament)
            else if (currentUrl !== lastTournamentCheck.tournamentUrl && currentUrl !== '') {
                shouldReload = true;
                reloadReason = 'tournament URL changed';
            }
            
            if (shouldReload) {
                console.log('🔄 Reloading page: ' + reloadReason);
                console.log('Old state:', lastTournamentCheck);
                console.log('New state:', {display: currentDisplay, playerCount: currentPlayerCount, tournamentUrl: currentUrl});
                location.reload();
            }
        })
        .catch(err => {
            console.log('Tournament check failed:', err.message);
        });
}

// Check every 15 seconds
setInterval(checkTournamentChanges, 15000);
// Initial check after 3 seconds
setTimeout(checkTournamentChanges, 3000);
</script>

</body>
</html>
