<?php
header('Content-Type: text/html; charset=utf-8');

$userFile = '../data/users.json';
$rankFile = '../data/rankings.json';

// Load password
$adminPass = "1234"; // Fallback default
if (file_exists('secret.php')) {
    include 'secret.php';
}
$inputPass = $_POST['pass'] ?? $_GET['pass'] ?? '';

// Handle Actions (MUST BE BEFORE HTML OUTPUT for Headers to work)
if ($inputPass === $adminPass) {
    // 1. Download All Logs (ZIP) - MOVED TO TOP
    if (isset($_POST['download_zip'])) {
        $zipname = 'all_logs_' . date('Ymd_His') . '.zip';
        $zipPath = '../data/' . $zipname;
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $files = scandir('../data/logs');
                $count = 0;
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $filePath = '../data/logs/' . $file;
                    if (is_file($filePath)) {
                        $zip->addFile($filePath, $file);
                        $count++;
                    }
                }
                $zip->close();

                if ($count > 0 && file_exists($zipPath)) {
                    // Start of buffer cleanup to prevent corruption
                    if (ob_get_level()) ob_end_clean();
                    
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="'.$zipname.'"');
                    header('Content-Length: ' . filesize($zipPath));
                    readfile($zipPath);
                    unlink($zipPath); 
                    exit;
                } else {
                    echo "<script>alert('다운로드할 로그 파일이 없습니다.');</script>";
                }
            } else {
                echo "<script>alert('ZIP 파일 생성 실패');</script>";
            }
        } else {
            echo "<script>alert('이 서버는 ZIP 기능을 지원하지 않습니다.');</script>";
        }
    }

    // 2. File Upload
    if (isset($_FILES['update_file'])) {
        $f = $_FILES['update_file'];
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $target = '';
        if ($ext === 'html' || $ext === 'js' || $ext === 'csv') {
            $target = '../' . basename($f['name']);
        } elseif ($ext === 'php') {
            $target = './' . basename($f['name']);
        } elseif ($ext === 'json') {
            $target = '../data/' . basename($f['name']);
        }

        if ($target && move_uploaded_file($f['tmp_name'], $target)) {
            echo "<script>alert('파일 업로드 성공: {$f['name']}');</script>";
        } else {
            echo "<script>alert('업로드 실패');</script>";
        }
    }

    // 3. Delete Log
    if (isset($_POST['delete_log'])) {
        $fileToDelete = '../data/logs/' . basename($_POST['delete_log']);
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete);
            echo "<script>alert('로그 삭제 완료');</script>";
        }
    }
    
    // 4. Reset Data (with PRG pattern to prevent refresh re-submit)
    if (isset($_POST['reset_target'])) {
        $target = $_POST['reset_target'];
        $msg = '';
        if ($target === 'rankings') {
            file_put_contents('../data/rankings.json', json_encode([]), LOCK_EX);
            $msg = urlencode('🏆 랭킹 데이터 초기화 완료');
        } elseif ($target === 'users') {
            file_put_contents('../data/users.json', json_encode([]), LOCK_EX);
            $msg = urlencode('👥 사용자 데이터 초기화 완료');
        }
        // PRG Redirect to prevent refresh re-submission
        header("Location: admin.php?pass=$inputPass&msg=$msg");
        exit;
    }
    
    // 5. Delete Individual Ranking
    if (isset($_POST['delete_ranking'])) {
        $mode = $_POST['delete_mode'] ?? '';
        $idx = intval($_POST['delete_idx'] ?? -1);
        $rankings = file_exists($rankFile) ? json_decode(file_get_contents($rankFile), true) : [];
        if ($mode && isset($rankings[$mode][$idx])) {
            array_splice($rankings[$mode], $idx, 1);
            file_put_contents($rankFile, json_encode($rankings, JSON_UNESCAPED_UNICODE), LOCK_EX);
            $msg = urlencode('🗑️ 랭킹 기록 삭제 완료');
        } else {
            $msg = urlencode('❌ 삭제 실패');
        }
        header("Location: admin.php?pass=$inputPass&msg=$msg");
        exit;
    }
    
    // 6. Bulk Delete Rankings
    if (isset($_POST['bulk_delete'])) {
        $toDelete = $_POST['delete_items'] ?? [];
        $rankings = file_exists($rankFile) ? json_decode(file_get_contents($rankFile), true) : [];
        $count = 0;
        // Group by mode and sort indices descending to delete from end first
        $byMode = [];
        foreach ($toDelete as $item) {
            list($mode, $idx) = explode('|', $item);
            $byMode[$mode][] = intval($idx);
        }
        foreach ($byMode as $mode => $indices) {
            rsort($indices); // Delete from end first
            foreach ($indices as $idx) {
                if (isset($rankings[$mode][$idx])) {
                    array_splice($rankings[$mode], $idx, 1);
                    $count++;
                }
            }
        }
        file_put_contents($rankFile, json_encode($rankings, JSON_UNESCAPED_UNICODE), LOCK_EX);
        $msg = urlencode("🗑️ {$count}개 기록 삭제 완료");
        header("Location: admin.php?pass=$inputPass&msg=$msg");
        exit;
    }
}

