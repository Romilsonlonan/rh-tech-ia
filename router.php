<?php

/**
 * Router for PHP built-in server
 *
 * Serves pipeline-monitor.html for all routes
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '/' || $uri === '/index.html' || $uri === '/pipeline-monitor.html') {
    $file = __DIR__.'/pipeline-monitor.html';
    if (file_exists($file)) {
        return false;
    }
    http_response_code(404);
    echo "File not found: {$file}";

    return true;
}

return false;
