<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $resident = $db->prepare("SELECT first_name, last_name FROM residents WHERE id = ?");
    $resident->execute([$id]);
    $r = $resident->fetch();

    if ($r) {
        $db->prepare("DELETE FROM residents WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => 'Resident ' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . ' has been deleted.'
        ];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Resident not found.'];
    }
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid request.'];
}

header('Location: index.php');
exit;
