<?php
/**
 * OSPanel Dashboard — Backend API
 * Developer: x0doit (https://github.com/x0doit)
 * Handles project CRUD + state regeneration
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$rootDir = realpath(__DIR__ . '/../../../');
if (!$rootDir) $rootDir = 'C:/OSPanel';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Support ?action= routing (Apache/direct access)
$action = $_GET['action'] ?? '';
if ($action) $uri = '/' . $action;

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError($msg, $code = 400) {
    jsonResponse(['error' => $msg], $code);
}

function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function regenerateState() {
    global $rootDir;
    $php = $rootDir . '/modules/PHP-8.3/php.exe';
    if (!is_file($php)) $php = $rootDir . '/modules/PHP-8.5/php.exe';
    $script = __DIR__ . '/generate.php';
    exec("\"$php\" \"$script\" 2>&1", $out, $ret);
    return $ret === 0;
}

function sanitizeDomain($domain) {
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/[^a-z0-9.\-]/', '', $domain);
    return $domain;
}

// ---- Routes ----

// GET /state — regenerate and return state.json
if ($uri === '/state' && $method === 'GET') {
    regenerateState();
    $stateFile = __DIR__ . '/state.json';
    if (is_file($stateFile)) {
        header('Content-Type: application/json; charset=utf-8');
        readfile($stateFile);
        exit;
    }
    jsonError('State file not found', 500);
}

// POST /project — create project
if ($uri === '/project' && $method === 'POST') {
    $body = getBody();
    $domain = sanitizeDomain($body['domain'] ?? '');
    if (!$domain || strlen($domain) < 3) jsonError('Invalid domain name');
    if (!preg_match('/\.[a-z]{2,}$/', $domain)) jsonError('Domain must have extension (e.g. .local)');

    $homeDir = "$rootDir/home/$domain";
    if (is_dir($homeDir)) jsonError('Project already exists');

    $httpEngine  = $body['http_engine'] ?? 'Apache';
    $phpEngine   = $body['php_engine'] ?? 'auto';
    $tlsEnabled  = !empty($body['tls_enabled']) ? 'on' : 'off';
    $webRoot     = $body['web_root'] ?? '{base_dir}';
    $enabled     = !empty($body['enabled']) ? 'on' : 'off';

    // Create directory structure
    if (!mkdir("$homeDir/.osp", 0755, true)) {
        jsonError('Failed to create project directory');
    }

    // Create index.html placeholder
    file_put_contents("$homeDir/index.html", "<!DOCTYPE html>\n<html>\n<head><meta charset=\"utf-8\"><title>$domain</title></head>\n<body><h1>$domain</h1><p>Project created via OSPanel Dashboard</p></body>\n</html>\n");

    // Write project.ini
    $ini = "[$domain]\n\n";
    $ini .= "app_start_command = \n";
    $ini .= "backend_enabled   = off\n";
    $ini .= "backend_ip        = auto\n";
    $ini .= "backend_port      = auto\n";
    $ini .= "base_url          = {host_scheme}://{host_decoded}{scheme_port}\n";
    $ini .= "bind_ip           = auto\n";
    $ini .= "environment       = System\n";
    $ini .= "http_engine       = $httpEngine\n";
    $ini .= "http_port         = 80\n";
    $ini .= "https_port        = 443\n";
    $ini .= "node_engine       = auto\n";
    $ini .= "php_engine        = $phpEngine\n";
    $ini .= "primary_domain    = off\n";
    $ini .= "project_category  = \n";
    $ini .= "project_enabled   = $enabled\n";
    $ini .= "project_root      = {base_dir}\n";
    $ini .= "server_aliases    = www.{host}\n";
    $ini .= "terminal_codepage = 65001\n";
    $ini .= "tls_cert_file     = auto\n";
    $ini .= "tls_enabled       = $tlsEnabled\n";
    $ini .= "tls_key_file      = auto\n";
    $ini .= "web_root          = $webRoot\n";

    file_put_contents("$homeDir/.osp/project.ini", $ini);

    regenerateState();
    jsonResponse(['ok' => true, 'domain' => $domain, 'message' => 'Project created. Restart OSPanel to apply.']);
}

// PUT /project — update project
if ($uri === '/project' && $method === 'PUT') {
    $body = getBody();
    $domain = sanitizeDomain($body['domain'] ?? '');
    $folder = $body['folder'] ?? $domain;
    if (!$domain) jsonError('Missing domain');

    $iniPath = "$rootDir/home/$folder/.osp/project.ini";
    if (!is_file($iniPath)) jsonError('Project not found');

    $sections = parse_ini_file($iniPath, true, INI_SCANNER_RAW);
    if (!isset($sections[$domain])) jsonError("Section [$domain] not found in project.ini");

    $cfg = &$sections[$domain];
    if (isset($body['http_engine']))  $cfg['http_engine']      = $body['http_engine'];
    if (isset($body['php_engine']))   $cfg['php_engine']       = $body['php_engine'];
    if (isset($body['tls_enabled']))  $cfg['tls_enabled']      = $body['tls_enabled'] ? 'on' : 'off';
    if (isset($body['enabled']))      $cfg['project_enabled']  = $body['enabled'] ? 'on' : 'off';
    if (isset($body['web_root']))     $cfg['web_root']         = $body['web_root'];

    // Rewrite INI
    $ini = '';
    foreach ($sections as $sec => $vals) {
        $ini .= "[$sec]\n\n";
        foreach ($vals as $k => $v) {
            $ini .= str_pad($k, 21) . " = $v\n";
        }
        $ini .= "\n";
    }
    file_put_contents($iniPath, $ini);

    regenerateState();
    jsonResponse(['ok' => true, 'message' => 'Project updated. Restart OSPanel to apply.']);
}

// DELETE /project?domain=xxx&folder=yyy — delete project
if ($uri === '/project' && $method === 'DELETE') {
    $domain = sanitizeDomain($_GET['domain'] ?? '');
    $folder = $_GET['folder'] ?? $domain;
    if (!$domain) jsonError('Missing domain');

    $homeDir = "$rootDir/home/$folder";
    if (!is_dir($homeDir)) jsonError('Project directory not found');

    // Safety: only delete within home directory
    $realPath = realpath($homeDir);
    $realHome = realpath("$rootDir/home");
    if (!$realPath || !$realHome || strpos($realPath, $realHome) !== 0 || $realPath === $realHome) {
        jsonError('Invalid project path');
    }

    // Recursive delete
    $it = new RecursiveDirectoryIterator($realPath, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) rmdir($file->getRealPath());
        else unlink($file->getRealPath());
    }
    rmdir($realPath);

    regenerateState();
    jsonResponse(['ok' => true, 'message' => 'Project deleted. Restart OSPanel to apply.']);
}

// Fallback: serve static files
return false;
