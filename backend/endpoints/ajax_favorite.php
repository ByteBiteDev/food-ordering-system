<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to continue.',
        'redirect' => url('login.php'),
    ]);
    exit;
}

// CSRF (manual for JSON-friendly errors)
$csrf = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($csrf === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Refresh the page and try again.']);
    exit;
}

$foodId = int_from_request($_POST, 'food_id', 0);
if ($foodId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid food id']);
    exit;
}

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to continue.',
        'redirect' => url('login.php'),
    ]);
    exit;
}

$pdo = db();
$userId = (int)$user['user_id'];

try {
    $stmtFood = $pdo->prepare('SELECT food_id FROM foods WHERE food_id = ? AND status = 1');
    $stmtFood->execute([$foodId]);
    if (!$stmtFood->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Food not found']);
        exit;
    }

    $stmtCheck = $pdo->prepare('SELECT favorite_id FROM favorites WHERE user_id = ? AND food_id = ?');
    $stmtCheck->execute([$userId, $foodId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        $stmtDelete = $pdo->prepare('DELETE FROM favorites WHERE favorite_id = ? AND user_id = ?');
        $stmtDelete->execute([(int)$existing['favorite_id'], $userId]);
        echo json_encode(['success' => true, 'favorited' => false]);
        exit;
    }

    $stmtInsert = $pdo->prepare('INSERT INTO favorites (user_id, food_id, created_at) VALUES (?, ?, NOW())');
    $stmtInsert->execute([$userId, $foodId]);
    echo json_encode(['success' => true, 'favorited' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update favorites.']);
}
