<?php
declare(strict_types=1);

const MISSION_COUNT = 5;
const SESSION_LIFETIME = 60 * 60 * 24 * 30;

function reply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function fail(string $message, int $status): never
{
    reply(['error' => $message], $status);
}

function boot(): void
{
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    $directory = getenv('VAULT217_SESSION_PATH') ?: dirname(__DIR__) . '/var/sessions';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        fail('No se pudo abrir el registro de operaciones. Avisa al instructor.', 503);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_save_path($directory);
    session_name('vault217_partida');
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        fail('No se pudo abrir tu partida. Avisa al instructor.', 503);
    }
    if (!isset($_SESSION['csrf'])) {
        fresh_game();
    }
    // Renueva la duración al regresar a la partida.
    setcookie(session_name(), session_id(), [
        'expires' => time() + SESSION_LIFETIME,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function fresh_game(): void
{
    $_SESSION = [
        'csrf' => bin2hex(random_bytes(32)),
        'started' => false,
        'completed' => 0,
        'hints' => [],
        'attempts' => [],
    ];
}

function mission_id(mixed $value): int
{
    if (!is_int($value) || $value < 1 || $value > MISSION_COUNT) {
        fail('Selecciona una misión válida.', 400);
    }
    return $value;
}

function accessible(int $id): void
{
    if (!$_SESSION['started'] || $id > min($_SESSION['completed'] + 1, MISSION_COUNT)) {
        fail('Esta misión sigue bloqueada. Valida primero la evidencia anterior.', 403);
    }
}

function snapshot(array $content, ?int $selected = null): array
{
    $completed = $_SESSION['completed'];
    $active = min($completed + 1, MISSION_COUNT);
    $missions = [];
    foreach ($content as $id => $mission) {
        $unlocked = $_SESSION['started'] && $id <= $active;
        $missions[] = [
            'id' => $id,
            'unlocked' => $unlocked,
            'completed' => $id <= $completed,
            'title' => $unlocked ? $mission['title'] : null,
            'location' => $unlocked ? $mission['location'] : null,
        ];
    }
    $result = [
        'csrf' => $_SESSION['csrf'],
        'started' => $_SESSION['started'],
        'completed' => $completed,
        'activeMission' => $active,
        'missionCount' => MISSION_COUNT,
        'missions' => $missions,
        'hintsUsed' => count($_SESSION['hints']),
    ];
    if ($_SESSION['started']) {
        $id = $selected ?? $active;
        accessible($id);
        $mission = $content[$id];
        $result['mission'] = [
            'id' => $id,
            'title' => $mission['title'],
            'location' => $mission['location'],
            'transmission' => $mission['transmission'],
            'situation' => $mission['situation'],
            'objective' => $mission['objective'],
            'sourcePage' => $mission['page'],
            'completed' => $id <= $completed,
            'hint' => isset($_SESSION['hints'][$id]) ? $mission['hint'] : null,
            'debrief' => $id <= $completed ? $mission['debrief'] : null,
        ];
    }
    if ($completed === MISSION_COUNT) {
        $result['ending'] = [
            'title' => 'El silencio también es una respuesta.',
            'body' => 'Has seguido una señal hasta el corazón de Vault 217. Recuperaste las cinco evidencias y el informe que el refugio guardaba en silencio. La Brotherhood of Steel tiene ahora algo más que una baliza: tiene un testimonio de lo que ocurrió aquí.',
        ];
    }
    return $result;
}
