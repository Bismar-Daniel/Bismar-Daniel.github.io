<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $configPath = __DIR__ . '/config.php';
    if (is_file($configPath)) $c = require $configPath;
    else $c = ['host'=>getenv('DB_HOST') ?: getenv('MYSQLHOST'), 'port'=>getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306', 'database'=>getenv('DB_NAME') ?: getenv('MYSQLDATABASE'), 'username'=>getenv('DB_USER') ?: getenv('MYSQLUSER'), 'password'=>getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD')];
    $pdo = new PDO('mysql:host='.$c['host'].';port='.($c['port'] ?? '3306').';dbname='.$c['database'].';charset=utf8mb4', $c['username'], $c['password'] ?? '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->query('SELECT 1');
    echo json_encode(['ok'=>true,'service'=>'hall-of-armor']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'error'=>'database_unavailable']);
}
