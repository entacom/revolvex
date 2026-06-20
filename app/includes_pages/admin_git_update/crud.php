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
$repoOwner = 'entacom';
$repoName = 'revolvex';
$branch = 'main';
$deployPath = '/home/revolvexcom/public_html';
$fallbackRepoPath = '/home/revolvexcom/revolvex';
$deployLog = $fallbackRepoPath . '/deploy_history.log';
$lockFile = sys_get_temp_dir() . '/revolvex_git_update.lock';

function gitUpdateHttpGet($url, $binary = false) {
    $headers = array(
        'User-Agent: RevolveX-Git-Update',
        'Accept: application/vnd.github+json'
    );

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            return array('ok' => false, 'body' => '', 'message' => $error ?: 'HTTP ' . $status . ' from GitHub.');
        }

        return array('ok' => true, 'body' => $body, 'message' => '');
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 120
            )
        ));
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return array('ok' => false, 'body' => '', 'message' => 'Could not read GitHub URL with file_get_contents.');
        }
        return array('ok' => true, 'body' => $body, 'message' => '');
    }

    return array('ok' => false, 'body' => '', 'message' => 'PHP cannot download from GitHub. Enable cURL or allow_url_fopen.');
}

function gitUpdateGithubCommit($repoOwner, $repoName, $branch) {
    $url = 'https://api.github.com/repos/' . rawurlencode($repoOwner) . '/' . rawurlencode($repoName) . '/commits/' . rawurlencode($branch);
    $response = gitUpdateHttpGet($url);
    if (!$response['ok']) {
        return array('ok' => false, 'message' => $response['message']);
    }

    $data = json_decode($response['body'], true);
    if (!is_array($data) || empty($data['sha'])) {
        return array('ok' => false, 'message' => 'GitHub commit response was not valid.');
    }

    return array(
        'ok' => true,
        'sha' => $data['sha'],
        'short_sha' => substr($data['sha'], 0, 7),
        'message' => isset($data['commit']['message']) ? strtok($data['commit']['message'], "\n") : '',
        'author' => isset($data['commit']['author']['name']) ? $data['commit']['author']['name'] : '',
        'date' => isset($data['commit']['author']['date']) ? $data['commit']['author']['date'] : ''
    );
}

function gitUpdateGithubHistory($repoOwner, $repoName, $branch) {
    $url = 'https://api.github.com/repos/' . rawurlencode($repoOwner) . '/' . rawurlencode($repoName) . '/commits?sha=' . rawurlencode($branch) . '&per_page=8';
    $response = gitUpdateHttpGet($url);
    if (!$response['ok']) {
        return array();
    }

    $data = json_decode($response['body'], true);
    if (!is_array($data)) {
        return array();
    }

    $rows = array();
    foreach ($data as $commit) {
        if (empty($commit['sha'])) {
            continue;
        }
        $date = isset($commit['commit']['author']['date']) ? strtotime($commit['commit']['author']['date']) : false;
        $rows[] = array(
            'hash' => substr($commit['sha'], 0, 7),
            'date' => $date ? date('d/m/Y h:i A', $date) : '',
            'author' => isset($commit['commit']['author']['name']) ? $commit['commit']['author']['name'] : '',
            'message' => isset($commit['commit']['message']) ? strtok($commit['commit']['message'], "\n") : ''
        );
    }
    return $rows;
}

function gitUpdateLastDeploy($deployLog) {
    if (!is_file($deployLog)) {
        return array('title' => 'No deploy recorded', 'detail' => '', 'sha' => '');
    }

    $lines = file($deployLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        return array('title' => 'No deploy recorded', 'detail' => '', 'sha' => '');
    }

    $lastLine = end($lines);
    $sha = '';
    if (preg_match('/\|\s*([a-f0-9]{7,40})\s*$/i', $lastLine, $matches)) {
        $sha = $matches[1];
    }

    return array('title' => 'Recorded', 'detail' => $lastLine, 'sha' => $sha);
}

function gitUpdateDiagnostics($deployPath, $fallbackRepoPath) {
    return array(
        'deploy_path' => $deployPath,
        'deploy_exists' => is_dir($deployPath),
        'deploy_writable' => is_writable($deployPath),
        'repo_path' => $fallbackRepoPath,
        'repo_exists' => is_dir($fallbackRepoPath),
        'repo_writable' => is_dir($fallbackRepoPath) && is_writable($fallbackRepoPath),
        'curl_available' => function_exists('curl_init'),
        'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
        'zip_available' => class_exists('ZipArchive'),
        'temp_writable' => is_writable(sys_get_temp_dir()),
        'exec_available' => function_exists('exec'),
        'shell_exec_available' => function_exists('shell_exec')
    );
}

