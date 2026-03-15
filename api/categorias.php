<?php // api/categorias.php
json_out(200, $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll());
