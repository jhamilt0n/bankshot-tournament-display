<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bankshot Tournament Display</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1e7e34;
            --primary-dark: #0d5c26;
            --sidebar-top: #1a472a;
            --sidebar-bottom: #0d3320;
        }

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
            background: linear-gradient(180deg, var(--sidebar-top) 0%, var(--sidebar-bottom) 100%);
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

        /* Left Panel - Tournament Console Theme */
        .left {
            left: 0;
            width: 20vw;
            background: linear-gradient(180deg, var(--sidebar-top) 0%, var(--sidebar-bottom) 100%);
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 20px rgba(0, 0, 0, 0.5);
            transition: transform 0.5s ease-in-out;
            overflow: hidden;
            height: 100vh;
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

        /* Sidebar Section Styling */
        .sidebar-section {
            padding: 2vh 1.2vw;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar-section:last-child {
            border-bottom: none;
            flex: 1;
        }

        .sidebar-label {
            font-family: 'Roboto', sans-serif;
            font-size: 0.7vw;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            margin-bottom: 1vh;
            color: white;
            width: 100%;
            text-align: center;
        }

        /* Tournament Name Header */
        .tournament-header {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.5vh 1vw;
        }

        .tournament-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8vw;
            color: #ffffff;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
            line-height: 1.2;
        }

        /* QR Code Section */
        .qr-container {
            text-align: center;
            background: white;
            padding: 0.6vw;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: inline-block;
        }

        .qr-container img {
            width: 8vw;
            height: 8vw;
            max-width: 140px;
            max-height: 140px;
            border-radius: 4px;
            display: block;
        }

        .qr-label {
            font-family: 'Roboto', sans-serif;
            font-size: 0.75vw;
            color: white;
            margin-top: 1vh;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Player Count Section */
        .player-count {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3vw;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 1vh 1vw;
            border-radius: 8px;
            width: 100%;
        }

        /* Entry Fee Section */
        .entry-fee {
            font-family: 'Oswald', sans-serif;
            font-size: 1.4vw;
            color: #ffffff;
            text-align: center;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1vh 1vw;
            border-radius: 8px;
            width: 100%;
        }

        /* Payouts Section */
        .payouts-header {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4vw;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1vh;
            font-weight: normal;
            text-align: center;
            width: 100%;
            opacity: 0.9;
        }

        .payouts {
            font-family: 'Roboto', sans-serif;
            font-size: 1.1vw;
            color: #ffffff;
            text-align: center;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.4vh;
        }

        .payouts div {
            padding: 0.5vh 0.8vw;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            font-weight: 400;
            display: flex;
            justify-content: space-between;
        }

        .payouts .first-place {
            background: rgba(255, 215, 0, 0.2);
            font-weight: 600;
            font-size: 1.3vw;
            border: 1px solid rgba(255, 215, 0, 0.4);
            padding: 0.7vh 0.8vw;
        }

        .payouts.compact {
            font-size: 0.95vw;
            gap: 0.3vh;
        }

        .payouts.compact div {
            padding: 0.35vh 0.6vw;
        }

        .payouts.compact .first-place {
            font-size: 1.1vw;
            padding: 0.5vh 0.6vw;
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
    </style>
</head>
<body>

<div id="loadingScreen">
    <div class="spinner"></div>
    <div class="loading-text">LOADING TOURNAMENT...</div>
</div>

<?php 
set_time_limit(60);
require_once 'tournament_payout_calculator.php';

function calculatePayouts($player_count, $entry_fee) {
    if ($player_count < 8) {
        return [];
    }
    
    $calculator = new TournamentPayoutCalculator($entry_fee, $player_count);
    $payouts_array = $calculator->getPayoutsArray();
    
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
    if ($place == 1) return '1st';
    if ($place == 2) return '2nd';
    if ($place == 3) return '3rd';
    if ($place == 4) return '4th';
    if ($place == 5 || $place == 6) return '5th-6th';
    if ($place == 7 || $place == 8) return '7th-8th';
    if ($place >= 9 && $place <= 12) return '9th-12th';
    if ($place >= 13 && $place <= 16) return '13th-16th';
    if ($place >= 17 && $place <= 24) return '17th-24th';
    if ($place >= 25 && $place <= 32) return '25th-32nd';
    if ($place >= 33 && $place <= 48) return '33rd-48th';
    if ($place >= 49 && $place <= 64) return '49th-64th';
    if ($place >= 65 && $place <= 96) return '65th-96th';
    if ($place >= 97 && $place <= 128) return '97th-128th';
    if ($place >= 129 && $place <= 256) return '129th-256th';
    return $place . 'th';
}

function formatPayoutsHTML($payouts) {
    if (empty($payouts)) {
        return '<div>Calculating...</div>';
    }
    
    $html = '';
    $seen_places = [];
    
    foreach ($payouts as $payout) {
        $place = $payout['place'] ?? '';
        $amount = $payout['amount'] ?? '';
        
        if (in_array($place, $seen_places)) {
            continue;
        }
        $seen_places[] = $place;
        
        $class = ($place === '1st') ? 'first-place' : '';
        $html .= "<div class='$class'><span>$place</span><span>$amount</span></div>\n";
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const CALCUTTA_SIDEPOT_DURATION = 40;
const TOURNAMENT_PLAYER_COUNT = <?php echo $player_count; ?>;
const TOURNAMENT_FOUND = <?php echo $tournament_found ? 'true' : 'false'; ?>;

function shouldDisplayMedia(m, now, currentDay, currentTime, currentDate) {
    if (m.hasEndDate && m.endDate && currentDate > m.endDate) return false;
    
    if (m.usePerDaySchedule && m.daySchedules) {
        var daySchedule = m.daySchedules[currentDay];
        if (!daySchedule || !daySchedule.enabled) return false;
        
        if (daySchedule.startTime && daySchedule.endTime) {
            var startParts = daySchedule.startTime.split(':');
            var endParts = daySchedule.endTime.split(':');
            var startTime = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
            var endTime = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);
            if (currentTime < startTime || currentTime > endTime) return false;
        }
        return true;
    }
    
    if (m.scheduleDays && m.scheduleDays.length > 0 && m.scheduleDays.indexOf(currentDay) === -1) return false;
    
    if (m.scheduleStartTime && m.scheduleEndTime) {
        var startParts = m.scheduleStartTime.split(':');
        var endParts = m.scheduleEndTime.split(':');
        var startTime = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
        var endTime = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);
        if (currentTime < startTime || currentTime > endTime) return false;
    }
    
    return true;
}

// Fetch active tournaments from local tournament_state.json via save_tournament.php
async function getActiveDisplays() {
    try {
        const response = await fetch('/save_tournament.php');
        if (!response.ok) return { tournaments: [] };
        
        const result = await response.json();
        if (!result.success || !result.data || !result.data.tournaments) {
            return { tournaments: [] };
        }
        
        const tournaments = [];
        
        // Process each tournament in the state file
        for (const [key, tournamentData] of Object.entries(result.data.tournaments)) {
            if (!tournamentData.checkedInPlayers || tournamentData.checkedInPlayers.length === 0) {
                continue;
            }
            
            const players = tournamentData.checkedInPlayers;
            
            // Check if tournament has calcutta data (any player with buyer and bid > 0)
            const hasCalcutta = players.some(p => 
                p.calcuttaBuyer && 
                p.calcuttaBuyer.trim() !== '' && 
                parseFloat(p.calcuttaBid) > 0
            );
            
            // Also check pool auction
            const hasPoolAuction = tournamentData.poolAuction && 
                tournamentData.poolAuction.buyer && 
                parseFloat(tournamentData.poolAuction.bid) > 0;
            
            // Check if tournament has sidepot data (any player in sidepot)
            const hasSidePot = players.some(p => p.inSidePot === true);
            
            // Only add if there's something to display
            if (hasCalcutta || hasPoolAuction || hasSidePot) {
                tournaments.push({
                    key: key,
                    name: tournamentData.tournamentName || 'Tournament',
                    hasCalcutta: hasCalcutta || hasPoolAuction,
                    hasSidePot: hasSidePot,
                    playerCount: players.length,
                    lastUpdated: tournamentData.lastUpdated
                });
            }
        }
        
        console.log('Found tournaments with display data:', tournaments);
        return { tournaments: tournaments };
        
    } catch (error) {
        console.error('Error fetching active displays:', error);
        return { tournaments: [] };
    }
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
    
    initializeDashboards: async function() {
        console.log('Loading media rotation...');
        console.log('Tournament found:', TOURNAMENT_FOUND);
        console.log('Player count:', TOURNAMENT_PLAYER_COUNT);
        
        try {
            const response = await fetch('/load_media.php');
            const data = await response.json();
            
            <?php if ($tournament_found): ?>
            var allMediaItems = data.filter(function(m) { 
                return m.active === true && m.displayOnTournaments === true;
            });
            console.log('Tournament mode: ' + allMediaItems.length + ' tournament media items');
            <?php else: ?>
            var allMediaItems = data.filter(function(m) { 
                return m.active === true && m.displayOnAds === true;
            });
            console.log('Ad mode: ' + allMediaItems.length + ' ad media items');
            <?php endif; ?>
            
            allMediaItems.sort(function(a, b) { return (a.order || 0) - (b.order || 0); });
            
            var now = new Date();
            var currentDay = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][now.getDay()];
            var currentTime = now.getHours() * 60 + now.getMinutes();
            var currentDate = now.toISOString().split('T')[0];
            
            var filteredMedia = allMediaItems.filter(function(m) {
                return shouldDisplayMedia(m, now, currentDay, currentTime, currentDate);
            });
            
            console.log('After schedule filter: ' + filteredMedia.length + ' items to display');
            
            for (var i = 0; i < filteredMedia.length; i++) {
                var mediaItem = filteredMedia[i];
                if (mediaItem.type === 'url') {
                    Dash.dashboards.push({
                        url: mediaItem.url,
                        time: mediaItem.duration || 20,
                        refresh: true,
                        name: mediaItem.name || 'URL'
                    });
                } else {
                    Dash.dashboards.push({
                        url: 'data:text/html;charset=utf-8,' + encodeURIComponent(Dash.createMediaHTML(mediaItem)),
                        time: mediaItem.duration || 20,
                        refresh: false,
                        name: mediaItem.name || 'Media'
                    });
                }
            }
            
            // Get tournaments with Calcutta/SidePot data from tournament_state.json
            // This runs independently of tournament_data.json player count
            const activeDisplays = await getActiveDisplays();
            
            if (activeDisplays.tournaments && activeDisplays.tournaments.length > 0) {
                console.log('Found ' + activeDisplays.tournaments.length + ' tournament(s) with display data');
                
                // Add displays for each tournament
                for (const tournament of activeDisplays.tournaments) {
                    const encodedName = encodeURIComponent(tournament.name);
                    const encodedKey = encodeURIComponent(tournament.key);
                    
                    if (tournament.hasCalcutta) {
                        console.log('Adding Calcutta for: ' + tournament.name);
                        Dash.dashboards.push({
                            url: '/calcutta.html?key=' + encodedKey + '&tournament=' + encodedName,
                            time: CALCUTTA_SIDEPOT_DURATION,
                            refresh: true,
                            name: 'Calcutta - ' + tournament.name
                        });
                    }
                    
                    if (tournament.hasSidePot) {
                        console.log('Adding Side Pot for: ' + tournament.name);
                        Dash.dashboards.push({
                            url: '/sidepot.html?key=' + encodedKey + '&tournament=' + encodedName,
                            time: CALCUTTA_SIDEPOT_DURATION,
                            refresh: true,
                            name: 'Side Pot - ' + tournament.name
                        });
                    }
                }
            } else {
                console.log('No tournaments with calcutta/sidepot data found');
            }
            
            console.log('Final dashboard lineup:');
            Dash.dashboards.forEach(function(d, i) {
                console.log('  ' + i + ': ' + d.name + ' (' + d.time + 's)');
            });
            
            Dash.continueStartup();
            
        } catch (err) {
            console.error('Error loading media:', err);
            Dash.continueStartup();
        }
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
        console.log('Displaying: ' + currentDashboard.name + ' for ' + currentDashboard.time + 's');
        
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
    <!-- Tournament Name Section -->
    <div class="sidebar-section tournament-header">
        <div class="tournament-name" id="tournamentName"><?php echo htmlspecialchars($tournament_name); ?></div>
    </div>
    
    <!-- QR Code Section -->
    <div class="sidebar-section">
        <div class="sidebar-label">Scan for Bracket</div>
        <div class="qr-container">
            <img src="tournament_qr.png?t=<?php echo time(); ?>" alt="Tournament Bracket QR Code">
        </div>
    </div>
    
    <!-- Player Count Section -->
    <div class="sidebar-section">
        <div class="sidebar-label">Checked In</div>
        <div class="player-count" id="playerCount"><?php echo $player_count; ?> PLAYERS</div>
    </div>
    
    <!-- Entry Fee Section -->
    <div class="sidebar-section">
        <div class="sidebar-label">Tournament Entry</div>
        <div class="entry-fee" id="entryFee">
            <?php 
            $fee_label = $tournament_data['entry_fee_label'] ?? 'Entry:';
            $fee_value = $tournament_data['entry_fee'] ?? 15;
            // Ensure dollar sign is present
            if (is_numeric($fee_value)) {
                $fee_value = '$' . number_format((float)$fee_value, 0);
            } elseif (is_string($fee_value) && strpos($fee_value, '$') === false) {
                $fee_value = '$' . $fee_value;
            }
            echo $fee_label . ' ' . $fee_value;
            ?>
        </div>
    </div>
    
    <!-- Payouts Section -->
    <div class="sidebar-section">
        <div class="payouts-header">Payouts</div>
        <div class="payouts" id="payouts">
            <?php 
            $has_digital_pool = isset($tournament_data['has_digital_pool_payouts']) 
                                && $tournament_data['has_digital_pool_payouts'] === true;
            
            if ($has_digital_pool && isset($tournament_data['payouts']) && is_array($tournament_data['payouts'])) {
                $payouts = $tournament_data['payouts'];
            } else {
                $entry_fee_num = $tournament_data['entry_fee'] ?? 15;
                if (is_string($entry_fee_num)) {
                    $entry_fee_num = str_replace('$', '', $entry_fee_num);
                }
                $payouts = calculatePayouts($player_count, $entry_fee_num);
            }
            echo formatPayoutsHTML($payouts);
            ?>
        </div>
    </div>
</div>

<div class="split right <?php echo ($player_count == 0) ? 'fullscreen' : ''; ?>" id="frameContainer"></div>

<script>
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

function checkForChanges() {
    fetch('/get_tournament_data.php?nocache=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            
            const current = {
                display: data.display_tournament || false,
                playerCount: data.player_count || 0,
                entryFee: data.entry_fee || 15,
                tournamentUrl: data.tournament_url || '',
                tournamentName: data.tournament_name || '',
                status: data.status || '',
                hasDigitalPoolPayouts: data.has_digital_pool_payouts || false
            };
            
            if (!lastTournamentState.initialized) {
                lastTournamentState = {...current, initialized: true};
                console.log('Initial tournament state:', current);
                updatePlayerData(data);
                return;
            }
            
            let changed = false;
            let changeReasons = [];
            
            if (current.display !== lastTournamentState.display) {
                changed = true;
                changeReasons.push('display changed');
            }
            if (current.playerCount !== lastTournamentState.playerCount) {
                changed = true;
                changeReasons.push('players: ' + lastTournamentState.playerCount + ' -> ' + current.playerCount);
            }
            if (current.entryFee !== lastTournamentState.entryFee) {
                changed = true;
                changeReasons.push('entry fee changed');
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
                changeReasons.push('status changed');
            }
            if (current.hasDigitalPoolPayouts !== lastTournamentState.hasDigitalPoolPayouts) {
                changed = true;
                changeReasons.push('payouts source changed');
            }
            
            if (changed) {
                console.log('TOURNAMENT DATA CHANGED - RELOADING:', changeReasons.join(', '));
                location.reload();
            } else {
                updatePlayerData(data);
            }
        })
        .catch(err => console.error('Error checking tournament data:', err));
}

function updatePlayerData(data) {
    if (data.display_tournament) {
        var playerCount = data.player_count || 0;
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
            // Ensure dollar sign is present
            if (typeof feeValue === 'number' || !isNaN(feeValue)) {
                feeValue = '$' + feeValue;
            } else if (typeof feeValue === 'string' && feeValue.indexOf('$') === -1) {
                feeValue = '$' + feeValue;
            }
            document.getElementById('entryFee').textContent = feeLabel + ' ' + feeValue;
        }
    }
}

setInterval(checkForChanges, 10000);
setTimeout(checkForChanges, 2000);

// Track calcutta/sidepot display changes
let lastActiveDisplaysHash = null;

async function checkDisplayTypeChange() {
    try {
        const activeDisplays = await getActiveDisplays();
        
        // Create a hash of the current state to detect changes
        const currentHash = JSON.stringify(activeDisplays.tournaments || []);
        
        if (lastActiveDisplaysHash === null) {
            lastActiveDisplaysHash = currentHash;
            console.log('Active displays initialized:', activeDisplays.tournaments?.length || 0, 'tournaments');
            return;
        }
        
        if (currentHash !== lastActiveDisplaysHash) {
            console.log('Active displays changed - reloading...');
            location.reload();
        }
    } catch (err) {
        console.error('Error checking display changes:', err);
    }
}

// Check for calcutta/sidepot changes every 30 seconds
setInterval(checkDisplayTypeChange, 30000);
</script>

<?php else: ?>

<div class="split right" id="frameContainer" style="width: 100vw; left: 0;"></div>
<script>console.log('No tournament today - showing media only');</script>

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
    setTimeout(function() { if (!loadingHidden) hideLoading(); }, 2000);
})();
</script>

</body>
</html>
