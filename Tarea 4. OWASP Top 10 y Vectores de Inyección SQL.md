# Tarea 4. OWASP Top 10 y Vectores de Inyección SQL

**Asignatura:** Seguridad de Aplicaciones
**Programa:** Tecnología en Desarrollo de Software para Negocios Digitales
**Institución:** Universidad de Manizales
**Docente:** Sergio Pinilla Valencia
**Estudiante:** Pablo Nieto Pérez
**Aplicación auditada:** NegocioSeguro (Compra Segura), repositorio https://github.com/pabloWIB/CompraSegura, commit b24a5de
**Entorno:** XAMPP (Apache + PHP + MySQL), http://localhost/compraseguras/, phpMyAdmin
**Fecha:** 13 de octubre de 2026

## 1. Introducción

Para esta tarea volví a usar la aplicación NegocioSeguro (la misma Compra Segura que veníamos auditando en clases pasadas) y la revisé con el marco OWASP Top 10 del 2021, centrándome en la categoría de Inyección (A03), en concreto la inyección SQL.

Lo que hice fue montar el proyecto en mi XAMPP local y seguir la guía de la práctica. Primero reproduje tres ataques de inyección en mi propio equipo y después revisé el código de otros dos archivos para encontrar más puntos vulnerables. Todo esto es en un entorno de pruebas local, sobre una app que ya está hecha a propósito para ser insegura, así que no hay ningún riesgo real.

Las tres variantes que reproduje son:

- Inyección basada en errores, en buscar_producto.php.
- Inyección basada en UNION, también en buscar_producto.php.
- Inyección ciega booleana, en login.php.

Y los dos archivos que revisé por código son registro.php y pedido.php.

## 2. Qué es OWASP y el Top 10

OWASP (Open Worldwide Application Security Project) es una organización sin ánimo de lucro que publica material gratis sobre seguridad en el software. Lo más conocido que tienen es el OWASP Top 10, que es una lista de los diez riesgos más graves que se ven en las aplicaciones web. La actualizan cada cierto tiempo; la versión vigente es la del 2021.

Estos son los diez de la edición 2021:

| Código | Categoría | Qué es |
|---|---|---|
| A01 | Pérdida de control de acceso | El usuario llega a cosas que no le corresponden. |
| A02 | Fallos criptográficos | Datos sensibles sin cifrar o mal protegidos (claves en texto plano, por ejemplo). |
| A03 | Inyección | Datos del usuario se terminan ejecutando como código o consulta (SQLi, XSS, comandos). |
| A04 | Diseño inseguro | El fallo está en el diseño, no solo en el código; faltan controles desde el principio. |
| A05 | Configuración incorrecta | Permisos de más, errores visibles, valores por defecto inseguros. |
| A06 | Componentes vulnerables | Librerías o servidores viejos con fallos ya conocidos. |
| A07 | Fallos de identificación y autenticación | Login flojo, sin control de intentos, sesiones mal manejadas. |
| A08 | Fallos de integridad | Se confía en datos o código sin verificar que no los cambiaron. |
| A09 | Fallos de registro y monitoreo | No se guardan ni se detectan los ataques. |
| A10 | SSRF | Al servidor se le obliga a pedir recursos que no debería. |

Compra Segura cae en varias de estas, pero la que más pesa es la A03, Inyección, que es sobre la que trabaja esta tarea.

## 3. Los vectores de inyección SQL

La inyección SQL pasa cuando lo que escribe el usuario se pega directamente dentro de una consulta SQL sin separarlo de la consulta en sí. Entonces la base de datos termina leyendo parte de ese texto como si fueran órdenes y no como un simple dato.

Los tipos que existen son básicamente estos:

**En banda:** el resultado del ataque se ve en la misma respuesta de la página.
- Basada en error: se provoca un error de la base de datos para que el mensaje de error suelte información de adentro (versión, tablas, etc.).
- Basada en UNION: se pega un UNION SELECT para leer datos de otras tablas.
- Tautología: se mete una condición que siempre da verdadera, típico para saltarse un login.

