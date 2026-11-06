<?php
include 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $claveTexto = $_POST['clave'] ?? '';
    $direccion  = trim($_POST['direccion'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $usuario)) {
        die("Nombre de usuario invalido. Use solo letras, numeros y guion bajo (3 a 20 caracteres).");
    }

    if (strlen($claveTexto) < 8) {
        die("La clave debe tener al menos 8 caracteres.");
    }

    if ($direccion === '' || mb_strlen($direccion) > 150) {
        die("La direccion de envio es obligatoria (maximo 150 caracteres).");
    }

    // La clave de cifrado vive en una variable de entorno del sistema, no en el codigo
    $claveCifrado = getenv('NEGOCIOSEGURO_CLAVE_CIFRADO');
    if ($claveCifrado === false || strlen($claveCifrado) !== 32) {
        die("El registro no esta disponible en este momento.");
    }

    // La contrasena se guarda como hash, nunca como texto
    $claveHash = password_hash($claveTexto, PASSWORD_DEFAULT);

    // La direccion se cifra con AES-256-CBC y un IV aleatorio por usuario
    $metodo   = "AES-256-CBC";
    $ivLength = openssl_cipher_iv_length($metodo);
    $iv       = openssl_random_pseudo_bytes($ivLength);

    $direccionCifrada = openssl_encrypt($direccion, $metodo, $claveCifrado, 0, $iv);
    if ($direccionCifrada === false) {
        die("No se pudo registrar el usuario.");
    }

    // Guardar IV y texto cifrado juntos
    $direccionParaGuardar = base64_encode($iv . $direccionCifrada);

    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, direccion) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $usuario, $claveHash, $direccionParaGuardar);

    if ($stmt->execute()) {
        echo "Usuario registrado con exito.";
    } else {
        echo "No se pudo registrar el usuario.";
    }
    $stmt->close();
}
?>
<form method="POST">
  Nuevo usuario: <input type="text" name="usuario"><br>
  Clave: <input type="password" name="clave"><br>
  Dirección de envío: <input type="text" name="direccion"><br>
  <button type="submit">Registrarme</button>
</form>
