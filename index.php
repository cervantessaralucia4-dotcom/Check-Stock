<?php
// public/index.php  — punto de entrada único
// Sirve el frontend y enruta las llamadas /api/*

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// ── API ──────────────────────────────────────────────────────
if (str_starts_with($uri, '/api')) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../config/db.php';

    $segment = explode('/', ltrim($uri, '/'));
    // $segment[0]=api, $segment[1]=recurso, $segment[2]=id opcional
    $recurso = $segment[1] ?? '';
    $id      = isset($segment[2]) && is_numeric($segment[2]) ? (int)$segment[2] : null;

    // Leer body JSON
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    match($recurso) {
        'dashboard'       => require __DIR__ . '/../api/dashboard.php',
        'productos'       => require __DIR__ . '/../api/productos.php',
        'bodegas'         => require __DIR__ . '/../api/bodegas.php',
        'categorias'      => require __DIR__ . '/../api/categorias.php',
        'proveedores'     => require __DIR__ . '/../api/proveedores.php',
        'compras'         => require __DIR__ . '/../api/compras.php',
        'ventas'          => require __DIR__ . '/../api/ventas.php',
        'alertas'         => require __DIR__ . '/../api/alertas.php',
        'install'         => require __DIR__ . '/../api/install.php',
        default           => json_out(404, ['error' => "Recurso '$recurso' no existe"])
    };
    exit;
}

// ── FRONTEND — sirve index.html para cualquier otra ruta ─────
$file = __DIR__ . $uri;
if ($uri !== '' && $uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // servidor built-in de PHP sirve el archivo estático
}
readfile(__DIR__ . '/index.html');

// ── Helper global ────────────────────────────────────────────
function json_out(int $code, mixed $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
