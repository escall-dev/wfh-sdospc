<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

function requireSuperAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'superadmin') {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Super Admin only.']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

function isSuperAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'superadmin';
}

function isHrTimekeeping(): bool {
    return ($_SESSION['role'] ?? '') === 'hr_timekeeping';
}

function isAdminRole(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'superadmin']);
}

function isAdminOrHr(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'hr_timekeeping']);
}

function requireAdminOrHr(): void {
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin', 'superadmin', 'hr_timekeeping'])) {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

function requireEmployee(): void {
    requireLogin();
    // Superadmin is the only role that does not file personal attendance records
    if (($_SESSION['role'] ?? '') === 'superadmin') {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
    // Enforce password change redirect for regular employees only
    if (($_SESSION['role'] ?? '') === 'employee' && !empty($_SESSION['must_change_password'])) {
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        if ($currentScript !== 'change_password.php') {
            header('Location: /employee/change_password.php');
            exit;
        }
    }
}

function isApiRequest(): bool {
    return strpos($_SERVER['REQUEST_URI'], '/api/') !== false
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function getCurrentUserName(): ?string {
    return $_SESSION['full_name'] ?? null;
}

function getCurrentUserPicture(): ?string {
    return $_SESSION['id_picture'] ?? null;
}

function getCurrentUserDivision(): ?string {
    return $_SESSION['functional_division'] ?? null;
}

function getCurrentUserPosition(): ?string {
    return $_SESSION['position'] ?? null;
}

function mustChangePassword(): bool {
    return !empty($_SESSION['must_change_password']);
}
