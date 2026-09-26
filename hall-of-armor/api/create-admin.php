<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$configPath=__DIR__.'/config.php';
if (!is_file($configPath)) { fwrite(STDERR,"Crea api/config.php desde config.example.php primero.\n"); exit(1); }
$config=require $configPath;
$pdo=new PDO('mysql:host='.$config['host'].';dbname='.$config['database'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$email=strtolower(trim($argv[1]??'')); $password=$argv[2]??''; $name=trim($argv[3]??'Jefe de taller');
if (!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12) { fwrite(STDERR,"Uso: php create-admin.php correo contraseña-larga [nombre]\nLa contraseña debe tener al menos 12 caracteres.\n"); exit(1); }
$stmt=$pdo->prepare('INSERT INTO administrators(email,password_hash,display_name) VALUES(?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name)');
$stmt->execute([$email,password_hash($password,PASSWORD_DEFAULT),$name]);
fwrite(STDOUT,"Administrador local creado/actualizado para $email\n");
