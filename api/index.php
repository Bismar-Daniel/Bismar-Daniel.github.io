<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>$https]);
session_start();

function respond(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function body(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) respond(400, ['error'=>'Cuerpo JSON requerido']);
    return $data;
}
function require_role(array $roles): void {
    if (empty($_SESSION['user_id'])) respond(401, ['error'=>'Inicia sesión para continuar.']);
    global $pdo;
    $stmt = $pdo->prepare('SELECT role,status,display_name FROM users WHERE id=? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $account = $stmt->fetch();
    if (!$account || $account['status'] !== 'activo') {
        $_SESSION = [];
        session_destroy();
        respond(401, ['error'=>'La sesión expiró o la cuenta ya no está activa. Inicia sesión de nuevo.']);
    }
    $_SESSION['role'] = $account['role'];
    $_SESSION['user_name'] = $account['display_name'];
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) respond(403, ['error'=>'Tu cuenta no tiene permiso para esta acción.']);
}
try {
    $configPath = __DIR__ . '/config.php';
    if (is_file($configPath)) $c = require $configPath;
    else $c = ['host'=>getenv('DB_HOST') ?: getenv('MYSQLHOST'), 'port'=>getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306', 'database'=>getenv('DB_NAME') ?: getenv('MYSQLDATABASE'), 'username'=>getenv('DB_USER') ?: getenv('MYSQLUSER'), 'password'=>getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD')];
    if (empty($c['host']) || empty($c['database']) || empty($c['username'])) respond(503, ['error'=>'Configura MySQL en api/config.php o en las variables de entorno DB_HOST, DB_NAME, DB_USER y DB_PASSWORD.']);
    $pdo = new PDO('mysql:host='.$c['host'].';port='.($c['port'] ?? '3306').';dbname='.$c['database'].';charset=utf8mb4', $c['username'], $c['password'] ?? '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    if ($method === 'POST' && $action === 'register') {
        $input = body();
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $name = trim((string)($input['name'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $role = (string)($input['role'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) respond(422, ['error'=>'Escribe un correo válido.']);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) respond(422, ['error'=>'El nombre debe tener entre 2 y 120 caracteres.']);
        if (mb_strlen($password) < 12 || mb_strlen($password) > 200) respond(422, ['error'=>'La contraseña debe tener entre 12 y 200 caracteres.']);
        if (!in_array($role, ['usuario','mecanico','soporte'], true)) respond(403, ['error'=>'El alta de administradores solo puede realizarla el propietario desde XAMPP.']);
        $status = $role === 'usuario' ? 'activo' : 'pendiente';
        try {
            $stmt = $pdo->prepare('INSERT INTO users(email,password_hash,display_name,role,status) VALUES (?,?,?,?,?)');
            $stmt->execute([$email,password_hash($password,PASSWORD_DEFAULT),$name,$role,$status]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') respond(409, ['error'=>'Ya existe una cuenta con ese correo.']);
            throw $e;
        }
        if ($status === 'activo') {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['role'] = $role;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }
        respond(201, ['id'=>(int)$pdo->lastInsertId(),'name'=>$name,'role'=>$role,'status'=>$status]);
    }
    if ($method === 'POST' && $action === 'login') {
        $input = body();
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $role = (string)($input['role'] ?? '');
        if (!in_array($role, ['usuario','mecanico','admin','soporte'], true)) respond(422, ['error'=>'Selecciona el tipo de cuenta.']);
        $stmt = $pdo->prepare('SELECT id,email,password_hash,display_name,role,status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify((string)($input['password'] ?? ''), $user['password_hash']) || $user['role'] !== $role) respond(401, ['error'=>'Correo, contraseña o tipo de cuenta incorrectos.']);
        if ($user['status'] === 'pendiente') respond(403, ['error'=>'Tu solicitud está pendiente de aprobación por el administrador.']);
        if ($user['status'] !== 'activo') respond(403, ['error'=>'Esta cuenta está desactivada. Contacta al administrador.']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['display_name'];
        $_SESSION['user_email'] = $user['email'];
        if ($user['role'] === 'admin') $_SESSION['admin_id'] = (int)$user['id'];
        else unset($_SESSION['admin_id']);
        respond(200, ['id'=>(int)$user['id'],'name'=>$user['display_name'],'role'=>$user['role']]);
    }
    if ($method === 'POST' && $action === 'logout') { $_SESSION = []; session_destroy(); respond(200, ['ok'=>true]); }
    if ($method === 'GET' && $action === 'me') {
        require_role(['usuario','mecanico','admin','soporte']);
        respond(200,['id'=>(int)$_SESSION['user_id'],'name'=>$_SESSION['user_name'] ?? '','role'=>$_SESSION['role']]);
    }
    if ($method === 'GET' && $action === 'state') {
        require_role(['usuario','mecanico','admin','soporte']);
        $role = (string)$_SESSION['role'];
        $allowed = $role === 'admin'
            ? ['vehicles','inventory','notices','tickets','messages','autoLog','autoSettings','workshop','sheetConfig','jarvisHistory','dailyFlags','orderDetails']
            : ($role === 'mecanico'
                ? ['vehicles','inventory','notices','tickets','messages','autoLog','autoSettings','jarvisHistory','dailyFlags','orderDetails']
                : ['notices','tickets','messages']);
        $rows = $pdo->query('SELECT state_key,state_json FROM app_state')->fetchAll();
        $state = [];
        foreach ($rows as $row) if (in_array($row['state_key'], $allowed, true)) {
            $value = json_decode($row['state_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) $state[$row['state_key']] = $value;
        }
        respond(200, ['state'=>$state]);
    }
    if ($method === 'PUT' && $action === 'state') {
        require_role(['usuario','mecanico','admin','soporte']);
        $role = (string)$_SESSION['role'];
        $allowed = $role === 'admin'
            ? ['vehicles','inventory','notices','tickets','messages','autoLog','autoSettings','workshop','sheetConfig','jarvisHistory','dailyFlags','orderDetails']
            : ($role === 'mecanico'
                ? ['vehicles','inventory','notices','tickets','messages','autoLog','autoSettings','jarvisHistory','dailyFlags','orderDetails']
                : ['notices','tickets','messages']);
        $state = body()['state'] ?? null;
        if (!is_array($state) || count($state) > count($allowed)) respond(422,['error'=>'Estado inválido.']);
        $stmt=$pdo->prepare('INSERT INTO app_state(state_key,state_json,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),updated_by=VALUES(updated_by)');
        foreach ($state as $key=>$value) {
            if (!is_string($key) || !in_array($key,$allowed,true)) respond(403,['error'=>'No tienes permiso para guardar esta sección.']);
            $json=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if ($json===false || strlen($json)>1000000) respond(413,['error'=>'El contenido de la sección supera el límite permitido.']);
            $stmt->execute([$key,$json,(int)$_SESSION['user_id']]);
        }
        respond(200,['ok'=>true]);
    }
    if ($method === 'GET' && $action === 'users') {
        require_role(['admin']);
        $rows=$pdo->query('SELECT id,email,display_name AS name,role,status,created_at FROM users ORDER BY FIELD(status,"pendiente","activo","desactivado"),created_at DESC')->fetchAll();
        respond(200,['users'=>$rows]);
    }
    if ($method === 'PATCH' && $action === 'users') {
        require_role(['admin']);
        $i=body(); $id=(int)($i['id']??0); $role=(string)($i['role']??''); $status=(string)($i['status']??'');
        if ($id<1 || !in_array($role,['usuario','mecanico','admin','soporte'],true) || !in_array($status,['activo','pendiente','desactivado'],true)) respond(422,['error'=>'Datos de cuenta inválidos.']);
        if ($id===(int)$_SESSION['user_id'] && ($role!=='admin' || $status!=='activo')) respond(409,['error'=>'No puedes quitarte tus propios permisos de administrador.']);
        $stmt=$pdo->prepare('SELECT email,display_name,password_hash,role,status FROM users WHERE id=?'); $stmt->execute([$id]); $managed=$stmt->fetch();
        if (!$managed) respond(404,['error'=>'No se encontró la cuenta.']);
        if (($managed['role']==='admin' && $managed['status']==='activo') && ($role!=='admin' || $status!=='activo') && (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='activo'")->fetchColumn() <= 1) respond(409,['error'=>'No puedes desactivar la última cuenta de administrador.']);
        $pdo->beginTransaction();
        $stmt=$pdo->prepare('UPDATE users SET role=?,status=? WHERE id=?'); $stmt->execute([$role,$status,$id]);
        if ($role==='admin') {
            $stmt=$pdo->prepare('INSERT INTO administrators(email,password_hash,display_name) VALUES (?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name)');
            $stmt->execute([$managed['email'],$managed['password_hash'],$managed['display_name']]);
        } else {
            $stmt=$pdo->prepare('DELETE FROM administrators WHERE email=?'); $stmt->execute([$managed['email']]);
        }
        $pdo->commit();
        respond(200,['ok'=>true]);
    }
    if ($method === 'GET' && $action === 'orders') {
        require_role(['admin','mecanico']);
        $rows = $pdo->query("SELECT w.order_code AS id,c.name AS client,v.make_model AS vehicle,v.year,v.powertrain AS type,w.service_description AS service,w.status,w.quoted_amount AS price,w.created_at AS time FROM work_orders w JOIN customers c ON c.id=w.customer_id JOIN vehicles v ON v.id=w.vehicle_id ORDER BY w.created_at DESC LIMIT 250")->fetchAll();
        respond(200, ['orders'=>$rows]);
    }
    if ($method === 'POST' && $action === 'orders') {
        require_role(['admin','mecanico']);
        $i = body();
        foreach (['client','vehicle','service','type'] as $key) if (trim((string)($i[$key] ?? '')) === '') respond(422, ['error'=>"Campo requerido: $key"]);
        if (!in_array($i['type'], ['Convencional','Híbrido','Eléctrico'], true)) respond(422, ['error'=>'Tren motriz inválido']);
        $pdo->beginTransaction();
        $stmt=$pdo->prepare('INSERT INTO customers(name) VALUES (?)'); $stmt->execute([mb_substr(trim($i['client']),0,160)]); $customer=(int)$pdo->lastInsertId();
        $stmt=$pdo->prepare('INSERT INTO vehicles(customer_id,make_model,year,powertrain) VALUES (?,?,?,?)'); $stmt->execute([$customer,mb_substr(trim($i['vehicle']),0,160),isset($i['year'])?(int)$i['year']:null,$i['type']]); $vehicle=(int)$pdo->lastInsertId();
        $code='HA-'.date('ymd').'-'.str_pad((string)random_int(1,9999),4,'0',STR_PAD_LEFT);
        $stmt=$pdo->prepare('INSERT INTO work_orders(order_code,customer_id,vehicle_id,service_description) VALUES (?,?,?,?)'); $stmt->execute([$code,$customer,$vehicle,mb_substr(trim($i['service']),0,500)]);
        $pdo->commit(); respond(201,['id'=>$code]);
    }
    if ($method === 'PATCH' && $action === 'orders') {
        require_role(['admin','mecanico']);
        $i=body(); $valid=['En diagnóstico','En reparación','Esperando repuesto','Listo para entrega','Cerrada'];
        if (!in_array($i['status']??'', $valid, true)) respond(422,['error'=>'Estado inválido']);
        $stmt=$pdo->prepare('UPDATE work_orders SET status=? WHERE order_code=?'); $stmt->execute([$i['status'],$i['id']??'']);
        respond($stmt->rowCount()?200:404,['ok'=>(bool)$stmt->rowCount()]);
    }
    if ($method === 'GET' && $action === 'automations') { require_role(['admin','mecanico']); respond(200,['automations'=>$pdo->query('SELECT automation_key AS `key`,name,enabled FROM automations ORDER BY id')->fetchAll()]); }
    if ($method === 'PATCH' && $action === 'automations') {
        require_role(['admin']);
        $i=body(); if (empty($i['key']) || !isset($i['enabled'])) respond(422,['error'=>'key y enabled son requeridos']);
        $stmt=$pdo->prepare('UPDATE automations SET enabled=? WHERE automation_key=?'); $stmt->execute([(int)(bool)$i['enabled'],(string)$i['key']]);
        respond($stmt->rowCount()?200:404,['ok'=>(bool)$stmt->rowCount()]);
    }
    if ($method === 'GET' && $action === 'cash-flow') {
        require_role(['admin']);
        $rows=$pdo->query('SELECT id,movement_date AS date,reason,income,expense FROM cash_movements ORDER BY movement_date,id')->fetchAll();
        $balance=0.0;
        foreach ($rows as &$row) { $row['income']=(float)$row['income']; $row['expense']=(float)$row['expense']; $balance += $row['income']-$row['expense']; $row['balance']=$balance; }
        unset($row); respond(200,['movements'=>$rows]);
    }
    if ($method === 'POST' && $action === 'cash-flow') {
        require_role(['admin']);
        $i=body(); $date=(string)($i['date']??''); $reason=trim((string)($i['reason']??''));
        $income=filter_var($i['income']??0,FILTER_VALIDATE_FLOAT); $expense=filter_var($i['expense']??0,FILTER_VALIDATE_FLOAT);
        $parts=explode('-',$date);
        if (count($parts)!==3 || !checkdate((int)$parts[1],(int)$parts[2],(int)$parts[0])) respond(422,['error'=>'Fecha inválida']);
        if ($reason==='' || mb_strlen($reason)>500) respond(422,['error'=>'Motivo requerido (máximo 500 caracteres)']);
        if ($income===false || $expense===false || $income<0 || $expense<0 || (($income>0)===($expense>0))) respond(422,['error'=>'Ingresa un importe positivo en ingresos o egresos']);
        $stmt=$pdo->prepare('INSERT INTO cash_movements(movement_date,reason,income,expense) VALUES (?,?,?,?)');
        $stmt->execute([$date,mb_substr($reason,0,500),$income,$expense]); respond(201,['id'=>(int)$pdo->lastInsertId()]);
    }
    respond(404,['error'=>'Ruta no encontrada']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Hall of Armor API error: '.$e->getMessage());
    respond(500,['error'=>'Error interno del servidor']);
}
