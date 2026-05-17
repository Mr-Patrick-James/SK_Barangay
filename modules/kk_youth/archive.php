<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $db->prepare("UPDATE kk_youth SET is_archived = 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile archived successfully.'];
}

header('Location: index.php');
exit;
