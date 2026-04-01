<?php
/**
 * OSPanel Dashboard — State Generator
 * Developer: x0doit (https://github.com/x0doit)
 * Generates state.json with module/project/log data
 */

$rootDir = realpath(__DIR__ . '/../../../');
if (!$rootDir) {
    $rootDir = 'C:/OSPanel';
}

$state = [
    'version'   => '',
    'generated' => date('c'),
    'api_token' => '',
    'modules'   => [],
    'projects'  => [],
    'logs'      => [],
];

// --- API token from osp.bat ---
$ospBat = "$rootDir/bin/osp.bat";
if (is_file($ospBat)) {
    $batContent = file_get_contents($ospBat);
    if (preg_match('/api\/cmd\/([A-F0-9]{64})\//i', $batContent, $tm)) {
        $state['api_token'] = $tm[1];
    }
}

// --- OSPanel version from general.log ---
$generalLog = "$rootDir/logs/general.log";
if (is_file($generalLog)) {
    $fh = fopen($generalLog, 'r');
    if ($fh) {
        $lines = [];
        while (($line = fgets($fh)) !== false) {
            $lines[] = $line;
            if (count($lines) > 200) break;
        }
        fclose($fh);
        foreach ($lines as $line) {
            if (preg_match('/Open Server Panel (v[\d.]+)/', $line, $m)) {
                $state['version'] = $m[1];
                break;
            }
        }
    }
}

// --- Modules ---
$configDir  = "$rootDir/config";
$modulesDir = "$rootDir/modules";

$configDirs = is_dir($configDir) ? scandir($configDir) : [];
foreach ($configDirs as $name) {
    if ($name === '.' || $name === '..') continue;
    $configIni = "$configDir/$name/module.ini";
    $metaIni   = "$modulesDir/$name/ospanel_data/module.ini";
    if (!is_file($configIni)) continue;

    $config = parse_ini_file($configIni, true, INI_SCANNER_RAW);
    $meta   = is_file($metaIni) ? parse_ini_file($metaIni, true, INI_SCANNER_RAW) : [];

    $main   = $config['main'] ?? $config ?? [];
    $mMain  = $meta['main'] ?? $meta ?? [];

    $enabled = false;
    if (isset($main['enabled'])) {
        $enabled = $main['enabled'] === '1' || $main['enabled'] === 'on' || $main['enabled'] === 'yes';
    }

    $module = [
        'name'     => $name,
        'category' => trim($mMain['category'] ?? ''),
        'version'  => trim($mMain['version'] ?? ''),
        'enabled'  => $enabled,
        'profile'  => trim($main['profile'] ?? 'default'),
    ];

    // Check if process is actually running
    $module['running'] = false;
    if ($enabled) {
        $logFile = "$rootDir/logs/general.log";
        if (is_file($logFile)) {
            $content = file_get_contents($logFile);
            if (strpos($content, "$name (default): Рабочий процесс модуля запущен") !== false) {
                // Check if no subsequent stop/error for this module
                $lastStart = strrpos($content, "$name (default): Рабочий процесс модуля запущен");
                $lastStop  = strrpos($content, "$name (default): Модуль остановлен");
                $lastError = strrpos($content, "Модуль: $name");
                if ($lastStart !== false) {
                    $stopped = ($lastStop !== false && $lastStop > $lastStart);
                    $module['running'] = !$stopped;
                }
            }
        }
    }

    $state['modules'][] = $module;
}

// Sort: enabled first, then by category, then by name
usort($state['modules'], function ($a, $b) {
    if ($a['enabled'] !== $b['enabled']) return $b['enabled'] ? 1 : -1;
    $cat = strcmp($a['category'], $b['category']);
    if ($cat !== 0) return $cat;
    return strcmp($a['name'], $b['name']);
});

// --- Projects ---
$homeDir = "$rootDir/home";
if (is_dir($homeDir)) {
    $dirs = scandir($homeDir);
    foreach ($dirs as $name) {
        if ($name === '.' || $name === '..') continue;
        $ini = "$homeDir/$name/.osp/project.ini";
        if (!is_file($ini)) continue;

        $sections = parse_ini_file($ini, true, INI_SCANNER_RAW);
        foreach ($sections as $domain => $cfg) {
            $enabled = false;
            $pe = $cfg['project_enabled'] ?? 'off';
            if ($pe === 'on' || $pe === '1' || $pe === 'yes') {
                $enabled = true;
            }

            $state['projects'][] = [
                'domain'      => $domain,
                'folder'      => $name,
                'enabled'     => $enabled,
                'http_engine' => trim($cfg['http_engine'] ?? 'auto'),
                'php_engine'  => trim($cfg['php_engine'] ?? 'auto'),
                'node_engine' => trim($cfg['node_engine'] ?? 'auto'),
                'tls'         => ($cfg['tls_enabled'] ?? 'off') === 'on',
                'web_root'    => trim($cfg['web_root'] ?? ''),
            ];
        }
    }
}

usort($state['projects'], function ($a, $b) {
    if ($a['enabled'] !== $b['enabled']) return $b['enabled'] ? 1 : -1;
    return strcmp($a['domain'], $b['domain']);
});

// --- Recent logs (last 50 lines of general.log) ---
if (is_file($generalLog)) {
    $allLines = file($generalLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $state['logs'] = array_slice($allLines, -80);
}

// --- Write JSON ---
$json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$outFile = __DIR__ . '/state.json';
file_put_contents($outFile, $json);

echo "Generated: $outFile\n";
echo "Modules: " . count($state['modules']) . "\n";
echo "Projects: " . count($state['projects']) . "\n";
