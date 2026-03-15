<?php // api/proveedores.php
if ($method === 'GET') {
    json_out(200, $pdo->query("SELECT * FROM proveedores ORDER BY nombre")->fetchAll());
    return;
}
if ($method === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO proveedores (nombre,nit,contacto,telefono,email) VALUES (?,?,?,?,?)");
    $stmt->execute([$body['nombre'],$body['nit']??null,$body['contacto']??null,$body['telefono']??null,$body['email']??null]);
    json_out(201, ['id' => $pdo->lastInsertId()]);
    return;
}
json_out(405, ['error' => 'Método no permitido']);