function gitUpdateStatusPayload($repoOwner, $repoName, $branch, $deployPath, $fallbackRepoPath, $deployLog) {
    $latest = gitUpdateGithubCommit($repoOwner, $repoName, $branch);
    $lastDeploy = gitUpdateLastDeploy($deployLog);

    $currentHash = $lastDeploy['sha'] ? substr($lastDeploy['sha'], 0, 7) : 'Unknown';
    $currentMessage = $lastDeploy['detail'];
    $remoteStatus = 'Unknown';
    $remoteDetail = $latest['ok'] ? 'GitHub latest: ' . $latest['short_sha'] . ' ' . $latest['message'] : $latest['message'];

    if ($latest['ok']) {
        if ($lastDeploy['sha'] && stripos($latest['sha'], $lastDeploy['sha']) === 0) {
            $remoteStatus = 'Up to date';
            $remoteDetail = 'Last deployed version matches GitHub main.';
        } else {
            $remoteStatus = 'Update available';
            $remoteDetail = 'GitHub has changes not recorded as deployed by this page.';
        }
    }

    return array(
        'success' => true,
        'current' => array('short_hash' => $currentHash, 'message' => $currentMessage),
        'remote' => array('status' => $remoteStatus, 'detail' => $remoteDetail),
        'last_deploy' => array('title' => $lastDeploy['title'], 'detail' => $lastDeploy['detail']),
        'history' => gitUpdateGithubHistory($repoOwner, $repoName, $branch),
        'diagnostics' => gitUpdateDiagnostics($deployPath, $fallbackRepoPath)
    );
}

function gitUpdateRemoveDirectory($path) {
    if (!is_dir($path)) {
        return;
    }
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath) && !is_link($fullPath)) {
            gitUpdateRemoveDirectory($fullPath);
        } else {
            @unlink($fullPath);
        }
    }
    @rmdir($path);
}

function gitUpdateShouldSkip($relativePath) {
    $relativePath = str_replace('\\', '/', trim($relativePath, '/'));
    $skip = array('.git', '.cpanel.yml', '_notes', 'files', 'web_config_ft.php');
    foreach ($skip as $blocked) {
        if ($relativePath === $blocked || strpos($relativePath, $blocked . '/') === 0 || strpos($relativePath, '/_notes/') !== false) {
            return true;
        }
    }
    return false;
}

function gitUpdateCopyDirectory($source, $destination, $baseSource, &$copied, &$skipped) {
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true) && !is_dir($destination)) {
            throw new Exception('Could not create deploy directory: ' . $destination);
        }
    }

    $items = scandir($source);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
        $relativePath = ltrim(str_replace('\\', '/', substr($sourcePath, strlen($baseSource))), '/');

        if (gitUpdateShouldSkip($relativePath)) {
            $skipped++;
            continue;
        }

        $destinationPath = $destination . DIRECTORY_SEPARATOR . $item;
        if (is_dir($sourcePath)) {
            gitUpdateCopyDirectory($sourcePath, $destinationPath, $baseSource, $copied, $skipped);
        } else {
            if (!copy($sourcePath, $destinationPath)) {
                throw new Exception('Could not copy file: ' . $relativePath);
            }
            @chmod($destinationPath, 0644);
            $copied++;
        }
    }
}

function gitUpdateFindExtractedAppPath($extractPath) {
    $items = scandir($extractPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $candidate = $extractPath . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'app';
        if (is_dir($candidate)) {
            return $candidate;
        }
    }
    return '';
}

function gitUpdateCleanProtectedPublicFiles($path, &$removedFiles, &$removedDirs) {
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath) && !is_link($fullPath)) {
            if ($item === '_notes') {
                gitUpdateRemoveDirectory($fullPath);
                $removedDirs++;
                continue;
            }

            gitUpdateCleanProtectedPublicFiles($fullPath, $removedFiles, $removedDirs);
            continue;
        }

        if (gitUpdateIsProtectedPublicFile($item) && @unlink($fullPath)) {
            $removedFiles++;
        }
    }
}

