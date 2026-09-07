<?php
// includes/db.php — MySQL version

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        //$host = 'fdb1031.runhosting.com';           // from your host's DB panel
		$host = 'pdb1054.runhosting.com';
        $dbname = '4461901_appt'; // from your host's DB panel
        $user = '4461901_appt';           // from your host's DB panel
        $pass = 'tib@@pcwmd1';
		
        // ===================================================================

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        ensure_provider_booking_slugs($pdo);
        //init_schema($pdo);
    }
    return $pdo;
}

function booking_slugify(string $value, string $fallback = 'provider'): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value, '-');
    return substr($value ?: $fallback, 0, 90);
}

function make_unique_booking_slug(PDO $pdo, string $source, string $fallback, ?int $excludeId = null): string {
    $base = booking_slugify($source, $fallback);
    $candidate = $base;
    $suffix = 2;

    while (true) {
        $sql = 'SELECT 1 FROM providers WHERE (booking_slug = ? OR username = ?)';
        $params = [$candidate, $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return $candidate;

        $candidate = substr($base, 0, 90 - strlen((string)$suffix) - 1) . '-' . $suffix;
        $suffix++;
    }
}

function ensure_provider_booking_slugs(PDO $pdo): void {
    $column = $pdo->query("SHOW COLUMNS FROM providers LIKE 'booking_slug'")->fetch();
    if (!$column) {
        $pdo->exec('ALTER TABLE providers ADD COLUMN booking_slug VARCHAR(100) NULL AFTER username');
    }

    $providers = $pdo->query("SELECT id, username, business_name FROM providers WHERE booking_slug IS NULL OR booking_slug = ''")->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare('UPDATE providers SET booking_slug = ? WHERE id = ?');
    foreach ($providers as $provider) {
        $slug = make_unique_booking_slug($pdo, $provider['business_name'], $provider['username'], (int)$provider['id']);
        $update->execute([$slug, $provider['id']]);
    }

    $pdo->exec('ALTER TABLE providers MODIFY booking_slug VARCHAR(100) NOT NULL');
    $indexes = $pdo->query("SHOW INDEX FROM providers WHERE Key_name = 'uniq_providers_booking_slug'")->fetchAll(PDO::FETCH_ASSOC);
    if (!$indexes) {
        $pdo->exec('ALTER TABLE providers ADD UNIQUE INDEX uniq_providers_booking_slug (booking_slug)');
    }
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS providers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        booking_slug VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        business_name VARCHAR(255) DEFAULT '',
        bio TEXT,
        photo_url VARCHAR(255) DEFAULT '',
        timezone VARCHAR(50) DEFAULT 'America/Los_Angeles',
        plan VARCHAR(20) DEFAULT 'free',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        provider_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        duration_minutes INT NOT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        buffer_minutes INT DEFAULT 0,
        active TINYINT DEFAULT 1,
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS availability (
        id INT PRIMARY KEY AUTO_INCREMENT,
        provider_id INT NOT NULL,
        day_of_week TINYINT NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS blocked_dates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        provider_id INT NOT NULL,
        blocked_date DATE NOT NULL,
        reason VARCHAR(255) DEFAULT '',
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS bookings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        provider_id INT NOT NULL,
        service_id INT NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        client_email VARCHAR(255) NOT NULL,
        client_phone VARCHAR(50) DEFAULT '',
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'confirmed',
        cancel_token VARCHAR(64) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");
}
