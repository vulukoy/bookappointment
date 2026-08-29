<?php
// includes/auth.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_provider(): ?array {
    if (empty($_SESSION['provider_id'])) return null;
    $stmt = db()->prepare('SELECT * FROM providers WHERE id = ?');
    $stmt->execute([$_SESSION['provider_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function require_login(): array {
    $p = current_provider();
    if (!$p) {
        header('Location: /dashboard/login.php');
        exit;
    }
    return $p;
}

function login(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM providers WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['provider_id'] = $row['id'];
        return true;
    }
    return false;
}

function signup(string $username, string $email, string $password): array {
    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z0-9\-]{3,30}$/', $username)) {
        return [false, 'Username must be 3-30 characters: lowercase letters, numbers, hyphens only.'];
    }
    if (strlen($password) < 6) {
        return [false, 'Password must be at least 6 characters.'];
    }
    $stmt = db()->prepare('SELECT id FROM providers WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return [false, 'That username or email is already taken.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO providers (username, email, password_hash, business_name) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $username]);
    $id = db()->lastInsertId();

    // seed sensible default availability: Mon-Fri 9-5
    $availStmt = db()->prepare('INSERT INTO availability (provider_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
    foreach ([1,2,3,4,5] as $day) {
        $availStmt->execute([$id, $day, '09:00', '17:00']);
    }

    // seed one example service
    $svcStmt = db()->prepare('INSERT INTO services (provider_id, name, duration_minutes, price) VALUES (?, ?, ?, ?)');
    $svcStmt->execute([$id, 'Consultation', 30, 0]);

    $_SESSION['provider_id'] = $id;
    return [true, null];
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}
