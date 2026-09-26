<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>!empty($_SERVER['HTTPS'])]);
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
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) respond(401, ['error'=>'Inicia sesión como administrador']);
}
try {
    $configPath = __DIR__ . '/config.php';
    if (!is_file($configPath)) respond(503, ['error'=>'Copia api/config.example.php a api/config.php y configura MySQL']);
    $c = require $configPath;
    $pdo = new PDO('mysql:host='.$c['host'].';dbname='.$c['database'].';charset=utf8mb4', $c['username'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    if ($method === 'POST' && $action === 'login') {
        $input = body();
        $stmt = $pdo->prepare('SELECT id,email,password_hash,display_name FROM administrators WHERE email = ?');
        $stmt->execute([strtolower(trim((string)($input['email'] ?? '')))]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify((string)($input['password'] ?? ''), $admin['password_hash'])) respond(401, ['error'=>'Credenciales incorrectas']);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        respond(200, ['name'=>$admin['display_name']]);
    }
    if ($method === 'POST' && $action === 'logout') { $_SESSION = []; session_destroy(); respond(200, ['ok'=>true]); }
    require_admin();
    if ($method === 'GET' && $action === 'orders') {
        $rows = $pdo->query("SELECT w.order_code AS id,c.name AS client,v.make_model AS vehicle,v.year,v.powertrain AS type,w.service_description AS service,w.status,w.quoted_amount AS price,w.created_at AS time FROM work_orders w JOIN customers c ON c.id=w.customer_id JOIN vehicles v ON v.id=w.vehicle_id ORDER BY w.created_at DESC LIMIT 250")->fetchAll();
        respond(200, ['orders'=>$rows]);
    }
    if ($method === 'POST' && $action === 'orders') {
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
        $i=body(); $valid=['En diagnóstico','En reparación','Esperando repuesto','Listo para entrega','Cerrada'];
        if (!in_array($i['status']??'', $valid, true)) respond(422,['error'=>'Estado inválido']);
        $stmt=$pdo->prepare('UPDATE work_orders SET status=? WHERE order_code=?'); $stmt->execute([$i['status'],$i['id']??'']);
        respond($stmt->rowCount()?200:404,['ok'=>(bool)$stmt->rowCount()]);
    }
    if ($method === 'GET' && $action === 'automations') respond(200,['automations'=>$pdo->query('SELECT automation_key AS `key`,name,enabled FROM automations ORDER BY id')->fetchAll()]);
    if ($method === 'PATCH' && $action === 'automations') {
        $i=body(); if (empty($i['key']) || !isset($i['enabled'])) respond(422,['error'=>'key y enabled son requeridos']);
        $stmt=$pdo->prepare('UPDATE automations SET enabled=? WHERE automation_key=?'); $stmt->execute([(int)(bool)$i['enabled'],(string)$i['key']]);
        respond($stmt->rowCount()?200:404,['ok'=>(bool)$stmt->rowCount()]);
    }
    if ($method === 'GET' && $action === 'cash-flow') {
        $rows=$pdo->query('SELECT id,movement_date AS date,reason,income,expense FROM cash_movements ORDER BY movement_date,id')->fetchAll();
        $balance=0.0;
        foreach ($rows as &$row) { $row['income']=(float)$row['income']; $row['expense']=(float)$row['expense']; $balance += $row['income']-$row['expense']; $row['balance']=$balance; }
        unset($row); respond(200,['movements'=>$rows]);
    }
    if ($method === 'POST' && $action === 'cash-flow') {
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
