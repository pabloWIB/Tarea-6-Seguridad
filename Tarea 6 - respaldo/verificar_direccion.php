<?php
// Archivo temporal de comprobacion: se borra de la carpeta publica al terminar las pruebas.
$origen = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !in_array($origen, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die("Acceso denegado.");
}

include 'conexion.php';

$resultado = $conn->query("SELECT usuario, direccion FROM usuarios WHERE direccion IS NOT NULL ORDER BY id DESC LIMIT 1");
$fila = $resultado->fetch_assoc();
if (!$fila) {
    die("No hay usuarios con direccion registrada.");
}

$claveCifrado = getenv('NEGOCIOSEGURO_CLAVE_CIFRADO');
$metodo       = "AES-256-CBC";
$ivLength     = openssl_cipher_iv_length($metodo);

$datosGuardados   = base64_decode($fila['direccion']);
$iv               = substr($datosGuardados, 0, $ivLength);
$direccionCifrada = substr($datosGuardados, $ivLength);

$direccionOriginal = openssl_decrypt($direccionCifrada, $metodo, $claveCifrado, 0, $iv);

echo "Usuario: " . htmlspecialchars($fila['usuario']) . "<br>";
echo "Dirección guardada en la base: " . htmlspecialchars($fila['direccion']) . "<br>";
echo "Dirección descifrada: " . ($direccionOriginal === false ? "no se pudo descifrar" : htmlspecialchars($direccionOriginal)) . "<br>";
?>
