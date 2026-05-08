<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit;
    }
}

function getLoginUrl(): string {
    // Determine the correct path to login.php based on current location
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(dirname($scriptPath), '/') - substr_count($_SERVER['DOCUMENT_ROOT'], '/');
    return str_repeat('../', max(0, $depth - 1)) . 'login.php';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once __DIR__ . '/database.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function login(string $username, string $password): bool {
    require_once __DIR__ . '/database.php';
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

function logout(): void {
    session_destroy();
    session_start();
}
