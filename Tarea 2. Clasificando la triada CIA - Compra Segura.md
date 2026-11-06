# Clasificando la tríada CIA — Compra Segura

**Pablo Nieto Pérez**

Seguridad de Aplicaciones

Tecnología en Desarrollo de Software para Negocios Digitales, Universidad de Manizales

02 de Septiembre de 2026

---

## Introducción

El propósito de este trabajo es aplicar los principios de la tríada CIA sobre nuestra aplicación Compra Segura, tomando como base el trabajo #1 ya entregado.

---

## Registro de hallazgos

Los hallazgos principales de los fallos en el sistema vienen principalmente desde la base de datos, esto después de hacer QA lo cual es aseguramiento de calidad del sistema, que es de mi punto de vista el tema más crítico del sistema viene desde la creación de la base de datos, ya que no cuenta con estándares de seguridad básicos, tampoco maneja cultura devops, como por ejemplo una base de datos que declare sus relaciones y sus restricciones y difiere problemas que desde el principio se pueden resolver desde base de datos para que se tengan que hacer desde un back end adicional. Algunos de los puntos más críticos del sistema, considero que son:

### Archivo revisado: `compraseguras.sql`

**¿Qué observamos?**

Empiezo por acá porque para mí el problema empieza desde la base de datos. Lo primero que salta es el nombramiento, que se llama `compraseguras`, y no `compra_segura` ni `compraSegura`, pero podemos omitir esta parte ya que el nombramiento de cada base de datos dependía de cada uno. Como punto más crítico es que las contraseñas se guardan como texto plano:

```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50),
    clave VARCHAR(50)
);

INSERT INTO usuarios (usuario, clave) VALUES
('admin', 'admin123'),
('jperez', 'miclave2024');
```

Cualquiera que abra el archivo o entre a phpMyAdmin ve las claves de todos los usuarios.

**¿Por qué creemos que es un riesgo?**

Porque cualquiera que acceda a la base de datos se lleva las contraseñas de todos los usuarios, y como la columna es `clave VARCHAR(50)`, entonces tampoco se puede arreglar sin modificar la tabla, como un hash que son 60 caracteres, así que MySQL lo cortaría y nadie podría entrar.

**Nivel de preocupación: Alto**

---

**¿Qué observamos?**

Que la tabla `usuarios` no tiene ninguna columna de roles, solo `id`, `usuario` y `clave`, y no existe un `es_admin` ni nada parecido, entonces "admin" es únicamente el nombre de un usuario, no un rol de verdad.

**¿Por qué creemos que es un riesgo?**

Porque si no hay roles el sistema no tiene forma de distinguir entre un cliente y un administrador, y cualquier cuenta registrada tiene exactamente los mismos permisos. Y como tampoco hay llaves foráneas, la base acepta pedidos con `usuario_id = 0` o con productos que no existen, entonces quedan registros huérfanos que no se pueden rastrear.

**Nivel de preocupación: Alto**

> **¿Qué es un hash?** Es el resultado de pasar un texto por una función matemática que lo convierte en una cadena de longitud fija.

### Archivo revisado: `login.php`

**¿Qué observamos?** Que lo que el usuario escribe se mete directo dentro de la consulta SQL, sin revisarse antes:

```php
<?php
include 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];
    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$clave'";
    $resultado = $conn->query($sql);
    if ($resultado && $resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $_SESSION['usuario_id'] = $fila['id'];
        $_SESSION['usuario'] = $fila['usuario'];
        echo "Bienvenido, " . $fila['usuario'] . ". Acceso concedido.";
    } else {
        echo "Usuario o clave incorrectos.";
    }
}
?>
```

Ahí `$usuario` y `$clave` van pegados dentro del texto de la consulta, o sea que lo que escriba el usuario se vuelve parte de la instrucción, porque no hay consultas preparadas en ningún lado.

Si por ejemplo en el login escribimos `' OR '1'='1` en el campo usuario y cualquier cosa en la clave, el sistema responde con true, entonces da acceso, sin saber ninguna contraseña.

**¿Por qué creemos que es un riesgo?**

Creemos que es un riesgo ya que cualquiera con conocimientos básicos de base de datos, o una IA a la mano junto con malas intenciones, puede ingresar al sistema sin tener una cuenta y no necesita adivinar la contraseña o robársela, solo escribe un texto en el formulario y ya está adentro con la sesión de admin, y para el sistema eso fue un login normal.

