<?php
header('Content-Type: application/json');

$tournament_data_file = '/var/www/html/tournament_data.json';

if (file_exists($tournament_data_file)) {
    $data = json_decode(file_get_contents($tournament_data_file), true);
    $data['success'] = true;
    echo json_encode($data);
} else {
    echo json_encode([
        'success' => false,
        'display_tournament' => false,
        'player_count' => 0
    ]);
}
?>
