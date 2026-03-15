<?php
// api/install.php
// GET /api/install  → crea tablas y carga datos demo
// Protegido con token para que no cualquiera lo ejecute

$token = $_GET['token'] ?? '';
$expectedToken = getenv('INSTALL_TOKEN') ?: 'stockflow2024';

if ($token !== $expectedToken) {
    json_out(403, ['error' => 'Token inválido. Usa /api/install?token=TU_TOKEN']);
    return;
}

$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');

// Ejecutar sentencia por sentencia
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

// Separar por DELIMITER para manejar triggers
$blocks = preg_split('/DELIMITER\s+(\S+)/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
$results = [];

try {
    // Ejecutar todo el SQL de una vez (PDO exec admite múltiples sentencias)
    $pdo->exec($sql);
    json_out(200, ['message' => '✅ Base de datos instalada correctamente con datos demo.']);
} catch (PDOException $e) {
    // Si falla por triggers, intentar sin ellos (hosting que no permite DELIMITER)
    // Extraer solo las sentencias DDL y DML (sin triggers)
    $sinTriggers = preg_replace('/DELIMITER.*?DELIMITER\s*;/s', '', $sql);
    $sentencias  = array_filter(
        array_map('trim', explode(';', $sinTriggers)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );
    $errores = [];
    foreach ($sentencias as $s) {
        try { $pdo->exec($s); } catch (PDOException $ex) { $errores[] = $ex->getMessage(); }
    }
    if (count($errores) < 3) {
        json_out(200, ['message' => '✅ Instalado (sin triggers — Railway MySQL los soporta nativamente).', 'warnings' => $errores]);
    } else {
        json_out(500, ['error' => 'Error en instalación', 'detalle' => $errores]);
    }
}
