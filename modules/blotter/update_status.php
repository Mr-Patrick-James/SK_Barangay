<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getDB();
$id          = (int)($_POST['id'] ?? 0);
$status      = trim($_POST['status'] ?? '');
$actionTaken = trim($_POST['action_taken'] ?? '');

$allowed = ['Pending', 'Ongoing', 'Resolved', 'Dismissed'];

if ($id > 0 && in_array($status, $allowed)) {
    $stmt = $db->prepare("
        UPDATE blotter SET status=?, action_taken=?, updated_at=datetime('now','localtime')
        WHERE id=?
    ");
    $stmt->execute([$status, $actionTaken, $id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Blotter status updated to ' . $status . '.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid request.'];
}

header("Location: view.php?id=$id");
exit;
