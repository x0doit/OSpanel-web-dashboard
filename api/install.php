<?php
/**
 * OSPanel Dashboard — Installer Backend
 * Developer: x0doit (https://github.com/x0doit)
 */

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$sourceDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));

function resp($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---- Detect OSPanel on all drives ----
if ($action === 'detect') {
    foreach (range('C', 'G') as $d) {
        $p = "$d:/OSPanel";
        if (is_file("$p/bin/ospanel.exe")) {
            resp(['found' => true, 'root' => $p]);
        }
    }
    resp(['found' => false, 'root' => '']);
}

// ---- Check environment ----
if ($action === 'check') {
    $root = rtrim(str_replace('\\', '/', $_GET['root'] ?? ''), '/');

    $isOsp    = is_file("$root/bin/ospanel.exe");
    $pub      = "$root/system/public_html";
    $writable = $isOsp && is_dir($pub) && is_writable($pub);
    $srcOk    = is_file("$sourceDir/index.html") && is_file("$sourceDir/api/backend.php");

    $hasDash = false;
    if (is_file("$pub/index.html")) {
        $hasDash = strpos(file_get_contents("$pub/index.html"), 'OSPanel Dashboard') !== false;
    }

    $checks = [];
    $checks[] = ['label' => 'OSPanel',           'ok' => $isOsp,    'detail' => $isOsp ? $root : 'ospanel.exe not found at this path'];
    $checks[] = ['label' => 'PHP',               'ok' => version_compare(PHP_VERSION, '7.2', '>='), 'detail' => PHP_VERSION];
    $checks[] = ['label' => 'Write permissions',  'ok' => $writable, 'detail' => $writable ? 'OK' : 'Cannot write to public_html'];
    $checks[] = ['label' => 'Dashboard source',   'ok' => $srcOk,    'detail' => $srcOk ? 'OK' : 'Files missing in installer folder'];
    $checks[] = ['label' => 'Current status',     'ok' => true,      'detail' => $hasDash ? 'Installed (will update)' : 'Not installed'];

    resp([
        'checks'    => $checks,
        'ready'     => $isOsp && $writable && $srcOk,
        'installed' => $hasDash
    ]);
}

// ---- Install ----
if ($action === 'install') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $root = rtrim(str_replace('\\', '/', trim($body['root'] ?? '')), '/');

    if (!$root || !is_file("$root/bin/ospanel.exe")) {
        resp(['error' => 'Invalid OSPanel path'], 400);
    }

    $pub    = "$root/system/public_html";
    $errors = [];
    $copied = 0;

    // Dashboard files to copy
    $files = [
        'index.html', 'favicon.ico', 'x16.png', 'x32.png',
        'api/backend.php', 'api/generate.php',
        'plugins/jquery/jquery.min.js',
        'plugins/toastr/toastr.min.css', 'plugins/toastr/toastr.min.js',
        'plugins/sweetalert2/sweetalert2.all.min.js', 'plugins/sweetalert2/sweetalert2.min.css',
    ];

    // Also copy installer files (will offer to remove later)
    $installerFiles = ['install.html', 'api/install.php'];

    foreach (array_merge($files, $installerFiles) as $f) {
        $src = "$sourceDir/$f";
        $dst = "$pub/$f";
        if (!is_file($src)) {
            if (!in_array($f, $installerFiles)) $errors[] = "Missing: $f";
            continue;
        }
        $dir = dirname($dst);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (copy($src, $dst)) $copied++;
        else $errors[] = "Failed: $f";
    }

    // Copy dashboard_api.bat to OSPanel bin
    $bat = "$sourceDir/bin/dashboard_api.bat";
    if (is_file($bat)) {
        if (copy($bat, "$root/bin/dashboard_api.bat")) $copied++;
        else $errors[] = 'Failed: dashboard_api.bat';
    }

    // Generate state.json using OSPanel's PHP
    $stateOk = false;
    foreach (['8.5', '8.4', '8.3', '8.2', '8.1', '8.0', '7.4'] as $v) {
        $php = "$root/modules/PHP-$v/php.exe";
        if (is_file($php)) break;
        $php = null;
    }
    $gen = "$pub/api/generate.php";
    if ($php && is_file($gen)) {
        exec("\"$php\" \"$gen\" 2>&1", $out, $ret);
        $stateOk = ($ret === 0);
        if (!$stateOk) $errors[] = 'state.json: ' . implode(' ', $out);
    } else {
        $errors[] = 'Cannot generate state.json — PHP module not found';
    }

    resp([
        'ok'      => empty($errors),
        'copied'  => $copied,
        'state'   => $stateOk,
        'errors'  => $errors,
        'message' => empty($errors) ? 'Installation complete!' : 'Completed with issues'
    ]);
}

// ---- Cleanup installer from OSPanel only ----
if ($action === 'cleanup') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $root = rtrim(str_replace('\\', '/', trim($body['root'] ?? '')), '/');
    $pub  = "$root/system/public_html";
    $removed = [];
    $failed  = [];

    $redirect = '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=/"></head><body></body></html>';

    foreach (['install.html', 'api/install.php'] as $f) {
        $path = "$pub/$f";
        if (!is_file($path)) continue;
        chmod($path, 0666);
        if (@unlink($path)) {
            $removed[] = $f;
        } else {
            // File locked by Apache — overwrite with redirect stub
            if (file_put_contents($path, $redirect) !== false) {
                $removed[] = "$f (overwritten)";
            } else {
                $err = error_get_last();
                $failed[] = "$f — " . ($err['message'] ?? 'cannot delete or overwrite');
            }
        }
    }

    resp(['ok' => empty($failed), 'removed' => $removed, 'failed' => $failed]);
}

resp(['error' => 'Unknown action'], 400);
