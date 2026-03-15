<?php
// api/ventas.php

if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor, b.nombre AS bodega
        FROM ventas v
        LEFT JOIN clientes c  ON c.id = v.cliente_id
        LEFT JOIN usuarios u  ON u.id = v.usuario_id
        LEFT JOIN bodegas b   ON b.id = v.bodega_id
        ORDER BY v.creado_en DESC LIMIT 50
    ");
    json_out(200, $stmt->fetchAll());
    return;
}

if ($method === 'POST') {
    $items     = $body['items']     ?? [];
    $bodegaId  = $body['bodega_id'] ?? 1;
    $clienteId = $body['cliente_id'] ?? 1;

    if (empty($items)) { json_out(422, ['error' => 'El carrito está vacío']); return; }

    // Verificar stock antes de vender
    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            SELECT stock FROM producto_bodega
            WHERE producto_id=? AND bodega_id=?
        ");
        $stmt->execute([$item['producto_id'], $bodegaId]);
        $row = $stmt->fetch();
        if (!$row || $row['stock'] < $item['cantidad']) {
            $p = $pdo->prepare("SELECT nombre FROM productos WHERE id=?");
            $p->execute([$item['producto_id']]);
            $nom = $p->fetchColumn();
            json_out(422, ['error' => "Stock insuficiente para: $nom"]);
            return;
        }
    }

    $pdo->beginTransaction();
    try {
        $subtotal = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $items));
        $iva      = round($subtotal * 0.19);
        $total    = $subtotal + $iva;

        $count  = $pdo->query("SELECT COUNT(*)+1 FROM ventas")->fetchColumn();
        $codigo = 'V-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO ventas (codigo, subtotal, iva, total, pagado, cambio, estado, cliente_id, bodega_id)
            VALUES (?,?,?,?,?,0,'completada',?,?)
        ");
        $stmt->execute([$codigo, $subtotal, $iva, $total, $total, $clienteId, $bodegaId]);
        $ventaId = $pdo->lastInsertId();

        // Insertar detalles — el TRIGGER descuenta el stock automáticamente
        $det = $pdo->prepare("
            INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, total_linea)
            VALUES (?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $det->execute([
                $ventaId, $item['producto_id'], $item['cantidad'],
                $item['precio_unitario'],
                $item['precio_unitario'] * $item['cantidad'],
            ]);
        }

        $pdo->commit();
        json_out(201, ['id' => $ventaId, 'codigo' => $codigo, 'total' => $total]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_out(500, ['error' => $e->getMessage()]);
    }
    return;
}

json_out(405, ['error' => 'Método no permitido']);
