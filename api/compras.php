<?php
// api/compras.php

if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT c.*, p.nombre AS proveedor, b.nombre AS bodega
        FROM compras c
        LEFT JOIN proveedores p ON p.id = c.proveedor_id
        LEFT JOIN bodegas b     ON b.id = c.bodega_id
        ORDER BY c.creado_en DESC LIMIT 50
    ");
    json_out(200, $stmt->fetchAll());
    return;
}

if ($method === 'POST') {
    $items      = $body['items']       ?? [];
    $provId     = $body['proveedor_id'] ?? null;
    $bodegaId   = $body['bodega_id']   ?? null;

    if (!$provId || !$bodegaId || empty($items)) {
        json_out(422, ['error' => 'Proveedor, bodega e items son obligatorios']); return;
    }

    $pdo->beginTransaction();
    try {
        $total  = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $items));
        $count  = $pdo->query("SELECT COUNT(*)+1 FROM compras")->fetchColumn();
        $codigo = 'C-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO compras (codigo, total, estado, notas, proveedor_id, bodega_id)
            VALUES (?,?,'recibida',?,?,?)
        ");
        $stmt->execute([$codigo, $total, $body['notas'] ?? null, $provId, $bodegaId]);
        $compraId = $pdo->lastInsertId();

        // El TRIGGER suma el stock automáticamente
        $det = $pdo->prepare("
            INSERT INTO compra_detalle (compra_id, producto_id, cantidad, precio_unitario, total_linea)
            VALUES (?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $det->execute([
                $compraId, $item['producto_id'], $item['cantidad'],
                $item['precio_unitario'],
                $item['precio_unitario'] * $item['cantidad'],
            ]);
        }

        $pdo->commit();
        json_out(201, ['id' => $compraId, 'codigo' => $codigo, 'total' => $total]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_out(500, ['error' => $e->getMessage()]);
    }
    return;
}

json_out(405, ['error' => 'Método no permitido']);
