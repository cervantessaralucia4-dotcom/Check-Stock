<?php
// api/dashboard.php
$hoy = date('Y-m-d');
$mes = date('Y-m-01');

$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();

$ventasHoy = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM ventas WHERE fecha=?");
$ventasHoy->execute([$hoy]);
$ventasHoy = (float)$ventasHoy->fetchColumn();

$alertas = $pdo->query("
    SELECT COUNT(*) FROM producto_bodega
    WHERE stock_minimo > 0 AND stock <= stock_minimo * 1.5
")->fetchColumn();

$comprasMes = $pdo->prepare("SELECT COUNT(*) FROM compras WHERE fecha >= ?");
$comprasMes->execute([$mes]);
$comprasMes = (int)$comprasMes->fetchColumn();

$ultimasVentas = $pdo->query("
    SELECT v.codigo, v.total, v.estado, v.fecha
    FROM ventas v ORDER BY v.creado_en DESC LIMIT 5
")->fetchAll();

$ultimasCompras = $pdo->query("
    SELECT c.codigo, c.total, c.estado, c.fecha,
           p.nombre AS proveedor, b.nombre AS bodega
    FROM compras c
    LEFT JOIN proveedores p ON p.id = c.proveedor_id
    LEFT JOIN bodegas b     ON b.id = c.bodega_id
    ORDER BY c.creado_en DESC LIMIT 4
")->fetchAll();

json_out(200, [
    'total_productos' => (int)$totalProductos,
    'ventas_hoy'      => $ventasHoy,
    'alertas'         => (int)$alertas,
    'compras_mes'     => $comprasMes,
    'ultimas_ventas'  => $ultimasVentas,
    'ultimas_compras' => $ultimasCompras,
]);
