<?php
// Script de una sola vez: pasa a hash las claves que siguen en texto plano.
// Solo se puede abrir desde el mismo equipo, y se borra de la carpeta publica al terminar.
$origen = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !in_array($origen, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die("Acceso denegado.");
}

include 'conexion.php';

$resultado = $conn->query("SELECT id, clave FROM usuarios");

$migrados   = 0;
$yaMigrados = 0;

$stmtUpdate = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");

while ($fila = $resultado->fetch_assoc()) {
    // Evitar volver a procesar contrasenas ya migradas (bcrypt empieza por $2y$)
    if (strpos($fila['clave'], '$2y$') === 0) {
        $yaMigrados++;
        continue;
    }

    $nuevoHash = password_hash($fila['clave'], PASSWORD_DEFAULT);

    $stmtUpdate->bind_param("si", $nuevoHash, $fila['id']);
    $stmtUpdate->execute();
    $migrados++;
}
$stmtUpdate->close();

echo "$migrados contraseñas migradas a hash seguro. ";
echo "$yaMigrados ya estaban migradas previamente.";
?>
