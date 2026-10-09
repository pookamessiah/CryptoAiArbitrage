<?php
session_start();
header('Content-Type: application/json');

require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'auth_required' => true, // Flag indicating the user needs to log in
        'message' => 'Authentication required.'
    ]);
    exit;
}

$db = new Database();
$pdo = $db->connect();

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['seats'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No seats selected.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO orders 
        (user_id, ticket_id, status, created_at) 
        VALUES (?, ?, 'cancelled', NOW())
    ");

    $orderIds = [];

    foreach ($data['seats'] as $seat) {
        $ticket_id = (int)$seat['ticket_id'];
        $insert->execute([$user_id, $ticket_id]);
        $orderIds[] = $pdo->lastInsertId();
    }

    $pdo->commit();

    // Store order IDs in session
    $_SESSION['checkout_order_ids'] = $orderIds;

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
