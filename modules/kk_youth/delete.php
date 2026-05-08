<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM kk_youth WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if ($record) {
        $db->prepare("DELETE FROM kk_youth WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Profile of ' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . ' has been deleted.',
        ];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Youth profile not found.'];
    }
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid request.'];
}

header('Location: index.php');
exit;