**Ciega (blind):** no se ve el dato directamente, se va deduciendo por cómo reacciona la página.
- Booleana: la respuesta cambia según si la condición es verdadera o falsa.
- Por tiempo: se mide cuánto tarda en responder (con SLEEP y parecidos).

**Fuera de banda:** el dato se saca por otro canal (DNS o HTTP). Es raro y depende de cómo esté configurado el servidor.

Al final el origen siempre es el mismo: mezclar el código con los datos. Y la solución también es siempre la misma: usar consultas preparadas con parámetros, que separan la estructura de la consulta de los datos, más validar las entradas y no dar más permisos de los necesarios en la base de datos.

## 4. Las tres inyecciones que probé

### Fase 0: preparar el entorno

Para que las inyecciones basadas en error muestren el mensaje de la base de datos, toca activar que se vean los errores. En conexion.php, apenas abre el PHP, agregué estas dos líneas:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Aclaro que esto es solo para la práctica en local. En un sitio real mostrarle los errores al usuario ya es de por sí un fallo de seguridad (A05), porque le estás dando información de la aplicación. Lo correcto allá sería mandarlos a un archivo de log y no imprimirlos en pantalla.

### Inyección 1: basada en error (buscar_producto.php)

La consulta que tiene el problema es esta:

```php
$sql = "SELECT * FROM productos WHERE nombre LIKE '%$busqueda%'";
```

Lo que escribe el usuario en el buscador entra sin ningún filtro. Aprovechando eso forcé un error de una función de XML de MySQL para que el mensaje de error me devolviera un dato interno, en este caso la versión del motor.

- Campo: el buscador de productos.
- Lo que ingresé: `' AND extractvalue(1,concat(0x7e,(SELECT version()))) -- -`
- Qué pasó: la página no mostró productos, sino un error de MySQL (XPATH syntax error) con un texto parecido a `~8.0.XX - MySQL Community Server`. O sea, me dijo qué versión está corriendo.
- Impacto: Confidencialidad. Todavía no robé datos, pero ya tengo información del sistema que me sirve para armar ataques más dirigidos.

### Inyección 2: basada en UNION (buscar_producto.php)

Es la misma consulta de arriba. La tabla de productos devuelve 4 columnas, así que le pegué un UNION SELECT también de 4 columnas pero sobre la tabla de usuarios.

- Campo: el buscador de productos.
- Lo que ingresé: `inexistente' UNION SELECT id, usuario, clave, 'robado' FROM usuarios -- -`
- Qué pasó: como busqué un producto que no existe, la primera consulta no trae nada, pero el UNION engancha la segunda. En pantalla me salieron los usuarios con sus contraseñas (en texto plano) en el lugar donde normalmente irían el nombre y el precio del producto.
- Impacto: Confidencialidad. Esto ya es sacar credenciales directamente de otra tabla. Peor todavía porque las claves están sin cifrar (A02).

### Inyección 3: ciega booleana (login.php)

La consulta vulnerable del login es:

```php
$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$clave'";
```

Acá la página no muestra ni datos ni errores, pero sí cambia la respuesta según si lo que pregunto es verdadero o falso. La idea es ir adivinando la clave del usuario admin letra por letra.

- Campo: el de usuario en el login (la clave no importa porque la corto con el comentario `-- -`).
- Caso verdadero: `admin' AND SUBSTRING(clave,1,1)='a' -- -`
- Caso falso: `admin' AND SUBSTRING(clave,1,1)='z' -- -`
- Qué pasó: con la 'a' el sistema me dejó entrar y mostró "Bienvenido, admin. Acceso concedido", o sea la clave sí empieza por 'a'. Con la 'z' me dijo "Usuario o clave incorrectos". Nunca vi la clave, pero por la respuesta fui confirmando cada carácter. Repitiendo eso se saca la clave completa.
- Impacto: Confidencialidad. Saco las credenciales pero de forma silenciosa. También toca A07 porque es un fallo del login.

## 5. Revisión de código: los otros dos puntos

### registro.php, línea 6

