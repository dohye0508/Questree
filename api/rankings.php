<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$dataFile = '../data/rankings.json';

// Ensure data directory exists
if (!file_exists('../data')) { mkdir('../data', 0777, true); }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['mode'] ?? '';
    if (file_exists($dataFile)) {
        $json = file_get_contents($dataFile);
        $data = json_decode($json, true);
        $rankings = $data[$mode] ?? [];
        echo json_encode($rankings);
    } else {
        echo json_encode([]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Switch to $_POST
    $mode = $_POST['mode'] ?? '';
    $name = $_POST['name'] ?? '';
    $time = floatval($_POST['time'] ?? 0);
    $token = $_POST['token'] ?? '';

    if (!$mode || !$name) {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    // Validate token
    if (!$token) {
        echo json_encode(['error' => 'No token - cheating detected']);
        exit;
    }

    // Call token validation
    $ch = curl_init();
    $tokenUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']) . "/game_token.php";
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'validate',
        'token' => $token,
        'time' => $time
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenResult = json_decode($response, true);
    if (!$tokenResult || !$tokenResult['success']) {
        echo json_encode(['error' => 'Invalid token - ' . ($tokenResult['error'] ?? 'unknown')]);
        exit;
    }

    // Validate move times for cheating patterns
    $moveTimes = json_decode($_POST['move_times'] ?? '[]', true);
    if (is_array($moveTimes) && count($moveTimes) > 0) {
        // Convert string times to floats
        $moveTimes = array_map('floatval', $moveTimes);
        
        // Check for suspicious patterns: too many moves at same timestamp
        $timeCounts = array_count_values(array_map(function($t) {
            return strval(round($t, 1)); // Round to 1 decimal, convert to string
        }, $moveTimes));
        
        $maxSameTime = max($timeCounts);
        // If more than 3 moves have the same timestamp = cheating
        if ($maxSameTime > 3) {
            echo json_encode(['error' => '비정상적인 이동 패턴이 감지되었습니다']);
            exit;
        }
    }

    // Verify final order hash matches server's stored hash
    $finalOrder = json_decode($_POST['final_order'] ?? '[]', true);
    
    // Get sorted hash from token
    $tokensFile = '../data/game_tokens.json';
    $tokens = file_exists($tokensFile) ? json_decode(file_get_contents($tokensFile), true) : [];
    
    if (isset($tokens[$token]) && isset($tokens[$token]['sorted_hash'])) {
        $storedHash = $tokens[$token]['sorted_hash'];
        
        // Compute hash of final order
        $finalHash = hash('sha256', implode('|', $finalOrder));
        
        // Compare hashes
        if ($finalHash !== $storedHash) {
            echo json_encode(['error' => '정렬 순서 검증 실패 - 치팅이 감지되었습니다']);
            exit;
        }
    }

    $data = [];
    if (file_exists($dataFile)) {
        $data = json_decode(file_get_contents($dataFile), true);
    }

    if (!isset($data[$mode])) {
        $data[$mode] = [];
    }

    $data[$mode][] = ['name' => $name, 'time' => (float)$time];
    
    // Sort
    usort($data[$mode], function($a, $b) {
        return $a['time'] <=> $b['time'];
    });

    // Top 100 (Expanded from 5)
    $data[$mode] = array_slice($data[$mode], 0, 100);

    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    echo json_encode(['success' => true]);
}
?>