if ($inputPass !== $adminPass) {
    echo '<form method="POST">Code: <input type="password" name="pass"><input type="submit" value="Login"></form>';
    exit;
}

// Load Data
$users = file_exists($userFile) ? json_decode(file_get_contents($userFile), true) : [];
$userCount = is_array($users) ? count($users) : 0;
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
    <?php if(isset($_GET['msg']) && $_GET['msg']): ?>
    <script>alert(decodeURIComponent('<?php echo $_GET['msg']; ?>'));</script>
    <?php endif; ?>
    <h1>📊 Data Viewer</h1>
    
    <!-- Admin Actions UI -->
    <div style="background:#fff3cd; padding:15px; border:1px solid #ffeeba; margin-bottom:20px;">
        <h3>⚠️ Danger Zone & Actions</h3>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="pass" value="<?= htmlspecialchars($inputPass) ?>">
            <input type="hidden" name="download_zip" value="1">
            <button type="submit" style="background:#4CAF50; color:white; border:none; padding:8px 15px; cursor:pointer; margin-right:10px;">📦 전체 로그 다운로드 (ZIP)</button>
        </form>

        <form method="POST" style="display:inline;" onsubmit="return confirm('정말 모든 랭킹 데이터를 삭제하시겠습니까?');">
            <input type="hidden" name="pass" value="<?= htmlspecialchars($inputPass) ?>">
            <input type="hidden" name="reset_target" value="rankings">
            <button type="submit" style="background:#ff4444; color:white; border:none; padding:8px 15px; cursor:pointer;">🏆 랭킹 초기화</button>
        </form>
        <form method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('정말 모든 사용자 정보를 삭제하시겠습니까?');">
            <input type="hidden" name="pass" value="<?= htmlspecialchars($inputPass) ?>">
            <input type="hidden" name="reset_target" value="users">
            <button type="submit" style="background:#ff4444; color:white; border:none; padding:8px 15px; cursor:pointer;">👥 회원 초기화</button>
        </form>
    </div>

    <!-- File Uploader -->
    <h2>🚀 Server File Update</h2>
    <p>파일질라 없이 여기서 파일(`index.html`, `.php`, `.js`)을 업로드하면 덮어씌워집니다.</p>
    <form method="POST" enctype="multipart/form-data" style="background:#f9f9f9; padding:15px; border:1px solid #ddd;">
        <input type="hidden" name="pass" value="<?= htmlspecialchars($inputPass) ?>">
        <input type="file" name="update_file" required>
        <button type="submit" onclick="return confirm('정말 덮어씌우시겠습니까?');">Upload & Update</button>
    </form>

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
        <form method="POST" id="bulkDeleteForm">
            <input type="hidden" name="pass" value="<?= htmlspecialchars($inputPass) ?>">
            <input type="hidden" name="bulk_delete" value="1">
            <button type="submit" onclick="return confirm('선택한 기록들을 삭제하시겠습니까?');" style="background:#ff4444; color:white; border:none; padding:8px 15px; cursor:pointer; margin-bottom:10px;">🗑️ 선택 삭제</button>
        <?php foreach($rankings as $mode => $list): ?>
            <h3><?= htmlspecialchars($mode) ?> <button type="button" onclick="toggleAll('<?= $mode ?>')" style="font-size:12px; padding:2px 6px;">전체선택</button></h3>
            <table>
                <tr><th><input type="checkbox" onclick="toggleAll('<?= $mode ?>')"></th><th>Rank</th><th>Name</th><th>Time</th></tr>
                <?php foreach($list as $i => $row): ?>
                <tr>
                    <td><input type="checkbox" name="delete_items[]" value="<?= $mode ?>|<?= $i ?>" class="check-<?= $mode ?>"></td>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><strong><?= htmlspecialchars($row['time']) ?>s</strong></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
        </form>
        <script>
        function toggleAll(mode) {
            const checkboxes = document.querySelectorAll('.check-' + mode);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
        </script>
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
            $url = '../data/logs/' . rawurlencode($f);
            echo "<li style='margin-bottom:5px;'>";
            echo "<form method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='pass' value='" . htmlspecialchars($inputPass) . "'>";
            echo "<input type='hidden' name='delete_log' value='" . htmlspecialchars($f) . "'>";
            echo "<button type='submit' style='background:#ff4444; color:white; border:none; padding:2px 5px; cursor:pointer; margin-right:5px;' onclick=\"return confirm('삭제하시겠습니까?');\">X</button>";
            echo "</form>";
            echo "<a href='{$url}' download>" . htmlspecialchars($f) . "</a> (" . filesize($logDir.'/'.$f) . " bytes)";
            echo "</li>";
        }
    } else {
        echo "<li>No logs directory.</li>";
    }
    ?>
    </ul>
</body>
</html>
