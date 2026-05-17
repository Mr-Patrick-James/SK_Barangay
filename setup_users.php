<?php
require 'config/database.php';
$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");

$stmt->execute(['sk_official']);
if ($stmt->fetchColumn() == 0) {
    $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)")
       ->execute(['sk_official', password_hash('sk123', PASSWORD_DEFAULT), 'SK Chairman', 'SK Official']);
    echo "Added sk_official\n";
}

$stmt->execute(['kk_member']);
if ($stmt->fetchColumn() == 0) {
    $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)")
       ->execute(['kk_member', password_hash('kk123', PASSWORD_DEFAULT), 'Juan Member', 'Katipunan Member']);
    echo "Added kk_member\n";
}
echo "Done.";
