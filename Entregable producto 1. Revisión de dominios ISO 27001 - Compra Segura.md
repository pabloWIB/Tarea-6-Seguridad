# Entregable producto 1 — Revisión de dominios ISO 27001 en Compra Segura

**Pablo Nieto Pérez**

Seguridad de Aplicaciones

Tecnología en Desarrollo de Software para Negocios Digitales, Universidad de Manizales

03 de Septiembre de 2026

---

## Introducción

Este entregable recoge la revisión de cinco dominios de la norma ISO 27001 (A.9, A.10, A.12, A.14 y A.16) sobre la aplicación Compra Segura, tomando como base las Tareas 1 y 2 ya entregadas. La revisión se hizo sobre el código fuente ubicado en `C:\xampp\htdocs\compraseguras` y sobre la base de datos `compraseguras` consultada desde phpMyAdmin.

---

## Revisión por dominio

### A.9 Control de acceso

**Instrucción:** abrir `registro.php` y `login.php`. ¿Hay algún requisito mínimo de contraseña al registrarse? ¿Todos los usuarios tienen el mismo nivel de acceso, o existen roles diferenciados?

**¿Hay requisito mínimo de contraseña?**

No, no hay ninguno. En `registro.php` la clave pasa de la línea 5 a la línea 6 sin nada en medio:

```php
$clave = $_POST['clave'];                                                      // línea 5
$sql = "INSERT INTO usuarios (usuario, clave) VALUES ('$usuario', '$clave')";  // línea 6
```

No hay `strlen()`, ni `preg_match()`, ni `filter_var()`. El formulario tampoco valida del lado del cliente: en la línea 13 aparece `<input type="password" name="clave">` sin los atributos `minlength`, `pattern` ni `required`. El sistema acepta incluso una clave vacía.

**¿Existen roles diferenciados?**

No. La tabla `usuarios` solo tiene tres columnas:

| Campo | Tipo | Llave |
|---|---|---|
| `id` | int(11) | PRI, auto_increment |
| `usuario` | varchar(50) | |
| `clave` | varchar(50) | |

No existe columna `rol` ni `es_admin`, entonces "admin" es únicamente un nombre de usuario y no un nivel de acceso. `login.php` guarda en la sesión solo `usuario_id` y `usuario`, así que después del login no queda nada con qué distinguir a un cliente de un administrador. Todos los usuarios registrados tienen exactamente los mismos permisos.

**Estado del dominio: No cumple**

---

### A.10 Criptografía

**Instrucción:** revisar la tabla `usuarios` en phpMyAdmin, columna `clave`.

No se aplica ninguna criptografía. Contenido actual de la tabla:

| id | usuario | clave |
|---|---|---|
| 1 | admin | `admin123` |
| 2 | jperez | `miclave2024` |
| 3 | prueba | `1234` |

Las tres contraseñas son legibles directamente desde phpMyAdmin. Además la columna está declarada como `clave VARCHAR(50)`, longitud que no alcanza para almacenar un hash bcrypt de `password_hash()` (60 caracteres), así que el problema no se puede corregir sin alterar antes la estructura de la tabla.

Como hallazgo adicional, la contraseña del usuario root de MySQL también está en texto plano dentro de `conexion.php` (línea 3) y es `admin123`, la misma clave del usuario admin de la aplicación.

**Estado del dominio: No cumple**

---

### A.12 Seguridad de las operaciones

**Instrucción:** revisar si algún archivo registra quién inició sesión, o intentos fallidos.

No existe ningún registro. Se verificó de tres formas:

1. Búsqueda en los cinco archivos PHP de las funciones `error_log`, `file_put_contents`, `fopen`, `syslog`, y de los términos `log`, `intento`, `auditoria` y `bitacora`: cero resultados.
2. No existe ningún archivo `.log` dentro del proyecto.
3. La base de datos solo contiene tres tablas: `pedidos`, `productos` y `usuarios`. No hay tabla de auditoría.

En `login.php` el acceso exitoso solo ejecuta `echo "Bienvenido, ..."` y el fallido `echo "Usuario o clave incorrectos."`. En ninguno de los dos casos queda algo escrito. El único campo de tiempo de todo el sistema es `pedidos.fecha`, que registra el momento de un pedido, no los accesos.

La consecuencia práctica es que el bypass de autenticación con `' OR '1'='1` documentado en la Tarea 2 no dejó ningún rastro en el sistema.

