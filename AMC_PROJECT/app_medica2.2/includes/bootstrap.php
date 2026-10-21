<?php
/**
 * Arranque común: configuración, sesión, conexión PDO y utilidades.
 * Todos los puntos de entrada (index.php, acciones.php, api.php) lo incluyen.
 */
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set(APP_TIMEZONE);

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Cookie de sesión no accesible desde JavaScript y no enviada desde otros sitios
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('APEXSESSID');
    session_start();
}

/* ------------------------------------------------------------------
 * Conexión a la base de datos
 * ------------------------------------------------------------------ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // APP_DB_DSN permite apuntar a otra base (por ejemplo, para pruebas) sin tocar config.php
    $dsnAlterno = getenv('APP_DB_DSN');
    try {
        if ($dsnAlterno) {
            $pdo = new PDO($dsnAlterno, null, null, $opciones);
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
            // Misma hora en PHP y en MySQL: los triggers comparan contra NOW() y CURDATE()
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!doctype html><meta charset="utf-8"><title>Error de conexión</title>'
           . '<div style="font-family:system-ui;max-width:560px;margin:80px auto;padding:24px;border:1px solid #fecdd3;border-radius:16px;background:#fff1f2;color:#9f1239">'
           . '<h2 style="margin-top:0">No se pudo conectar a la base de datos</h2>'
           . '<p>Verifica que MySQL esté encendido y que importaste <code>database/app_medica_citas.sql</code>. '
           . 'Revisa también los datos de <code>config/config.php</code>.</p>'
           . '<p style="font-size:12px;color:#be123c">Detalle: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
        exit;
    }
    return $pdo;
}

/* ------------------------------------------------------------------
 * Utilidades de salida
 * ------------------------------------------------------------------ */

/** Escapa texto para HTML. */
function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Ícono de Lucide (se convierte en SVG con lucide.createIcons()). */
function icono(string $nombre, string $clases = 'w-4 h-4'): string
{
    return '<i data-lucide="' . e($nombre) . '" class="' . e($clases) . '"></i>';
}

/** URL de una vista del router. */
function url(string $vista, array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(['vista' => $vista], $params));
}

function hoy(): string
{
    return date('Y-m-d');
}

function dinero($monto, int $decimales = 0): string
{
    return '$' . number_format((float)$monto, $decimales, '.', ',');
}

function hora_corta(?string $hora): string
{
    return substr((string)$hora, 0, 5);
}

/* ------------------------------------------------------------------
 * Seguridad: token CSRF para todos los formularios POST
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/* ------------------------------------------------------------------
 * Mensajes flash (sobreviven una redirección)
 * ------------------------------------------------------------------ */
function flash(string $clave, ?string $mensaje = null): ?string
{
    if ($mensaje !== null) {
        $_SESSION['flash'][$clave] = $mensaje;
        return null;
    }
    $valor = $_SESSION['flash'][$clave] ?? null;
    unset($_SESSION['flash'][$clave]);
    return $valor;
}

/** Valores previos de un formulario rechazado, para no perder lo capturado. */
function viejo(string $campo, $porDefecto = '')
{
    return $_SESSION['viejo'][$campo] ?? $porDefecto;
}

function limpiar_viejo(): void
{
    unset($_SESSION['viejo']);
}

/* ------------------------------------------------------------------
 * Reglas de negocio compartidas
 * ------------------------------------------------------------------ */

const ESTADOS_CITA = ['Programada', 'Confirmada', 'Completada', 'Cancelada'];

/** Turnos de 30 minutos que se ofrecen al agendar (08:00 a 18:30). */
define('HORARIOS_CITA', array_map(fn($i) => sprintf('%02d:%02d', 8 + intdiv($i, 2), ($i % 2) * 30), range(0, 21)));

/** Clases de la etiqueta de estado de una cita. */
function clases_estado_cita(string $estado): string
{
    $mapa = [
        'Programada' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'Confirmada' => 'bg-teal-50 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-800',
        'Completada' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'Cancelada'  => 'bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    ];
    return $mapa[$estado] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
}

/** Nombre completo de un paciente (columnas nombre, ap_paterno, ap_materno). */
function nombre_completo(array $p): string
{
    return trim($p['nombre'] . ' ' . $p['ap_paterno'] . ' ' . ($p['ap_materno'] ?? ''));
}

function iniciales_paciente(array $p): string
{
    return mb_strtoupper(mb_substr($p['nombre'], 0, 1) . mb_substr($p['ap_paterno'], 0, 1));
}

/** "Hace 10 min", "Hace 2 h", "Justo ahora"... */
function tiempo_relativo(string $fecha): string
{
    $seg = time() - strtotime($fecha);
    if ($seg < 60) {
        return 'Justo ahora';
    }
    if ($seg < 3600) {
        return 'Hace ' . floor($seg / 60) . ' min';
    }
    if ($seg < 86400) {
        return 'Hace ' . floor($seg / 3600) . ' h';
    }
    $dias = floor($seg / 86400);
    return 'Hace ' . $dias . ($dias == 1 ? ' día' : ' días');
}

require_once __DIR__ . '/datos.php';
require_once __DIR__ . '/tema.php';
require_once __DIR__ . '/componentes.php';
require_once __DIR__ . '/fotos.php';
require_once __DIR__ . '/auth.php';
