<?php
/**
 * WebRTC Signaling Server
 * Handles message passing between screen share peers across networks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple file-based message storage (for demo purposes)
$messagesFile = __DIR__ . '/data/signaling_messages.json';
$sessionsFile = __DIR__ . '/data/sessions.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Initialize files if they don't exist
if (!file_exists($messagesFile)) {
    file_put_contents($messagesFile, json_encode([]));
}
if (!file_exists($sessionsFile)) {
    file_put_contents($sessionsFile, json_encode([]));
}

function getMessages() {
    global $messagesFile;
    $content = file_get_contents($messagesFile);
    return json_decode($content, true) ?: [];
}

function saveMessage($message) {
    global $messagesFile;
    $messages = getMessages();
    $message['timestamp'] = time();
    $message['id'] = uniqid();
    $messages[] = $message;

    // Keep only last 100 messages to prevent file bloat
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }

    file_put_contents($messagesFile, json_encode($messages));
    return $message['id'];
}

function getSession($sessionId) {
    global $sessionsFile;
    $content = file_get_contents($sessionsFile);
    $sessions = json_decode($content, true) ?: [];
    return isset($sessions[$sessionId]) ? $sessions[$sessionId] : null;
}

function updateSession($sessionId, $data) {
    global $sessionsFile;
    $content = file_get_contents($sessionsFile);
    $sessions = json_decode($content, true) ?: [];

    if (!isset($sessions[$sessionId])) {
        $sessions[$sessionId] = [
            'created' => time(),
            'sharer' => null,
            'viewers' => []
        ];
    }

    $sessions[$sessionId] = array_merge($sessions[$sessionId], $data);
    $sessions[$sessionId]['updated'] = time();

    // Clean up old sessions (older than 1 hour)
    foreach ($sessions as $sid => $session) {
        if (time() - $session['updated'] > 3600) {
            unset($sessions[$sid]);
        }
    }

    file_put_contents($sessionsFile, json_encode($sessions));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        if (!$input || !isset($input['type']) || !isset($input['sessionId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $messageId = saveMessage($input);

        // Update session info based on message type
        switch ($input['type']) {
            case 'join-request':
                $session = getSession($input['sessionId']);
                if (!$session) {
                    updateSession($input['sessionId'], []);
                }
                break;
            case 'offer':
                updateSession($input['sessionId'], ['sharer' => $input['from'] ?? 'anonymous']);
                break;
        }

        echo json_encode(['success' => true, 'messageId' => $messageId]);
        break;

    case 'GET':
        $sessionId = $_GET['session'] ?? '';
        $since = intval($_GET['since'] ?? 0);

        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Session ID required']);
            exit;
        }

        $messages = getMessages();
        $sessionMessages = [];

        foreach ($messages as $message) {
            if ($message['sessionId'] === $sessionId && $message['timestamp'] > $since) {
                $sessionMessages[] = $message;
            }
        }

        echo json_encode([
            'messages' => $sessionMessages,
            'timestamp' => time()
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>