<?php

declare(strict_types=1);

// Security router for PHP's local development server.
$uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if (str_contains($uri, '..')
    || preg_match('#^/(?:config|database|includes|tests)(?:/|$)#i', $uri)
    || preg_match('#^/storage/(?!uploads/barbers/)#i', $uri)
    || preg_match('/\.(?:sqlite|sqlite3|sql|ini|log|md)$/i', $uri)
    || (str_starts_with($uri, '/storage/uploads/barbers/') && preg_match('/\.(?:php|phtml|phar|php[0-9]*)$/i', $uri))) {
    http_response_code(404);
    exit('Not found');
}

$path = __DIR__ . $uri;
if ($uri !== '/' && is_file($path)) {
    return false;
}

return false;
