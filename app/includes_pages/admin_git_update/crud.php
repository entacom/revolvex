<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include_once("../../includes/common.php");
requireLoggedInJson();

header('Content-Type: application/json');

if (!in_array($_SESSION['session_group_id'], [11, 12, 13])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to run Git updates.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$repoPath = '/home/revolvexcom/revolvex';
$deployPath = '/home/revolvexcom/public_html';
$deployLog = $repoPath . '/deploy_history.log';
$lockFile = sys_get_temp_dir() . '/revolvex_git_update.lock';

function gitUpdateRun($command, $cwd) {
    $fullCommand = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';
    return trim((string)shell_exec($fullCommand));
}

function gitUpdateHistory($repoPath) {
    $raw = gitUpdateRun('git log -8 --date=format:"%d/%m/%Y %I:%M %p" --pretty=format:"%h%x1f%ad%x1f%an%x1f%s"', $repoPath);
    $rows = array();
    foreach (explode("\n", $raw) as $line) {
        $parts = explode("\x1f", $line);
        if (count($parts) === 4) {
            $rows[] = array(
                'hash' => $parts[0],
                'date' => $parts[1],
                'author' => $parts[2],
                'message' => $parts[3]
            );
        }
    }
    return $rows;
}

function gitUpdateStatusPayload($repoPath, $deployLog) {
    $hash = gitUpdateRun('git rev-parse --short HEAD', $repoPath);
    $message = gitUpdateRun('git log -1 --pretty=format:%s', $repoPath);
    $local = gitUpdateRun('git rev-parse HEAD', $repoPath);
    $remote = gitUpdateRun('git ls-remote origin refs/heads/main | awk "{print $1}"', $repoPath);
    $status = 'Unknown';
    $detail = 'Remote check unavailable.';

    if ($local !== '' && $remote !== '') {
        if ($local === $remote) {
            $status = 'Up to date';
            $detail = 'Local repo matches origin/main.';
        } else {
            $status = 'Update available';
            $detail = 'GitHub has changes not deployed locally.';
        }
    }

    $lastDeployTitle = 'No deploy recorded';
    $lastDeployDetail = '';
    if (is_file($deployLog)) {
        $lines = file($deployLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($lines)) {
            $lastDeployTitle = 'Recorded';
            $lastDeployDetail = end($lines);
        }
    }

    return array(
        'success' => true,
        'current' => array('short_hash' => $hash, 'message' => $message),
        'remote' => array('status' => $status, 'detail' => $detail),
        'last_deploy' => array('title' => $lastDeployTitle, 'detail' => $lastDeployDetail),
        'history' => gitUpdateHistory($repoPath)
    );
}

if ($action === 'status') {
    echo json_encode(gitUpdateStatusPayload($repoPath, $deployLog));
    exit;
}

if ($action === 'deploy') {
    if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
        echo json_encode(['success' => false, 'message' => 'A Git update is already running.']);
        exit;
    }

    file_put_contents($lockFile, (string)time());
    $outputParts = array();

    try {
        if (!is_dir($repoPath) || !is_dir($deployPath)) {
            throw new Exception('Repository or deploy path does not exist.');
        }

        $outputParts[] = '$ git pull --ff-only origin main';
        $pullOutput = gitUpdateRun('git pull --ff-only origin main', $repoPath);
        $outputParts[] = $pullOutput;

        $outputParts[] = '$ rsync app/ to public_html/';
        $rsyncCommand = '/bin/rsync -av --exclude=".git" --exclude=".cpanel.yml" --exclude="_notes" --exclude="*/_notes" --exclude="files/" --exclude="web_config_ft.php" ' . escapeshellarg($repoPath . '/app/') . ' ' . escapeshellarg($deployPath . '/');
        $rsyncOutput = gitUpdateRun($rsyncCommand, $repoPath);
        $outputParts[] = $rsyncOutput;

        $hash = gitUpdateRun('git rev-parse --short HEAD', $repoPath);
        $logLine = date('Y-m-d H:i:s') . ' | user_id=' . (int)$_SESSION['session_user_id'] . ' | ' . $hash;
        file_put_contents($deployLog, $logLine . PHP_EOL, FILE_APPEND);

        @unlink($lockFile);
        echo json_encode([
            'success' => true,
            'message' => 'Git pull and deploy completed.',
            'output' => implode("\n\n", $outputParts),
            'status' => gitUpdateStatusPayload($repoPath, $deployLog)
        ]);
        exit;
    } catch (Exception $e) {
        @unlink($lockFile);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'output' => implode("\n\n", $outputParts)
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