**Estado del dominio: No cumple**

---

### A.14 Adquisición, desarrollo y mantenimiento

**Instrucción:** revisar `pedido.php` y `buscar_producto.php`. ¿El código valida lo que el usuario envía antes de usarlo?

Ninguno de los dos archivos valida los datos recibidos.

**`pedido.php`** — el precio lo envía el cliente y se usa tal cual:

```php
$precio = $_POST['precio'];     // viene de <input type="hidden" value="3000">
$total  = $precio * $cantidad;  // se calcula con el dato del navegador
```

No hay `intval()` ni `floatval()`, y el código nunca consulta la tabla `productos` para comparar contra el precio real. La cantidad tampoco se valida, así que acepta valores negativos. Los cuatro valores se concatenan directamente dentro del `INSERT`.

**`buscar_producto.php`** — el parámetro `q` de la URL entra directo a la consulta:

```php
$sql = "SELECT * FROM productos WHERE nombre LIKE '%$busqueda%'";
```

Y la salida se imprime sin escapar (`echo $fila['nombre']` sin `htmlspecialchars()`), lo que añade riesgo de XSS sobre el mismo archivo.

**Estado del dominio: No cumple**

---

### A.16 Gestión de incidentes

**Instrucción:** discutir como equipo, sin revisar código: si un cliente reportara hoy un problema de seguridad, ¿existe algún procedimiento definido en el proyecto?

No existe ningún procedimiento. En el proyecto no hay archivo `README`, ni `SECURITY.md`, ni documento de contacto, ni registro de incidentes.

Lo único documentado es `HALLAZGOS.md`, que corresponde al informe de auditoría de la Tarea 1. Ese archivo describe fallos encontrados, pero no es un procedimiento de gestión de incidentes: no define a quién se reporta, en cuánto tiempo se debe responder, cómo se clasifica la gravedad, ni quién toma la decisión de actuar.

Si un cliente reportara hoy un problema de seguridad, no habría canal definido para recibir el reporte. Y como tampoco existe ningún registro de actividad según lo revisado en A.12, no habría evidencia con qué investigar lo reportado.

**Estado del dominio: No cumple**

---

## Desarrollo por pasos

### Paso 1 — Control

Exigencia de complejidad y longitud mínima en la creación de credenciales del sistema.

*(Dominio asociado: A.9 Control de acceso.)*

### Paso 2 — Pregunta verificable

¿El sistema valida que las contraseñas nuevas cumplan con un mínimo de caracteres y con requisitos de complejidad, en el archivo `registro.php`?

### Paso 3 — Evidencia recolectada

**Resultado: el control NO se cumple.** Se recogieron tres evidencias.

**Evidencia 1 — Código fuente (`registro.php`, líneas 4 a 7).**

Entre el momento en que se recibe la clave y el momento en que se inserta en la base de datos no hay ninguna instrucción de validación. El flujo completo es recibir e insertar:

```php
$usuario = $_POST['usuario'];
$clave   = $_POST['clave'];
$sql = "INSERT INTO usuarios (usuario, clave) VALUES ('$usuario', '$clave')";
$conn->query($sql);
```

**Evidencia 2 — Formulario HTML (`registro.php`, línea 13).**

```html
Clave: <input type="password" name="clave"><br>
```

El campo no tiene los atributos `minlength`, `pattern` ni `required`, por lo que tampoco hay validación del lado del cliente. No existe control ni en el servidor ni en el navegador.

**Evidencia 3 — Prueba en la base de datos.**

En la tabla `usuarios` existe el registro `id = 3`, `usuario = 'prueba'`, `clave = '1234'`. Son 4 caracteres, únicamente dígitos, sin mayúsculas ni símbolos. El sistema lo aceptó y lo almacenó, lo que confirma de forma empírica que no existe ninguna política de contraseñas aplicada.

**Reproducción:** abrir `http://localhost/compraseguras/registro.php`, escribir cualquier nombre de usuario y `1` como clave, y enviar el formulario. El sistema responde "Usuario registrado con éxito" y el registro queda guardado en la tabla.

### Paso 4 — Registro exacto

*(Pendiente de diligenciar.)*

---

## Bibliografías

- https://www.php.net/manual/es/function.password-hash.php
- https://www.php.net/manual/es/function.preg-match.php
- https://www.php.net/manual/es/function.htmlspecialchars.php
- https://www.php.net/manual/es/mysqli.quickstart.prepared-statements.php
