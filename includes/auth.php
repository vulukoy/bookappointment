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

function booking_path(array $provider): string {
    return '/book/' . rawurlencode($provider['booking_slug'] ?: $provider['username']);
}

function booking_slug_available(string $slug, ?int $excludeId = null): bool {
    $sql = 'SELECT 1 FROM providers WHERE (booking_slug = ? OR username = ?)';
    $params = [$slug, $slug];
    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return !$stmt->fetch();
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

function signup(string $username, string $businessName, string $email, string $password): array {
    $username = strtolower(trim($username));
    $businessName = trim($businessName);
    if (!preg_match('/^[a-z0-9\-]{3,30}$/', $username)) {
        return [false, 'Username must be 3-30 characters: lowercase letters, numbers, hyphens only.'];
    }
    if (strlen($password) < 6) {
        return [false, 'Password must be at least 6 characters.'];
    }
    if ($businessName === '') {
        return [false, 'Business name is required.'];
    }
    $stmt = db()->prepare('SELECT id FROM providers WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return [false, 'That username or email is already taken.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo = db();
    $bookingSlug = make_unique_booking_slug($pdo, $businessName, $username);
    $stmt = $pdo->prepare('INSERT INTO providers (username, booking_slug, email, password_hash, business_name) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$username, $bookingSlug, $email, $hash, $businessName]);
    $id = $pdo->lastInsertId();

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
