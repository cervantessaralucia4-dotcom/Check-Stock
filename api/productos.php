<?php
// api/productos.php

if ($method === 'GET') {
    $cat = $_GET['categoria_id'] ?? null;
    $q   = $_GET['q'] ?? null;

    $sql = "
        SELECT p.*, c.nombre AS categoria, c.color AS cat_color,
               pb.bodega_id, pb.pasillo, pb.estante, pb.stock, pb.stock_minimo,
               b.nombre AS bodega
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN producto_bodega pb ON pb.producto_id = p.id
        LEFT JOIN bodegas b ON b.id = pb.bodega_id
        WHERE p.activo = 1
    ";
    $params = [];

    if ($cat) { $sql .= " AND p.categoria_id = ?"; $params[] = $cat; }
    if ($q)   { $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
                $params[] = "%$q%"; $params[] = "%$q%"; }

    $sql .= " ORDER BY p.nombre";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_out(200, $stmt->fetchAll());
    return;
}

if ($method === 'POST') {
    $required = ['codigo','nombre','precio_compra','precio_venta'];
    foreach ($required as $f) {
        if (empty($body[$f])) { json_out(422, ['error' => "Campo '$f' es obligatorio"]); return; }
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO productos (codigo, nombre, descripcion, marca, modelo,
                tipo_unidad, precio_compra, precio_venta, categoria_id)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $body['codigo'], $body['nombre'], $body['descripcion'] ?? null,
            $body['marca'] ?? null, $body['modelo'] ?? null,
            $body['tipo_unidad'] ?? 'unidad',
            $body['precio_compra'], $body['precio_venta'],
            $body['categoria_id'] ?? null,
        ]);
        $prodId = $pdo->lastInsertId();

        if (!empty($body['bodega_id'])) {
            $stmt2 = $pdo->prepare("
                INSERT INTO producto_bodega (producto_id, bodega_id, pasillo, estante, stock, stock_minimo)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE stock=VALUES(stock), stock_minimo=VALUES(stock_minimo)
            ");
            $stmt2->execute([
                $prodId, $body['bodega_id'],
                $body['pasillo'] ?? null, $body['estante'] ?? null,
                $body['stock'] ?? 0, $body['stock_minimo'] ?? 0,
            ]);
        }

        $pdo->commit();
        json_out(201, ['id' => $prodId, 'message' => 'Producto creado']);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_out(500, ['error' => $e->getMessage()]);
    }
    return;
}

if ($method === 'PUT' && $id) {
    $stmt = $pdo->prepare("
        UPDATE productos SET
            nombre=?, descripcion=?, marca=?, precio_compra=?,
            precio_venta=?, categoria_id=?
        WHERE id=?
    ");
    $stmt->execute([
        $body['nombre'], $body['descripcion'] ?? null, $body['marca'] ?? null,
        $body['precio_compra'], $body['precio_venta'],
        $body['categoria_id'] ?? null, $id,
    ]);
    json_out(200, ['message' => 'Producto actualizado']);
    return;
}

if ($method === 'DELETE' && $id) {
    $pdo->prepare("UPDATE productos SET activo=0 WHERE id=?")->execute([$id]);
    json_out(200, ['message' => 'Producto eliminado']);
    return;
}

json_out(405, ['error' => 'Método no permitido']);
