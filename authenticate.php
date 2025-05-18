<?php
// authenticate.php

require 'config.php';
require 'csrf.php';

// prevent any output before headers
ob_start();

// 1. CSRF check
if (empty($_POST['csrf_token']) || ! verifyToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Login failed. Please try again.';
    header('Location: login.php');
    exit;
}

// 2. input validation
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Login failed. Please try again.';
    header('Location: login.php');
    exit;
}

// 3. fetch user record (now including username)
try {
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, role
           FROM users
          WHERE username = ?'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // in production, log $e->getMessage()
    $_SESSION['error'] = 'Login failed. Please try again.';
    header('Location: login.php');
    exit;
}

// 4. verify password
if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];

    header('Location: dashboard.php');
    exit;
}

// fallback for any other failure
$_SESSION['error'] = 'Invalid username or password.';
header('Location: login.php');
exit;
