<?php
// api/stats.php
//
// Returns the number of tables in the database, plus a row count for each
// table and a grand total across all of them. Protected by a hardcoded
// token — anyone calling this endpoint must supply it.
//
// Usage:
//   /api/stats.php?token=YOUR_TOKEN_HERE
//   or send it as a header:  Authorization: Bearer YOUR_TOKEN_HERE
//
// SECURITY — before deploying:
// 1. Replace API_TOKEN below with a long random value of your own. A short
//    or guessable token defeats the point of this check.
// 2. Treat that value like a password: don't commit it to a public repo,
//    don't share the URL with the token in it over insecure channels.
// 3. Since this host doesn't support HTTPS, the token travels in plain
//    text over the network on every request. That's an inherent limitation
//    of running this over http:// — anyone able to observe the traffic
//    (e.g. on a shared/public network) could see the token. Treat this
//    endpoint as convenience tooling for you personally, not as something
//    safe to expose broadly, until HTTPS is available.

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// === Hardcoded API token — CHANGE THIS ===
const API_TOKEN = 'S@k@ry@';

function get_request_token(): string {
    if (!empty($_GET['token'])) {
        return (string)$_GET['token'];
    }
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($authHeader, 'Bearer ') === 0) {
        return trim(substr($authHeader, 7));
    }
    return '';
}

$providedToken = get_request_token();

// hash_equals() does a constant-time comparison so the check can't leak
// information about the correct token via response-timing differences.
if (!hash_equals(API_TOKEN, $providedToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. A valid token is required.']);
    exit;
}

try {
    $pdo = db();

    // Discover tables dynamically rather than hardcoding names, so this
    // endpoint stays correct automatically if tables are ever added/removed.
    $tableNames = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $counts = [];
    $totalRows = 0;

    foreach ($tableNames as $table) {
        // Table names can't be parameterized in SQL, so we validate against
        // the exact list MySQL itself just gave us — never against
        // user-supplied input — before interpolating.
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            continue;
        }
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $counts[$table] = $count;
        $totalRows += $count;
    }

    echo json_encode([
        'table_count' => count($tableNames),
        'tables' => $counts,
        'total_rows_all_tables' => $totalRows,
        'generated_at' => date('c'),
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error while fetching stats.']);
}
