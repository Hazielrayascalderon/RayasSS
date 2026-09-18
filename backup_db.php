<?php
/**
 * backup_db.php
 * ---------------------------------------------------------
 * Genera un respaldo completo (estructura + datos) de la base de datos
 * en un archivo .sql (comprimido con gzip si la extensión está disponible)
 * dentro de la carpeta /backups/.
 *
 * Se puede ejecutar de dos formas:
 *   1) Manualmente, como Administrador logueado, desde gestion_respaldos.php
 *   2) Automáticamente, sin sesión, llamando a esta URL con el token secreto:
 *      https://tu-dominio.com/backup_db.php?token=CAMBIA_ESTE_TOKEN
 *      (útil para programarlo con un servicio externo de cron, ver más abajo)
 * ---------------------------------------------------------
 */

// ===================== CONFIGURACIÓN =====================
// Cambia este token por uno propio y secreto antes de usarlo en un cron externo.
define('BACKUP_TOKEN', 'CAMBIA_ESTE_TOKEN_2026');
// Cuántos días conservar los respaldos antes de borrarlos automáticamente.
define('BACKUP_RETENCION_DIAS', 30);
// Cuántas filas incluir por sentencia INSERT (rendimiento vs. legibilidad).
define('BACKUP_LOTE_FILAS', 200);
// ===========================================================

session_start();
require_once 'conexion.php';

$rol = $_SESSION['rol'] ?? '';
$rol_clean = mb_strtolower(trim($rol), 'UTF-8');
$es_admin_logueado = isset($_SESSION['id_usuario']) && in_array($rol_clean, ['administrador']);
$token_valido = isset($_GET['token']) && hash_equals(BACKUP_TOKEN, $_GET['token']);

if (!$es_admin_logueado && !$token_valido) {
    http_response_code(403);
    die("Acceso denegado.");
}

set_time_limit(300);
@ini_set('memory_limit', '256M');

$carpeta_backups = __DIR__ . '/backups';
if (!is_dir($carpeta_backups)) {
    mkdir($carpeta_backups, 0755, true);
}
// Evita que cualquiera liste o descargue los .sql directamente por URL.
$htaccess = $carpeta_backups . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Order Allow,Deny\nDeny from all\n");
}

try {
    $db = new Conexion();
    $conn = $db->getConexion();

    $tablas = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $usar_gzip = function_exists('gzopen');
    $timestamp = date('Y-m-d_His');
    $nombre_archivo = "backup_{$timestamp}.sql" . ($usar_gzip ? '.gz' : '');
    $ruta_completa = $carpeta_backups . '/' . $nombre_archivo;

    $handle = $usar_gzip ? gzopen($ruta_completa, 'w9') : fopen($ruta_completa, 'w');
    if (!$handle) {
        throw new Exception("No se pudo crear el archivo de respaldo en el servidor.");
    }
    $escribir = function ($texto) use ($handle, $usar_gzip) {
        $usar_gzip ? gzwrite($handle, $texto) : fwrite($handle, $texto);
    };

    $escribir("-- Respaldo de base de datos - Control Documental\n");
    $escribir("-- Generado el: " . date('d/m/Y H:i:s') . "\n");
    $escribir("-- Tablas incluidas: " . count($tablas) . "\n\n");
    $escribir("SET FOREIGN_KEY_CHECKS=0;\n");
    $escribir("SET NAMES utf8mb4;\n\n");

    foreach ($tablas as $tabla) {
        // --- Estructura ---
        $crear = $conn->query("SHOW CREATE TABLE `$tabla`")->fetch();
        $escribir("-- ----------------------------\n-- Tabla: `$tabla`\n-- ----------------------------\n");
        $escribir("DROP TABLE IF EXISTS `$tabla`;\n");
        $escribir($crear['Create Table'] . ";\n\n");

        // --- Datos ---
        $total_filas = intval($conn->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn());
        if ($total_filas === 0) { continue; }

        $stmt = $conn->query("SELECT * FROM `$tabla`");
        $columnas = null;
        $lote = [];
        $filas_en_lote = 0;

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($columnas === null) {
                $columnas = array_keys($fila);
                $columnas_sql = '`' . implode('`, `', $columnas) . '`';
            }
            $valores = array_map(function ($v) use ($conn) {
                if ($v === null) { return 'NULL'; }
                return $conn->quote($v);
            }, array_values($fila));
            $lote[] = '(' . implode(', ', $valores) . ')';
            $filas_en_lote++;

            if ($filas_en_lote >= BACKUP_LOTE_FILAS) {
                $escribir("INSERT INTO `$tabla` ($columnas_sql) VALUES\n" . implode(",\n", $lote) . ";\n");
                $lote = [];
                $filas_en_lote = 0;
            }
        }
        if (count($lote) > 0) {
            $escribir("INSERT INTO `$tabla` ($columnas_sql) VALUES\n" . implode(",\n", $lote) . ";\n");
        }
        $escribir("\n");
    }

    $escribir("SET FOREIGN_KEY_CHECKS=1;\n");
    $usar_gzip ? gzclose($handle) : fclose($handle);

    // --- Limpieza de respaldos antiguos ---
    $limite = time() - (BACKUP_RETENCION_DIAS * 86400);
    $borrados = [];
    foreach (glob($carpeta_backups . '/backup_*.sql*') as $archivo) {
        if (filemtime($archivo) < $limite) {
            unlink($archivo);
            $borrados[] = basename($archivo);
        }
    }

    $resultado = [
        'ok' => true,
        'archivo' => $nombre_archivo,
        'tamano' => filesize($ruta_completa),
        'tablas' => count($tablas),
        'borrados' => $borrados,
    ];

} catch (Exception $e) {
    http_response_code(500);
    $resultado = ['ok' => false, 'error' => $e->getMessage()];
}

// Si viene de un cron externo (sin sesión), responde en texto plano simple.
if ($token_valido && !$es_admin_logueado) {
    header('Content-Type: text/plain; charset=utf-8');
    if ($resultado['ok']) {
        echo "OK - Respaldo generado: {$resultado['archivo']} (" . round($resultado['tamano'] / 1024, 1) . " KB)\n";
        if (!empty($resultado['borrados'])) {
            echo "Respaldos antiguos eliminados: " . implode(', ', $resultado['borrados']) . "\n";
        }
    } else {
        echo "ERROR - " . $resultado['error'] . "\n";
    }
    exit;
}

// Si viene del panel de administración (con sesión), regresa a la pantalla de gestión.
header("Location: gestion_respaldos.php?" . ($resultado['ok'] ? 'ok=1&archivo=' . urlencode($resultado['archivo']) : 'error=' . urlencode($resultado['error'])));
exit;
