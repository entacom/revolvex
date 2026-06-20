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
    if (!is_dir($cwd)) {
        return array('ok' => false, 'output' => 'Working directory not found: ' . $cwd);
    }

    $fullCommand = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';

    if (function_exists('exec')) {
        $lines = array();
        $exitCode = 0;
        @exec($fullCommand, $lines, $exitCode);
        return array(
            'ok' => $exitCode === 0,
            'output' => trim(implode("\n", $lines)),
            'exit_code' => $exitCode
        );
    }

    if (function_exists('shell_exec')) {
        $output = @shell_exec($fullCommand);
        return array(
            'ok' => $output !== null,
            'output' => trim((string)$output),
            'exit_code' => null
        );
    }

    return array(
        'ok' => false,
        'output' => 'PHP command execution is disabled on this hosting account. Enable exec or shell_exec, or use cPanel Pull/Deploy.',
        'exit_code' => null
    );
}

function gitUpdateOutput($command, $cwd) {
    $result = gitUpdateRun($command, $cwd);
    return $result['output'];
}

function gitUpdateHistory($repoPath) {
    $raw = gitUpdateOutput('git log -8 --date=format:"%d/%m/%Y %I:%M %p" --pretty=format:"%h%x1f%ad%x1f%an%x1f%s"', $repoPath);
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
    $diagnostics = array(
        'repo_path' => $repoPath,
        'repo_exists' => is_dir($repoPath),
        'deploy_path' => '/home/revolvexcom/public_html',
        'deploy_exists' => is_dir('/home/revolvexcom/public_html'),
        'exec_available' => function_exists('exec'),
        'shell_exec_available' => function_exists('shell_exec')
    );

    $hash = gitUpdateOutput('git rev-parse --short HEAD', $repoPath);
    $message = gitUpdateOutput('git log -1 --pretty=format:%s', $repoPath);
    $local = gitUpdateOutput('git rev-parse HEAD', $repoPath);
    $remoteLine = gitUpdateOutput('git ls-remote origin refs/heads/main', $repoPath);
    $remoteParts = preg_split('/\s+/', trim($remoteLine));
    $remote = isset($remoteParts[0]) ? $remoteParts[0] : '';
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
        'history' => gitUpdateHistory($repoPath),
        'diagnostics' => $diagnostics
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
        $pullResult = gitUpdateRun('git pull --ff-only origin main', $repoPath);
        $outputParts[] = $pullResult['output'];
        if (!$pullResult['ok']) {
            throw new Exception('Git pull failed. ' . $pullResult['output']);
        }

        $outputParts[] = '$ rsync app/ to public_html/';
        $rsyncCommand = '/bin/rsync -av --exclude=".git" --exclude=".cpanel.yml" --exclude="_notes" --exclude="*/_notes" --exclude="files/" --exclude="web_config_ft.php" ' . escapeshellarg($repoPath . '/app/') . ' ' . escapeshellarg($deployPath . '/');
        $rsyncResult = gitUpdateRun($rsyncCommand, $repoPath);
        $outputParts[] = $rsyncResult['output'];
        if (!$rsyncResult['ok']) {
            throw new Exception('Deploy copy failed. ' . $rsyncResult['output']);
        }

        $hash = gitUpdateOutput('git rev-parse --short HEAD', $repoPath);
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