```php
4   $usuario = $_POST['usuario'];
5   $clave   = $_POST['clave'];
6   $sql = "INSERT INTO usuarios (usuario, clave) VALUES ('$usuario', '$clave')";
```

El usuario y la clave llegan del formulario y se pegan tal cual dentro del INSERT (línea 6). Con solo meter una comilla simple en cualquiera de los dos campos ya se rompe la consulta, y con algo más elaborado se podría alterar lo que se guarda. Aparte, la clave queda guardada en texto plano (A02).

- Línea vulnerable: 6, en los campos usuario y clave.
- Impacto: Integridad. Se pueden dañar o modificar los datos de la tabla usuarios.

### pedido.php, línea 10

```php
4    $producto_id = $_POST['producto_id'];
5    $precio      = $_POST['precio'];
6    $cantidad    = $_POST['cantidad'];
7    $usuario_id  = $_SESSION['usuario_id'] ?? 0;
8    $total = $precio * $cantidad;
...
10   $sql = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, total) VALUES ('$usuario_id','$producto_id','$cantidad','$total')";
```

Igual que en registro, el producto_id y la cantidad entran sin filtrar al INSERT (línea 10). Pero este archivo además tiene dos problemas de diseño que una consulta preparada por sí sola no arregla:

1. El precio viene de un campo oculto del formulario (`<input type="hidden" name="precio" value="3000">`). El usuario puede cambiar ese valor y pagar lo que quiera (A04 y A08).
2. Con `$_SESSION['usuario_id'] ?? 0` se pueden registrar pedidos sin haber iniciado sesión (A01).

- Línea vulnerable: 10, en los campos producto_id y cantidad.
- Impacto: Integridad (se manipulan los datos del pedido) y además afecta al negocio porque se puede pagar menos de lo real.

## 6. Tabla del informe

| # | Archivo | Línea / Campo | Variante | Payload usado | Resultado observado | Impacto (principio) |
|---|---|---|---|---|---|---|
| 1 | buscar_producto.php | Campo de búsqueda | Basada en error | `' AND extractvalue(1,concat(0x7e,(SELECT version()))) -- -` | Error XPATH de MySQL que muestra la versión del motor (~8.0.XX) | Confidencialidad (reconocimiento) |
| 2 | buscar_producto.php | Campo de búsqueda | UNION | `inexistente' UNION SELECT id, usuario, clave, 'robado' FROM usuarios -- -` | Salen los usuarios y claves en texto plano en lugar de los productos | Confidencialidad (fuga de credenciales) |
| 3 | login.php | Campo usuario | Ciega booleana | `admin' AND SUBSTRING(clave,1,1)='a' -- -` (y con 'z' para el caso falso) | Con 'a' deja entrar, con 'z' no; así se saca la clave letra por letra | Confidencialidad (extracción silenciosa) |
| 4 | registro.php | Línea 6 (usuario/clave en el INSERT) | Identificada en código | No aplica (análisis de código) | El POST se concatena directo en el INSERT; se pueden alterar los datos | Integridad |
| 5 | pedido.php | Línea 10 (producto_id/cantidad en el INSERT) | Identificada en código | No aplica (análisis de código) | El POST entra directo al INSERT, más el precio puesto por el cliente y pedidos sin sesión | Integridad / negocio |

## 7. Cómo se corrige

La idea de fondo es no concatenar nunca lo que escribe el usuario dentro del SQL y usar consultas preparadas con parámetros (prepare y bind_param). Así quedarían los cuatro archivos:

registro.php, con consulta preparada y la clave cifrada:

```php
$hash = password_hash($_POST['clave'], PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave) VALUES (?, ?)");
$stmt->bind_param("ss", $_POST['usuario'], $hash);
$stmt->execute();
```

login.php, con consulta preparada y verificando el hash, sin comparar la clave dentro del SQL:

```php
$stmt = $conn->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_POST['usuario']);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
if ($fila && password_verify($_POST['clave'], $fila['clave'])) {
    $_SESSION['usuario_id'] = $fila['id'];
    $_SESSION['usuario']    = $fila['usuario'];
}
```