**Nivel de preocupación: Alto**

### Archivo revisado: `pedido.php`

**¿Qué observamos?**

Que el precio del producto viaja desde el navegador, en un campo oculto del formulario, y el PHP lo recibe y lo usa tal cual sin verificar nada:

```php
<?php
include 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = $_POST['producto_id'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $total = $precio * $cantidad;
    $sql = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, total)
            VALUES ('$usuario_id','$producto_id','$cantidad','$total')";
    $conn->query($sql);
    echo "Pedido registrado. Total a pagar: $" . $total;
}
?>
<form method="POST">
    <input type="hidden" name="producto_id" value="1">
    <input type="hidden" name="precio" value="3000">
    Cantidad: <input type="number" name="cantidad" value="1">
    <button type="submit">Confirmar pedido</button>
</form>
```

O sea que el sistema le está preguntando al cliente cuánto vale el producto, y con las herramientas de desarrollador del navegador uno cambia ese `3000` por un `1` y envía el formulario, entonces el pedido queda guardado con ese total. Lo mismo con cualquier valor, como si el cliente quiere poner `0` o `-1000`.

**¿Por qué creemos que es un riesgo?**

Porque el cliente decide lo que paga, el precio real está en la tabla `productos`, pero el código nunca lo consulta, entonces no hay nada con qué comparar y el total falso queda guardado en la base como si fuera de verdad. Como tampoco hay control de sesiones y vimos que cualquiera puede ingresar, esos pedidos se pueden hacer sin haber iniciado sesión y quedan a nombre del usuario 0 que no existe.

**Nivel de preocupación: Alto**

### Hallazgo revisado: cookie de sesión `PHPSESSID`

**¿Qué observamos?**

Cuando uno inicia sesión y abre las herramientas de desarrollador (F12 → pestaña Application → Cookies), se ve que la sesión se guarda en una cookie llamada `PHPSESSID`, y esa cookie viaja sin las banderas `HttpOnly` ni `Secure`, o sea sin ninguna protección.

**¿Por qué creemos que es un riesgo?**

Porque si la cookie no tiene `HttpOnly` cualquier script en la página la puede leer, y si no tiene `Secure` puede viajar por una conexión sin cifrar. Entonces un atacante que logre leer o robar ese `PHPSESSID` puede secuestrar la sesión y hacerse pasar por el usuario sin necesidad de saber su contraseña, y para el sistema sería el usuario de verdad porque la sesión es válida.

**Nivel de preocupación: Alto**

---

## Reto

**¿Funcionó el bypass?** Sí, escribimos `' OR '1'='1` en usuario y cualquier texto en clave, y el sistema respondió "Bienvenido, admin. Acceso concedido" y nunca usamos una contraseña real.

**¿Qué pudiste ver una vez dentro?** El sistema nos identificó como admin y guardó su id y su nombre en la sesión. Desde ahí cualquier función, como registrar un pedido, queda asociada a esa cuenta aunque nunca nos autenticamos.

**¿Por qué ocurre en el código y no en la base de datos?** Porque la base de datos hace lo que le pidieron, que es recibir una consulta ya armada y ejecutarla, y el error está en `login.php` que arma esa consulta pegando texto del usuario dentro del SQL en vez de mandar el dato aparte.

---

## Tabla de clasificación CIA

