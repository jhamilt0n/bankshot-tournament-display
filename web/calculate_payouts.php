<?php
header('Content-Type: application/json');
require_once 'payout_calculator.php';

$players = isset($_GET['players']) ? intval($_GET['players']) : 0;
$entry = isset($_GET['entry']) ? intval($_GET['entry']) : 15;

echo json_encode(getPayouts($players, $entry));
?>
