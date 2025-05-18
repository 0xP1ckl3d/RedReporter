<?php
// Session and role-based access control helpers

// Ensure session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Retrieve current user info from session.
 *
 * @return array{id:int|null, username:string|null, role:string}
 */
function current_user(): array {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'username' => $_SESSION['username']  ?? null,
        'role'     => $_SESSION['role']      ?? 'client',
    ];
}

/**
 * Require that the current user has one of the given roles.
 * Redirects to login if not.
 *
 * @param string[] $roles
 */
function require_role(array $roles): void {
    $user = current_user();
    if (empty($user['id']) || ! in_array($user['role'], $roles, true)) {
        header('Location: login.php');
        exit;
    }
}
