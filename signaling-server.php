<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use current directory for message storage (more reliable on shared hosting)
$dataDir = __DIR__ . '/temp_messages/';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

$messagesFile = $dataDir . 'messages.txt';

function getMessages() {
    global $messagesFile;
    if (!file_exists($messagesFile)) {
        return [];
    }

    $content = @file_get_contents($messagesFile);
    if (!$content) return [];

    $lines = explode("\n", trim($content));
    $messages = [];

    foreach ($lines as $line) {
        if ($line) {
            $msg = @json_decode($line, true);
            if ($msg) $messages[] = $msg;
        }
    }

    return $messages;
}

function saveMessage($message) {
    global $messagesFile;
    $message['timestamp'] = time();
    $message['id'] = uniqid();

    $line = json_encode($message) . "\n";
    @file_put_contents($messagesFile, $line, FILE_APPEND | LOCK_EX);

    return $message['id'];
}

function cleanOldMessages() {
    global $messagesFile;
    $messages = getMessages();
    $cutoff = time() - 300; // Keep messages for 5 minutes

    $kept = [];
    foreach ($messages as $msg) {
        if ($msg['timestamp'] > $cutoff) {
            $kept[] = $msg;
        }
    }

    if (count($kept) < count($messages)) {
        $content = '';
        foreach ($kept as $msg) {
            $content .= json_encode($msg) . "\n";
        }
        @file_put_contents($messagesFile, $content, LOCK_EX);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = @json_decode(file_get_contents('php://input'), true);

        if ($input && isset($input['sessionId'])) {
            cleanOldMessages();
            saveMessage($input);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid input']);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sessionId = $_GET['session'] ?? '';
        $since = intval($_GET['since'] ?? 0);

        if (!$sessionId) {
            echo json_encode(['error' => 'Session ID required']);
            exit;
        }

        cleanOldMessages();
        $messages = getMessages();
        $filtered = [];

        foreach ($messages as $msg) {
            if ($msg['sessionId'] === $sessionId && $msg['timestamp'] > $since) {
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
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>