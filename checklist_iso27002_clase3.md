

---

## 4. Documentación y justificación del hallazgo de autenticidad

### 4.1 Situación observada en el inicio de sesión

**Ubicación:** `login.php`, líneas 4-18.

El formulario de acceso recibe dos campos (`usuario` y `clave`) y los inserta **sin ningún tratamiento** dentro de una cadena SQL:

```php
$usuario = $_POST['usuario'];
$clave   = $_POST['clave'];

$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$clave'";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $_SESSION['usuario_id'] = $fila['id'];
    $_SESSION['usuario']    = $fila['usuario'];
    echo "Bienvenido, " . $fila['usuario'] . ". Acceso concedido.";
}
```

**Prueba ejecutada (bypass de autenticación):**

| Campo | Valor introducido |
|---|---|
| Usuario | `' OR ''='` |
| Clave | `' OR ''='` |

La consulta que llega finalmente al gestor es:

```sql
SELECT * FROM usuarios WHERE usuario = '' OR ''='' AND clave = '' OR ''=''
```

La condición `''=''` es siempre verdadera, por lo que la cláusula `WHERE` deja de filtrar y la consulta **devuelve todas las filas de la tabla `usuarios`**. Como el código solo comprueba `num_rows > 0` (línea 11) y luego toma la **primera fila** con `fetch_assoc()` (línea 12), el sistema:

1. Concede el acceso sin conocer ninguna contraseña.
2. Asigna a la sesión la identidad de la primera fila de la tabla, que según `compraseguras.sql:10-12` es **`admin`**.
3. Responde: *"Bienvenido, admin. Acceso concedido."*

**Resultado: cualquier persona en Internet puede iniciar sesión como `admin` sin credenciales.**

**Segunda situación observada (suplantación visual de identidad):** la columna `usuarios.usuario` (`compraseguras.sql:6`) **carece de restricción `UNIQUE`** y `registro.php` no verifica duplicados. Por lo tanto, un atacante puede registrar legítimamente una segunda cuenta llamada `admin` con la clave que él elija. Ante los ojos de cualquier otro usuario —y ante el propio mensaje de bienvenida de `login.php:15`— ambas cuentas son **indistinguibles**: el sistema muestra el mismo nombre para dos identidades diferentes.

### 4.2 Justificación: por qué esto compromete la autenticidad (y no la confidencialidad)

**Definición aplicada.** La **autenticidad** es la propiedad que garantiza que una entidad es *quien dice ser*, es decir, que la identidad reclamada por un sujeto ha sido genuinamente verificada. La **confidencialidad**, en cambio, garantiza que la información solo sea accesible a quien está autorizado a conocerla.

El hallazgo compromete la **autenticidad** por las siguientes razones técnicas:

1. **El sistema deja de verificar identidad y pasa a verificar existencia.** El control de `login.php:11` no pregunta *"¿es esta persona quien dice ser?"* sino *"¿la consulta devolvió al menos una fila?"*. Al poder manipularse la consulta misma, el atacante **no rompe el secreto de la contraseña: elimina la necesidad de la contraseña**. Nunca llega a conocer `admin123`; simplemente hace que la pregunta de verificación sea irrelevante. El mecanismo de prueba de identidad ha sido anulado, no descifrado.

2. **La identidad afirmada por el sistema es falsa.** Tras el bypass, `$_SESSION['usuario_id']` y `$_SESSION['usuario']` (líneas 13-14) contienen la identidad de `admin`, y **toda operación posterior del atacante quedará atribuida a `admin`**. El sistema, con toda su lógica funcionando "correctamente", está afirmando un hecho falso sobre quién está actuando. Esa afirmación falsa es precisamente la ruptura de la autenticidad, y arrastra consigo el **no repudio**: `admin` no podría negar de forma creíble unas acciones que el sistema registra a su nombre.

3. **La suplantación visual traslada el fallo al usuario final.** Con dos cuentas `admin` posibles, la identidad mostrada en pantalla deja de ser un identificador único. Un cliente que interactúe con el falso `admin` no tiene ningún elemento para distinguirlo del legítimo: el sistema le está presentando una identidad **no auténtica** como si lo fuera.

**Diferencia con la confidencialidad, sobre este mismo caso.** Si el atacante hubiera consultado la base de datos y leído `admin / admin123` en texto plano (hallazgo A.10), habría vulnerado la **confidencialidad**: obtuvo información secreta que no debía conocer. Es un problema distinto y separable. Aquí, en cambio, **el secreto sigue intacto y sin embargo el acceso se produce**: la falla no está en la protección del dato, sino en el proceso de verificación de la identidad. Dicho brevemente:

> **Confidencialidad rota = "supe tu contraseña".**
> **Autenticidad rota = "no necesité tu contraseña, y el sistema cree que soy tú".**

Ambas propiedades están afectadas en *Compra Segura*, pero por causas independientes: la confidencialidad por el almacenamiento en texto plano (A.10) y la autenticidad por la ausencia de una verificación de identidad confiable (A.9 + A.14). Corregir el almacenamiento de contraseñas **no repararía** el bypass, y corregir el bypass **no repararía** la exposición de las contraseñas: es la demostración práctica de que se trata de dos principios distintos.

### 4.3 Remediación propuesta para el hallazgo de autenticidad

```php
// 1) Consulta preparada: los datos nunca se interpretan como código SQL
$stmt = $conn->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();

// 2) Verificación criptográfica real de la identidad (una sola fila, un solo usuario)
if ($fila && password_verify($clave, $fila['clave'])) {
    session_regenerate_id(true);                 // 3) evita fijación de sesión
    $_SESSION['usuario_id'] = $fila['id'];
    echo "Bienvenido, " . htmlspecialchars($fila['usuario']);
} else {
    error_log("Login fallido: usuario={$usuario} ip={$_SERVER['REMOTE_ADDR']}"); // 4) traza (A.12)
    echo "Usuario o clave incorrectos.";         // 5) mensaje genérico, no revela cuál falló
}
```

Complementariamente, en base de datos: `ALTER TABLE usuarios MODIFY clave VARCHAR(255) NOT NULL, ADD UNIQUE KEY uk_usuario (usuario);`

---