function gitUpdateIsProtectedPublicFile($fileName) {
    if (preg_match('/_orig\.php$/i', $fileName)) {
        return true;
    }

    if (preg_match('/\.(log|sql|bak|backup|old|orig|p12|crt|key|pem|zip|tar|gz)$/i', $fileName)) {
        return true;
    }

    return in_array(strtolower($fileName), array(
        'info.php',
        'test_autoload.php',
        'first_test.php',
        'web_config_ft.php'
    ), true);
}

if ($action === 'status') {
    echo json_encode(gitUpdateStatusPayload($repoOwner, $repoName, $branch, $deployPath, $fallbackRepoPath, $deployLog));
    exit;
}

if ($action === 'deploy') {
    if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
        echo json_encode(['success' => false, 'message' => 'A Git update is already running.']);
        exit;
    }

    file_put_contents($lockFile, (string)time());
    $outputParts = array();
    $tempRoot = sys_get_temp_dir() . '/revolvex_deploy_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
    $zipFile = $tempRoot . '/revolvex.zip';
    $extractPath = $tempRoot . '/extract';

    try {
        if (!is_dir($deployPath) || !is_writable($deployPath)) {
            throw new Exception('Deploy path is missing or not writable: ' . $deployPath);
        }
        if (!class_exists('ZipArchive')) {
            throw new Exception('PHP ZipArchive is not enabled. Enable the zip PHP extension in cPanel.');
        }
        if (!mkdir($tempRoot, 0755, true) || !mkdir($extractPath, 0755, true)) {
            throw new Exception('Could not create temporary deploy folder.');
        }

        $latest = gitUpdateGithubCommit($repoOwner, $repoName, $branch);
        if (!$latest['ok']) {
            throw new Exception('Could not check GitHub latest commit. ' . $latest['message']);
        }

        $zipUrl = 'https://github.com/' . rawurlencode($repoOwner) . '/' . rawurlencode($repoName) . '/archive/refs/heads/' . rawurlencode($branch) . '.zip';
        $outputParts[] = 'Downloading GitHub ZIP: ' . $zipUrl;
        $zipResponse = gitUpdateHttpGet($zipUrl, true);
        if (!$zipResponse['ok']) {
            throw new Exception('GitHub ZIP download failed. ' . $zipResponse['message']);
        }
        file_put_contents($zipFile, $zipResponse['body']);

        $outputParts[] = 'Extracting ZIP.';
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception('Could not open downloaded GitHub ZIP.');
        }
        $zip->extractTo($extractPath);
        $zip->close();

        $appSource = gitUpdateFindExtractedAppPath($extractPath);
        if ($appSource === '') {
            throw new Exception('Downloaded ZIP did not contain an app folder.');
        }

        $copied = 0;
        $skipped = 0;
        $removedFiles = 0;
        $removedDirs = 0;
        $legacyAppPath = $deployPath . DIRECTORY_SEPARATOR . 'app';
        if (is_dir($legacyAppPath)) {
            $outputParts[] = 'Removing legacy nested public_html/app folder.';
            gitUpdateRemoveDirectory($legacyAppPath);
            $removedDirs++;
        }
        $outputParts[] = 'Cleaning protected legacy files from public_html.';
        gitUpdateCleanProtectedPublicFiles($deployPath, $removedFiles, $removedDirs);
        $outputParts[] = 'Removed protected files: ' . $removedFiles;
        $outputParts[] = 'Removed _notes folders: ' . $removedDirs;
        $outputParts[] = 'Copying app folder to public_html.';
        gitUpdateCopyDirectory($appSource, $deployPath, $appSource, $copied, $skipped);
        $outputParts[] = 'Copied files: ' . $copied;
        $outputParts[] = 'Skipped protected paths: ' . $skipped;

        $logLine = date('Y-m-d H:i:s') . ' | user_id=' . (int)$_SESSION['session_user_id'] . ' | ' . $latest['sha'];
        @file_put_contents($deployLog, $logLine . PHP_EOL, FILE_APPEND);

        gitUpdateRemoveDirectory($tempRoot);
        @unlink($lockFile);

        echo json_encode([
            'success' => true,
            'message' => 'GitHub ZIP deploy completed.',
            'output' => implode("\n", $outputParts),
            'status' => gitUpdateStatusPayload($repoOwner, $repoName, $branch, $deployPath, $fallbackRepoPath, $deployLog)
        ]);
        exit;
    } catch (Exception $e) {
        gitUpdateRemoveDirectory($tempRoot);
        @unlink($lockFile);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'output' => implode("\n", $outputParts)
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
