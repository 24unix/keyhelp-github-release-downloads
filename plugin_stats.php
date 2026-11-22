<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$confFile = __DIR__ . '/download_box.conf';

function parseGitHubReposFromConf(string $confPath): array {
    if (!is_readable($confPath)) {
        return [];
    }

    $lines = file($confPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $repos = [];
    $seen  = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, ';') || str_starts_with($trimmed, '//')) {
            continue;
        }
        if (str_contains($trimmed, '=')) {
            [, $trimmed] = explode('=', $trimmed, 2);
            $trimmed = trim($trimmed, " \t\n\r\0\x0B\"'");
        }
        if ($trimmed === '') {
            continue;
        }

        $normalizedUrl = rtrim($trimmed, "/ \t\n\r\0\x0B");
        if (preg_match('#^https?://(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#i', $normalizedUrl, $m)) {
            $ownerRepo = $m[1] . '/' . $m[2];
            $key = strtolower($ownerRepo);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $repos[] = [
                    'repo' => $ownerRepo,
                    'url'  => 'https://github.com/' . $ownerRepo,
                ];
            }
        }
    }

    return $repos;
}

$repos     = parseGitHubReposFromConf($confFile);
$confHash  = is_readable($confFile) ? md5_file($confFile) : 'empty';
$cacheFile = sys_get_temp_dir() . '/kh_plugin_stats_gh_' . $confHash . '.json';
$cacheTtl  = 60; // Sekunden Schutz vor Doppel-Reloads

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    readfile($cacheFile);
    exit;
}

function fetchJson(string $url): array {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 8,
            'header'  => "User-Agent: KeyHelp-Dashboard-Stats\r\nAccept: application/vnd.github+json\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['_error' => 'Request fehlgeschlagen'];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['_error' => 'Ungültiges JSON'];
}

function loadGhReleases(string $repo): array {
    $rels = fetchJson("https://api.github.com/repos/{$repo}/releases");
    if (isset($rels['_error'])) {
        return ['latest' => '-', 'total' => 0, 'releases' => [], 'error' => $rels['_error']];
    }
    if (isset($rels['message']) && !array_is_list($rels)) {
        return ['latest' => '-', 'total' => 0, 'releases' => [], 'error' => (string)$rels['message']];
    }

    $total  = 0;
    $latest = '-';
    $parsed = [];

    foreach ($rels as $idx => $rel) {
        $tag = (string)($rel['tag_name'] ?? '-');
        if ($idx === 0) {
            $latest = $tag;
        }
        $assets = [];
        $relSum = 0;
        foreach (($rel['assets'] ?? []) as $a) {
            $cnt     = (int)($a['download_count'] ?? 0);
            $relSum += $cnt;
            $total  += $cnt;
            $assets[] = [
                'name'  => (string)($a['name'] ?? ''),
                'count' => $cnt,
            ];
        }
        $parsed[] = [
            'tag'    => $tag,
            'sum'    => $relSum,
            'assets' => $assets,
        ];
    }

    return [
        'latest'   => $latest,
        'total'    => $total,
        'releases' => $parsed,
        'error'    => null,
    ];
}

$items = [];
foreach ($repos as $entry) {
    $stats = loadGhReleases($entry['repo']);
    $items[] = [
        'repo'     => $entry['repo'],
        'url'      => $entry['url'],
        'latest'   => $stats['latest'],
        'total'    => $stats['total'],
        'releases' => $stats['releases'],
        'error'    => $stats['error'],
    ];
}

$result = [
    'updated' => date('H:i:s'),
    'items'   => $items,
];

$json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@file_put_contents($cacheFile, $json);
echo $json;