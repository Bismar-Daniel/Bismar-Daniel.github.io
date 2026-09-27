<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>!empty($_SERVER['HTTPS'])]);
session_start();

function jarvis_respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jarvis_respond(405, ['error'=>'Este endpoint solo acepta solicitudes POST.']);
}
if (empty($_SESSION['admin_id'])) jarvis_respond(401, ['error'=>'Inicia sesión como administrador para usar J.A.R.V.I.S.']);

// Accept only same-origin browser requests when an Origin header is present.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $originPort = parse_url($origin, PHP_URL_PORT);
    $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $expected = strtolower((string)$originHost).($originPort ? ':'.$originPort : '');
    if (!$originHost || strtolower($requestHost) !== $expected) jarvis_respond(403, ['error'=>'Origen de solicitud no permitido.']);
}

$now = time();
$requests = array_values(array_filter($_SESSION['jarvis_request_times'] ?? [], static fn($stamp) => is_int($stamp) && $stamp > $now - 60));
if (count($requests) >= 12) jarvis_respond(429, ['error'=>'Se alcanzó el límite temporal de consultas. Espera un minuto e inténtalo de nuevo.']);
$requests[] = $now;
$_SESSION['jarvis_request_times'] = $requests;

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input) || !is_array($input['messages'] ?? null)) jarvis_respond(400, ['error'=>'Envía una conversación válida en formato JSON.']);
$messages = array_slice($input['messages'], -16);
$contents = [];
$totalChars = 0;
foreach ($messages as $message) {
    if (!is_array($message)) continue;
    $role = ($message['role'] ?? '') === 'assistant' ? 'model' : (($message['role'] ?? '') === 'user' ? 'user' : '');
    $text = trim((string)($message['text'] ?? ''));
    if ($role === '' || $text === '') continue;
    $totalChars += mb_strlen($text, 'UTF-8');
    if (mb_strlen($text, 'UTF-8') > 3000 || $totalChars > 16000) jarvis_respond(413, ['error'=>'La conversación excede el tamaño permitido.']);
    $contents[] = ['role'=>$role, 'parts'=>[['text'=>$text]]];
}
if (!$contents || end($contents)['role'] !== 'user') jarvis_respond(422, ['error'=>'Envía primero un mensaje para J.A.R.V.I.S.']);

$key = trim((string)(getenv('GEMINI_API_KEY') ?: ''));
$configPath = __DIR__.'/config.php';
if ($key === '' && is_file($configPath)) {
    $config = require $configPath;
    if (is_array($config)) $key = trim((string)($config['gemini_api_key'] ?? ''));
}
if ($key === '') jarvis_respond(503, ['error'=>'Falta configurar GEMINI_API_KEY en el servidor. Añádela como variable de entorno o en api/config.php.']);
if (!function_exists('curl_init')) jarvis_respond(503, ['error'=>'La extensión PHP cURL debe estar habilitada en XAMPP.']);

$systemInstruction = <<<'PROMPT'
Eres J.A.R.V.I.S., el asistente técnico del taller Hall of Armor, especializado en sistemas de frenos de vehículos convencionales, híbridos y eléctricos. Responde en español salvo que el usuario pida otro idioma. Mantén un tono sereno, conciso, preciso y cortés; presenta la información con claridad y sin inventar mediciones, datos del vehículo ni resultados de diagnóstico. Ayuda a organizar comprobaciones, interpretar síntomas, explicar componentes y preparar listas de inspección. Cuando falten datos, pregunta por marca, modelo, año, tren motriz, códigos de avería, síntomas y condiciones de aparición antes de inferir causas.

La seguridad es prioritaria: distingue hipótesis de conclusiones; recomienda consultar manuales y procedimientos del fabricante y usar herramientas adecuadas. En vehículos eléctricos e híbridos, advierte sobre alta tensión y no des instrucciones improvisadas para desenergizar, medir o intervenir el circuito de alta tensión; deriva esos trabajos a personal capacitado y al procedimiento OEM. Si hay pérdida de frenado, fuga, pedal que se hunde o advertencia crítica, indica detener el vehículo en un lugar seguro y no conducirlo hasta revisarlo. No afirmes haber controlado automatizaciones, leído sensores, creado órdenes ni accedido a datos del taller; solo tienes el texto de esta conversación. No solicites datos personales innecesarios.
PROMPT;

$model = trim((string)(getenv('GEMINI_MODEL') ?: 'gemini-3.6-flash'));
$payload = json_encode([
    'system_instruction'=>['parts'=>[['text'=>$systemInstruction]]],
    'contents'=>$contents,
    'generationConfig'=>['temperature'=>0.55, 'maxOutputTokens'=>1200],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent';
$curl = curl_init($endpoint);
curl_setopt_array($curl, [
    CURLOPT_POST=>true,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_CONNECTTIMEOUT=>12,
    CURLOPT_TIMEOUT=>50,
    CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'x-goog-api-key: '.$key],
    CURLOPT_POSTFIELDS=>$payload,
]);
$response = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_errno($curl);
curl_close($curl);
if ($response === false || $curlError !== 0) {
    error_log('Hall of Armor JARVIS upstream transport error '.$curlError);
    jarvis_respond(502, ['error'=>'No se pudo conectar con Gemini. Revisa la conexión del servidor e inténtalo otra vez.']);
}
$result = json_decode($response, true);
if ($status < 200 || $status >= 300) {
    error_log('Hall of Armor JARVIS upstream HTTP '.$status.'; response omitted');
    if ($status === 401 || $status === 403) jarvis_respond(502, ['error'=>'Gemini rechazó la credencial o sus permisos. Verifica GEMINI_API_KEY en la configuración del servidor.']);
    if ($status === 429) jarvis_respond(429, ['error'=>'Gemini alcanzó su cuota o límite de solicitudes. Intenta más tarde.']);
    jarvis_respond(502, ['error'=>'Gemini no pudo completar la consulta. Inténtalo de nuevo en unos momentos.']);
}
$parts = $result['candidates'][0]['content']['parts'] ?? [];
$reply = '';
foreach ($parts as $part) if (isset($part['text'])) $reply .= (string)$part['text'];
if (trim($reply) === '') jarvis_respond(502, ['error'=>'Gemini no generó una respuesta de texto. Reformula la consulta.']);
jarvis_respond(200, ['reply'=>trim($reply), 'model'=>$model]);
