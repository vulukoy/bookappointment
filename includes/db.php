<?php
// includes/db.php — MySQL version

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'fdb1031.runhosting.com';           // from your host's DB panel
        $dbname = '4461901_appt'; // from your host's DB panel
        $user = '4461901_appt';           // from your host's DB panel
        $pass = 'tib@@pcwmd1';
		
        // ===================================================================

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //init_schema($pdo);
    }
    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS providers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
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
