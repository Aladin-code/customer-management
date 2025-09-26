<?php
/**
 * WebRTC Signaling Server - Memory-based (simplified)
 * Handles message passing between screen share peers across networks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use session storage instead of files
session_start();

function getMessages() {
    return isset($_SESSION['signaling_messages']) ? $_SESSION['signaling_messages'] : [];
}

function saveMessage($message) {
    if (!isset($_SESSION['signaling_messages'])) {
        $_SESSION['signaling_messages'] = [];
    }

    $message['timestamp'] = time();
    $message['id'] = uniqid();
    $_SESSION['signaling_messages'][] = $message;

    // Keep only last 50 messages to prevent memory bloat
    if (count($_SESSION['signaling_messages']) > 50) {
        $_SESSION['signaling_messages'] = array_slice($_SESSION['signaling_messages'], -50);
    }

    return $message['id'];
}

function getSession($sessionId) {
    $sessions = isset($_SESSION['sessions']) ? $_SESSION['sessions'] : [];
    return isset($sessions[$sessionId]) ? $sessions[$sessionId] : null;
}

function updateSession($sessionId, $data) {
    if (!isset($_SESSION['sessions'])) {
        $_SESSION['sessions'] = [];
    }

    if (!isset($_SESSION['sessions'][$sessionId])) {
        $_SESSION['sessions'][$sessionId] = [
            'created' => time(),
            'sharer' => null,
            'viewers' => []
        ];
    }

    $_SESSION['sessions'][$sessionId] = array_merge($_SESSION['sessions'][$sessionId], $data);
    $_SESSION['sessions'][$sessionId]['updated'] = time();

    // Clean up old sessions (older than 30 minutes for memory efficiency)
    foreach ($_SESSION['sessions'] as $sid => $session) {
        if (time() - $session['updated'] > 1800) {
            unset($_SESSION['sessions'][$sid]);
        }
    }
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