<?php
/**
 * Simple WebRTC Signaling Server
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$messagesFile = 'messages.json';

// Initialize file if it doesn't exist
if (!file_exists($messagesFile)) {
    file_put_contents($messagesFile, '[]');
}

function getMessages() {
    global $messagesFile;
    $content = @file_get_contents($messagesFile);
    return $content ? json_decode($content, true) : [];
}

function saveMessage($message) {
    global $messagesFile;
    $messages = getMessages();
    $message['timestamp'] = time();
    $messages[] = $message;

    // Keep only last 20 messages
    if (count($messages) > 20) {
        $messages = array_slice($messages, -20);
    }

    @file_put_contents($messagesFile, json_encode($messages));
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if ($input && isset($input['sessionId'])) {
            saveMessage($input);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid input']);
        }

    } else if ($method === 'GET') {
        $sessionId = $_GET['session'] ?? '';
        $since = intval($_GET['since'] ?? 0);

        if (!$sessionId) {
            echo json_encode(['error' => 'Session ID required']);
            exit;
        }

        $messages = getMessages();
        $filtered = [];

        foreach ($messages as $msg) {
            if (isset($msg['sessionId']) && $msg['sessionId'] === $sessionId && $msg['timestamp'] > $since) {
                $filtered[] = $msg;
            }
        }

        echo json_encode([
            'messages' => $filtered,
            'timestamp' => time()
        ]);

    } else {
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
}
?>