<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario        = $_POST['usuario'] ?? '';
    $claveIngresada = $_POST['clave'] ?? '';

    $stmt = $conn->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();

    // password_verify saca la sal y el costo del propio hash y compara de forma segura
    if ($fila && password_verify($claveIngresada, $fila['clave'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $fila['id'];
        $_SESSION['usuario']    = $fila['usuario'];

        // Si PHP cambia el algoritmo o el costo por defecto, el hash se renueva en este login
        if (password_needs_rehash($fila['clave'], PASSWORD_DEFAULT)) {
            $nuevoHash  = password_hash($claveIngresada, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $nuevoHash, $fila['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        echo "Bienvenido, " . htmlspecialchars($fila['usuario']) . ". Acceso concedido.";
    } else {
        echo "Usuario o clave incorrectos.";
    }
}
?>
<form method="POST">
  Usuario: <input type="text" name="usuario"><br>
  Clave: <input type="password" name="clave"><br>
  <button type="submit">Ingresar</button>
</form>
