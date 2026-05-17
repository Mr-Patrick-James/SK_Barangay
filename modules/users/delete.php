<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole('Admin');
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$currentUser = getCurrentUser();

if ($id > 0 && $id !== $currentUser['id']) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted successfully.'];
} elseif ($id === $currentUser['id']) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'You cannot delete your own account.'];
}

header('Location: index.php');
exit;