| Archivo / Hallazgo | ¿Qué observamos? | C | I | A | Justificación |
|---|---|:-:|:-:|:-:|---|
| `compraseguras.sql` — claves en texto plano | Las contraseñas se guardan sin cifrar | **Sí** | No | No | **C:** cualquiera con acceso a la base de datos puede leer `admin123` y `miclave2024` directo del `INSERT` → se expone un secreto. **I:** no se altera ningún dato. **A:** no se cae el servicio. |
| `compraseguras.sql` — sin roles ni llaves foráneas | No hay columna de rol ni `FOREIGN KEY` entre `pedidos`, `usuarios` y `productos` | No | **Sí** | No | **I:** sin llaves foráneas la base acepta `usuario_id = 0` y productos que no existen → registros huérfanos y datos no confiables. **C / A:** no se expone ningún secreto ni se cae el servicio. *(La falta de roles es un tema de autorización que se clasifica como Autenticidad en la Clase 3, no dentro de CIA.)* |
| `login.php` — inyección SQL (bypass) | El input se mete directo en la consulta SQL, sin consultas preparadas | **Sí** | **Sí** | **Sí** | **C:** con `' OR '1'='1` se entra sin conocer ninguna clave → acceso no autorizado a la información y a la sesión de admin. **I:** la misma inyección permite meter comandos como `UPDATE` o `DELETE` y modificar datos que no se deberían tocar. **A:** la misma inyección permite borrar tablas (`DROP TABLE`) y dejar el sistema sin datos y sin funcionar. |
| `pedido.php` — manipulación del precio | El precio viaja en un campo oculto y el PHP lo usa tal cual sin verificar nada | No | **Sí** | No | **C:** no se expone ningún secreto. **I:** cambié el `value="3000"` a `-10` desde F12 y se guardó un total falso en la tabla `pedidos`. El precio real está en la tabla `productos`, pero el código nunca lo consulta para comparar. **A:** no se cae el servicio. |
| Cookie `PHPSESSID` sin protección | El identificador de sesión viaja sin las banderas `HttpOnly` ni `Secure` | **Sí** | No | No | **C:** si un atacante lee o roba la cookie (visible en F12 → panel de Cookies) puede secuestrar la sesión y hacerse pasar por el usuario sin conocer su clave. **I:** por sí mismo no altera ningún dato. **A:** no se cae el servicio. |

---

## Respuestas a las 2 preguntas de discusión

**1. ¿Algún hallazgo rompe más de un principio?**

Sí, por ejemplo `login.php` es uno que rompe más de un principio, y es el de la inyección SQL. Ese rompe los tres a la vez: la confidencialidad porque con `' OR '1'='1` uno entra sin saber ninguna clave y queda con acceso a la información y a la sesión de admin; también la integridad porque esa misma inyección deja meter comandos para actualizar o borrar datos que uno no debería tocar; y también la disponibilidad porque con esa misma falla se pueden borrar tablas completas y dejar el sistema sin datos y sin funcionar. Yo por eso considero que es el más peligroso, porque con una sola falla se abre la puerta para todo.

**2. Si solo pudieras corregir uno esta semana, ¿cuál?**

Yo arreglaría primero el de `login.php`, porque es el único que rompe los tres principios a la vez y el más fácil de explotar: cualquiera escribe `' OR '1'='1` y ya está adentro sin saber ninguna contraseña. Con consultas preparadas se cierra de una la puerta más grande del sistema. Además es lo más grave para el negocio, porque por ahí se roba la sesión de admin, se alteran o borran datos y se registran pedidos falsos, entonces ese es el que más protege el negocio.

---

## Desafío Avanzado

**La cadena rota: cómo un fallo de confidencialidad (contraseñas en texto plano) puede terminar en una caída de disponibilidad.**

**Paso 1 (Confidencialidad):** Todo empieza con las contraseñas en texto plano de `compraseguras.sql`. Un atacante llega a la base de datos, ya sea con la inyección SQL de `login.php` o abriendo el archivo o phpMyAdmin, y como las claves están guardadas sin cifrar (`clave VARCHAR(50)`) lee de una `admin` / `admin123` sin tener que descifrar nada, o sea que se lleva la contraseña real del administrador.

**Paso 2 (Autenticidad):** Con esa clave real ya ni necesita hacer trampa, entra al sistema como el admin de verdad y el sistema ni cuenta se da, porque no hay roles ni doble verificación, solo guarda el id y el nombre en la sesión, entonces para el sistema es un login normal y le cree que es el administrador.

**Paso 3 (Integridad):** Ya adentro como admin el atacante empieza a dañar los datos, cambia precios, modifica o borra pedidos y hasta le cambia la clave al otro usuario, entonces la información deja de ser confiable porque cualquiera pudo haberla alterado.

**Paso 4 (Disponibilidad):** Y el golpe final, como tiene control total corre un `DROP TABLE` y borra las tablas de usuarios, pedidos y productos, o le cambia la clave al admin y deja a todos por fuera, entonces ya nadie puede entrar ni usar Compra Segura, y como no había copias de seguridad el sistema se cae completo. Así un problema que empezó siendo solo de confidencialidad terminó rompiendo la disponibilidad de todo el sistema.

---

## Bibliografías

- https://www.php.net/manual/es/mysqli.quickstart.prepared-statements.php
- https://www.php.net/manual/es/function.password-hash.php
- https://stackoverflow.com/questions/1370951/what-is-phpsessid
