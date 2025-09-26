<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '{"success":true}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo '{"messages":[],"timestamp":' . time() . '}';
    exit;
}

echo '{"error":"Method not allowed"}';
?>