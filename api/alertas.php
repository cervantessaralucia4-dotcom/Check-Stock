<?php // api/alertas.php
$stmt = $pdo->query("
    SELECT p.id AS producto_id, p.codigo, p.nombre AS producto,
           c.nombre AS categoria,
           b.nombre AS bodega,
           pb.pasillo, pb.estante, pb.stock, pb.stock_minimo,
           CASE
             WHEN pb.stock = 0                        THEN 'sin_stock'
             WHEN pb.stock <= pb.stock_minimo          THEN 'critico'
             ELSE 'bajo'
           END AS nivel
    FROM producto_bodega pb
    JOIN productos p  ON p.id = pb.producto_id
    JOIN bodegas b    ON b.id = pb.bodega_id
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE pb.stock_minimo > 0
      AND pb.stock <= pb.stock_minimo * 1.5
    ORDER BY pb.stock ASC
");
json_out(200, $stmt->fetchAll());
