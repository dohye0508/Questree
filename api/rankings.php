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

    // NOTE: sorted_hash 검증은 제거됨
    // 이유: 서버가 단어 목록을 모르는 구조에서는 의미없음
    // 대신 토큰 검증, 시간 검증, 이동 패턴 검증으로 치팅 방지

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