buscar_producto.php, poniendo los % dentro del parámetro y no en la cadena:

```php
$stmt = $conn->prepare("SELECT nombre, precio FROM productos WHERE nombre LIKE ?");
$like = '%' . $_GET['q'] . '%';
$stmt->bind_param("s", $like);
$stmt->execute();
```

pedido.php, leyendo el precio de la base de datos, con consulta preparada y exigiendo sesión:

```php
if (empty($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$stmt = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
$stmt->bind_param("i", $_POST['producto_id']);
$stmt->execute();
$precio = $stmt->get_result()->fetch_assoc()['precio'];   // sale de la BD, no del formulario

$cantidad = (int) $_POST['cantidad'];
$total    = $precio * $cantidad;

$ins = $conn->prepare("INSERT INTO pedidos (usuario_id, producto_id, cantidad, total) VALUES (?,?,?,?)");
$ins->bind_param("iiid", $_SESSION['usuario_id'], $_POST['producto_id'], $cantidad, $total);
$ins->execute();
```

Aparte de las consultas preparadas, hay otras cosas que conviene arreglar:

- Guardar las claves con password_hash y password_verify (eso cierra el A02).
- Crear un usuario de base de datos solo para esta app, con permisos únicamente sobre su base, en vez de conectarse como root (A05).
- No mostrarle el error de conexión al usuario, mandarlo a un log (A05 y A09).
- Usar htmlspecialchars al imprimir datos que escribió el usuario, para evitar XSS (A03).
- Validar bien las entradas, por ejemplo que la cantidad sea un entero positivo (A04).
- Agregar un token anti-CSRF en los formularios (A01).

## 8. Reflexión: cuál es el mayor riesgo para el negocio

Si uno mira solo cuántos datos saca de una, la inyección UNION (la fila 2) parece la peor, porque de un solo envío muestra todos los usuarios con sus contraseñas. Pero la guía pide comparar eso con el ruido que deja el ataque, y ahí yo cambio de opinión.

Para mí el mayor riesgo es la inyección ciega booleana del login (fila 3). Al final saca lo mismo que la UNION, que son las credenciales, pero sin dejar rastro a la vista. No genera errores en pantalla ni salidas raras, solo son intentos de login que, mirados uno por uno, parecen intentos normales de alguien que no se acuerda de la clave. Un atacante puede automatizar eso y sacar la clave carácter por carácter sin que nadie se dé cuenta. Y como Compra Segura no guarda registro de los intentos ni tiene monitoreo (A09), ese ataque podría estar corriendo días y la empresa ni enterarse. En cambio la UNION y la de error son escandalosas: cualquiera que mire la pantalla o los logs las ve enseguida.

O sea, la UNION hace más daño rápido pero es fácil de pillar, y la booleana hace el mismo daño pero calladita. Para el negocio eso último es más grave, porque el robo pasa sin que alcancen a reaccionar.

Al hacer la tarea me quedaron claras dos cosas. La primera es que todos estos fallos vienen del mismo error, que es pegar los datos del usuario dentro del SQL, y que con una sola práctica (las consultas preparadas) se tapan los cinco de una vez. La segunda es que la seguridad no es solo cosa de código: lo del precio en pedido.php es un error de diseño (A04) que ninguna consulta preparada arregla, y tener las claves en texto plano (A02) hace que una simple lectura de datos se convierta en un robo directo de contraseñas. Hacer esto en una app vulnerable y en local deja ver el daño real sin afectar a nadie, que es justo para lo que sirve el ejercicio.

## 9. Referencias

- OWASP Top 10 (2021): https://owasp.org/Top10/
- OWASP, SQL Injection Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- PHP, sentencias preparadas con MySQLi: https://www.php.net/manual/es/mysqli.quickstart.prepared-statements.php
- Guía de la práctica: "4 Guía del Estudiante - OWASP TOP 10 e Inyección" (material de clase, Classroom).
- Repositorio auditado: https://github.com/pabloWIB/CompraSegura (commit b24a5de), archivo HALLAZGOS.md.
