<?php // api/bodegas.php
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT b.*, COUNT(pb.id) AS total_productos,
               SUM(CASE WHEN pb.stock_minimo>0 AND pb.stock<=pb.stock_minimo THEN 1 ELSE 0 END) AS alertas
        FROM bodegas b
        LEFT JOIN producto_bodega pb ON pb.bodega_id = b.id
        WHERE b.activa = 1
        GROUP BY b.id ORDER BY b.nombre
    ");
    json_out(200, $stmt->fetchAll());
    return;
}
if ($method === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO bodegas (nombre,direccion,tipo,pasillos) VALUES (?,?,?,?)");
    $stmt->execute([$body['nombre'],$body['direccion']??null,$body['tipo']??'bodega',$body['pasillos']??1]);
    json_out(201, ['id' => $pdo->lastInsertId()]);
    return;
}
json_out(405, ['error' => 'Método no permitido']);
