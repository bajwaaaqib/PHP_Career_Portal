<?php
// Include functions
require_once __DIR__ . '/functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is employee
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

// Redirect if not employee
function requireEmployee() {
    requireLogin();
    if (!isEmployee()) {
        header("Location: ../login.php");
        exit();
    }
}
?>