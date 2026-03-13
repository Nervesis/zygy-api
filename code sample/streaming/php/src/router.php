<?php
// Simple router for the PHP built-in server
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Route API requests
if (strpos($requestUri, '/api/') === 0) {
    if (strpos($requestUri, '/api/chat') === 0 && $requestMethod === 'POST') {
        require 'api/chat.php';
        exit;
    }
}

// Route to index.php for all other requests
if (file_exists(__DIR__ . $requestUri) && is_file(__DIR__ . $requestUri)) {
    return false;
}

require 'index.php';
