<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    boot();
    $content = require dirname(__DIR__) . '/app/content.php';
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $id = null;
        if (isset($_GET['mission'])) {
            $value = $_GET['mission'];
            if (!is_string($value) || !preg_match('/^[1-5]$/D', $value)) {
                fail('Selecciona una misión válida.', 400);
            }
            $id = (int) $value;
            accessible($id);
        }
        reply(snapshot($content, $id));
    }
    if ($method !== 'POST') {
        header('Allow: GET, POST');
        fail('Este canal solo admite lectura y envío de evidencias.', 405);
    }
    $input = file_get_contents('php://input', false, null, 0, 4097);
    if (strlen($input) > 4096) {
        fail('El mensaje es demasiado largo.', 413);
    }
    $body = json_decode($input, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($body) || !is_string($body['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $body['csrf'])) {
        fail('La conexión ha cambiado. Recarga la consola y vuelve a intentarlo.', 403);
    }
    $action = $body['action'] ?? null;
    if ($action === 'begin') {
        if (!$_SESSION['started']) {
            session_regenerate_id(true);
            $_SESSION['started'] = true;
        }
        reply(snapshot($content));
    }
    if ($action === 'reset') {
        session_regenerate_id(true);
        fresh_game();
        reply(snapshot($content));
    }
    if (!in_array($action, ['hint', 'flag'], true)) {
        fail('Elige una acción de la consola.', 400);
    }
    $id = mission_id($body['mission'] ?? null);
    accessible($id);
    if ($action === 'hint') {
        $_SESSION['hints'][$id] = true;
        reply(snapshot($content, $id));
    }
    if ($id <= $_SESSION['completed']) {
        reply(snapshot($content, $id));
    }
    $flag = $body['flag'] ?? null;
    if (!is_string($flag) || strlen($flag) > 256 || trim($flag) === '') {
        fail('Pega la flag recuperada en la máquina, con su formato completo.', 400);
    }
    $now = time();
    $attempts = array_values(array_filter($_SESSION['attempts'], static fn (int $time): bool => $time > $now - 60));
    if (count($attempts) >= 12) {
        header('Retry-After: ' . max(1, 60 - ($now - $attempts[0])));
        fail('Demasiados envíos seguidos. Espera un minuto y revisa la evidencia.', 429);
    }
    $attempts[] = $now;
    $_SESSION['attempts'] = $attempts;
    $hashes = require dirname(__DIR__) . '/app/flag-hashes.php';
    if (!hash_equals($hashes[$id], hash('sha256', trim($flag)))) {
        fail('La flag no coincide con esta misión. Comprueba que la copiaste completa y que corresponde a la etapa actual.', 422);
    }
    $_SESSION['completed'] = $id;
    $_SESSION['attempts'] = [];
    reply(snapshot($content, $id));
} catch (JsonException) {
    fail('El mensaje no se pudo leer. Recarga la consola y vuelve a intentarlo.', 400);
} catch (Throwable $error) {
    error_log('Vault 217 companion: ' . get_class($error));
    fail('La consola no pudo registrar la operación. Vuelve a intentarlo.', 500);
}
