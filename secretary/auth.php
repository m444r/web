<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['userid']) && isset($_SESSION['role']);
}

function login($userId, $username, $role) {
    session_regenerate_id(true);
    $_SESSION['userid'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['last_login'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function validateSession() {
    if (!isset($_SESSION['ip_address']) || $_SESSION['ip_address'] != $_SERVER['REMOTE_ADDR']) {
        return false;
    }
    if (!isset($_SESSION['user_agent']) || $_SESSION['user_agent'] != $_SERVER['HTTP_USER_AGENT']) {
        return false;
    }
    return true;
}

function requireRole($requiredRole) {
    if (!isLoggedIn() || $_SESSION['role'] !== $requiredRole || !validateSession()) {
        logout();
        header('Location: login.php');
        exit;
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
