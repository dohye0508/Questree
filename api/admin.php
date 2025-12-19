<?php
header('Content-Type: text/html; charset=utf-8');

$userFile = '../data/users.json';
$rankFile = '../data/rankings.json';

// Simple Password Protection (Hardcoded for simplicity)
$adminPass = "1234"; // Default Password
$inputPass = $_POST['pass'] ?? $_GET['pass'] ?? '';

if ($inputPass !== $adminPass) {
    echo '<form method="POST">Code: <input type="password" name="pass"><input type="submit" value="Login"></form>';
    exit;
}

// Load Users
$users = file_exists($userFile) ? json_decode(file_get_contents($userFile), true) : [];
$userCount = is_array($users) ? count($users) : 0;

// Load Rankings
$rankings = file_exists($rankFile) ? json_decode(file_get_contents($rankFile), true) : [];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Data Viewer</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        h2 { border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .badge { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; }
    </style>
</head>
<body>
    <h1>📊 Data Viewer</h1>
    
    <h2>👥 Registered Users (<?= $userCount ?>)</h2>
    <table>
        <tr><th>Student ID</th><th>Name</th><th>Joined</th></tr>
        <?php if($users): ?>
            <?php foreach($users as $id => $data): ?>
            <?php 
                $name = is_array($data) ? ($data['name'] ?? 'Unknown') : $data; 
                $joined = is_array($data) ? ($data['joined_at'] ?? '-') : '-';
            ?>
            <tr>
                <td><?= htmlspecialchars($id) ?></td>
                <td><?= htmlspecialchars($name) ?></td>
                <td><small><?= htmlspecialchars($joined) ?></small></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">No users registered yet.</td></tr>
        <?php endif; ?>
    </table>

    <h2>🏆 Rankings</h2>
    <?php if($rankings): ?>
        <?php foreach($rankings as $mode => $list): ?>
            <h3><?= htmlspecialchars($mode) ?></h3>
            <table>
                <tr><th>Rank</th><th>Name</th><th>Time</th></tr>
                <?php foreach($list as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><strong><?= htmlspecialchars($row['time']) ?>s</strong></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No rankings data yet.</p>
    <?php endif; ?>

    <h2>📂 Log Files</h2>
    <ul>
    <?php
    $logDir = '../data/logs';
    if(is_dir($logDir)){
        $files = scandir($logDir);
        foreach($files as $f){
            if($f === '.' || $f === '..') continue;
            echo "<li>" . htmlspecialchars($f) . " (" . filesize($logDir.'/'.$f) . " bytes)</li>";
        }
    } else {
        echo "<li>No logs directory.</li>";
    }
    ?>
    </ul>
</body>
</html>
