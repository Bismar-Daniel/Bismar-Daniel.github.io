<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) $config = require $configPath;
else $config = ['host'=>getenv('DB_HOST') ?: getenv('MYSQLHOST'), 'port'=>getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306', 'database'=>getenv('DB_NAME') ?: getenv('MYSQLDATABASE'), 'username'=>getenv('DB_USER') ?: getenv('MYSQLUSER'), 'password'=>getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD')];
if (empty($config['host']) || empty($config['database']) || empty($config['username'])) {
    fwrite(STDERR, "Configura api/config.php o las variables de entorno para MySQL.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? '3306') . ';dbname=' . $config['database'] . ';charset=utf8mb4',
    $config['username'],
    $config['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$email = strtolower(trim($argv[1] ?? ''));
$password = $argv[2] ?? '';
$name = trim($argv[3] ?? 'Jefe de taller');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php create-admin.php correo contraseña-larga [nombre]\n");
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$pdo->beginTransaction();
$stmt = $pdo->prepare(
    'INSERT INTO administrators(email, password_hash, display_name)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        password_hash = VALUES(password_hash),
        display_name = VALUES(display_name)'
);

$stmt->execute([
    $email,
    $passwordHash,
    $name,
]);

$stmt = $pdo->prepare(
    'INSERT INTO users(email,password_hash,display_name,role,status)
    VALUES (?,?,?,\'admin\',\'activo\')
    ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name),role=\'admin\',status=\'activo\''
);
$stmt->execute([$email,$passwordHash,$name]);
$pdo->commit();

fwrite(STDOUT, "Administrador local creado/actualizado para $email\n");
