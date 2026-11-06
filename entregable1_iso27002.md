ENTREGABLE 1


Pablo Nieto


Tecnología en Desarrollo de Software para Negocios Digitales, Universidad de Manizales


10 de Septiembre de 2026


---


## Introducción

El propósito de este informe es dar continuidad a la auditoría de la clase 2 que fue en donde identificamos y clasificamos hallazgos de seguridad sobre la aplicación compra segura, en esta tercera actividad ya no se trata de encontrar fallas sino de medir el nivel de cumplimiento de la app frente a cinco dominios de control del estándar ISO y IEC 27002 y vamos aportando para cada uno evidencia concreta y que sea verificable en el código fuente o en la base de datos


## Objetivo

El objetivos es documentar y justificar un hallazgo específico sobre el principio de autenticidad en el formulario de inicio de sesión y responder a las dos preguntas de la discusión final y emitir una respuesta a la objeción planteada


## Revisión de pasos

### Instrucción exacta por dominio


---


## A.9 Control de acceso

Abran registro.php y login.php. ¿Hay algún requisito mínimo de contraseña al registrarse? ¿Todos los usuarios tienen el mismo nivel de acceso, o existen roles diferenciados?


**1.** Para cumplir con el requisito de exigencia de la complejidad y longitud mínima en la creación de credenciales del sistema, primero nos preguntamos si hay algún requisito mínimo de contraseña al registrarse, y la respuesta es que no, primero porque el sistema no tiene redireccionamiento, al lanzarse la app http://localhost/compraseguras/ 1. No se redirige a index.php ni tiene htaccess 2. No existe forma que el usuario llegue a registro.php a no ser que sea escribiendo la url 3. Si el usuario llega a registro.php no tiene caracteres mínimos para la creación de usuarios

**(No cumple)**


**2.** El sistema valida que las contraseñas nuevas cumplan con un mínimo de caracteres y su complejidad en el archivo registro.php? No lo hace, en ninguna parte de las 15 líneas no existe ninguna instrucción de validación, ni por medio de funciones (js) porque no hay js, ni por php y tampoco por html, este lo probamos con registro con usuario test y clave 1 y nos devuelve "Usuario registrado con éxito.", y fila creada en la tabla

**(No cumple)**


**3.** Existen roles o niveles de acceso distintos? No, la tabla de usuarios tiene solo tres columnas que son id, usuario y clave, no existe columna rol ni perfil o nivel y así todos los usuarios comparten el mismo nivel de acceso

**(No cumple)**


**4.** Cada archivo verifica que exista sesión iniciada? (Después de preguntarle a Claude como funcionan las peticiones y que auditara el login.php, mi respuesta) No, no se verifica, en si ahora el sistema de auth es solo decorativo ya que los archivos no verifican sin un usuario ya pasó por el login para permitirle hacer peticiones, entonces cualquiera puede saltárselos entrando directo a los archivos que hacen el trabajo real, la lógica de un sistema debería ser que si no hay sesión, entonces cortar la ejecución ahí mismo y mandar al usuario a login, pero ahora no hay nada que lo verifique

**(No cumple)**


**5.** La aplicación se conecta a la BD con una cuenta de mínimo privilegio? La aplicación usa la cuenta root del gestor base, como si en ningún momento se hubiera hecho ningúna configuración, también nos damos cuenta porque no hay ninguna llave guarda .env y todo está guardado en los archivos sql, los cuales no están ignorado por lo cual quien tenga acceso a repo, directamente también tiene acceso a la base de datos

**(No cumple)**


---


## A.10 Criptografía

Revisen la tabla usuarios en phpMyAdmin, columna clave. Acá el objetivo es asegurar el uso apropiado de la criptografía para proteger la confidencialidad, la autenticidad e integridad de la información


**1.** La columna clave de la tabla usuarios almacena hashes? En este punto auditamos phpMyAdmin para revisar como están las tablas, por medio de una select revisamos la tabla de usuarios y podemos ver que para cada usuario se guarda, id, nombre y clave (Sin cifrar) por ejemplo "1 | admin | admin123 | 8"

**(No cumple)**


**2.** El esquema soportaría un hash moderno? No lo soporta ya que el sistema nunca fue diseñado para esto, primero no tiene planeación y aunque se hagan medidas preventivas en el sistema, no es posible ya que un hash ocupa 60 caracteres

**(No cumple)**


**3.** Se invoca alguna función de hash o cifrado? Par esta parte revisamoc los archivos php y encontramos 0 resultados, donde en ninguna parte del código se ve alguna función hash o verify, y aunque esté mal nombrada, tampoco hay ningún patrón de función que cumpla con cifrados

**(No cumple)**


**4.** El envío de credenciales viaja cifrado? No, y en este caso mucho menos, ya teniendo en cuenta como se ha fundamentado el sistema esta parte no tiene como existir sin bases primarias, pero de igual forma revisamos cómo se hacen las peticiones y vemos que se publica por POST sobre http://localhost/ sin ninguna configuración de HTTPS, las credenciales viajan directo y son legibles

**(No cumple)**


**5.** Los secretos están fuera del código fuente? La contraseña del gestor de base de datos está escrita literalmente en conexion.php y publicada en el repo, tampoco o existe archivo .env ni variables de entorno, ni .gitignore

**(No cumple)**


---


## A.12 Seguridad de las operaciones


**1.** Algún archivo registra quién inició sesión? Revisamos en login.php y podemos ver que el sistema únicamente asigna variables de sesión e imprime "Bienvenido" pero no escribe en ningún archivo ni tabla

**(No cumple)**


**2.** Se registran los intentos fallidos? Para este punto revisamos en login.php y vemos que no hay ningún contador ni marca de tiempo o bloqueo por intentos ni registro de la IP de origen

**(No cumple)**


**3.** Se usa alguna función de escritura de log? Buscamos en todos los php las funciones con las que normalmente se escribe un log que son error_log, fopen y logger y salieron 0 resultados, tampoco hay una carpeta logs ni ningún archivo .log en el repositorio, no existe

**(No cumple)**


**4.** Se audita la creación de usuarios o de pedidos? No, registro.php mete el usuario nuevo y no deja constancia de quién lo creó, ni a qué hora, ni desde dónde, lo único que tiene fecha en todo el sistema es la columna fecha de la tabla pedidos que se llena sola con CURRENT_TIMESTAMP

**(No cumple)**


**5.** Los registros están protegidos contra alteración? Esta podemos decir que no alcanza ni a evaluarse porque no hay registros que proteger y de todas formas como la app se conecta con root, si alguien logra una inyección puede borrar o cambiar cualquier registro que lleguemos a tener más adelante

**(No cumple)**


**6.** Los errores se registran en lugar de mostrarse al usuario? Esto incluso es al revés porque en conexion.php si la conexión falla se hace un die imprimiendo el error que devuelve el gestor y eso sale directo en el navegador del que esté visitando la página. ahí se alcanza a ver información del servidor y de la base que nadie de afuera tendría por qué estar viendo

**(No cumple)**


---


## A.14 Adquisición, desarrollo y mantenimiento de sistemas


**1.** El código valida lo que el usuario envía antes de usarlo? No porque pedido.php agarra producto id, precio y cantidad del POST y los usa de una directamente sin mirar si son números ni si están en un rango que tenga sentido ni si son negativos, buscar_producto.php agarra lo que venga por GET. pero buscamos en todo el proyecto las funciones con las que normalmente se valida, filte y match y salieron 0 resultados, esto lo probamos mandando cantidad -5 y el total sale en negativo y el pedido igual se guarda

**(No cumple)**


**2.** Las consultas separan código de datos? No, las cuatro consultas que tiene el sistema arman el sql pegando directamente lo que escribe el usuario. está en el login, en el registro, en la búsqueda y en el pedido y en los cuatro es lo mismo, aquí para comprobarlo mandamos una comilla sola en la búsqueda, buscar_producto.php?q=' y el gestor se rompe y tira el error, confirmamos que el parámetro sí se puede inyectar

**(No cumple)**


**3.** El precio de la transacción se determina en el servidor? No y esta nos pareció de las más graves, en pedido.php el precio va en un input hidden con el valor 3000 y el servidor lo recibe y lo multiplica por la cantidad sin ir nunca a la tabla productos a ver cuánto vale ese producto de verdad, le cambiamos el hidden a 1 desde el navegador y el sistema respondió "Pedido registrado. Total a pagar: $1" por el mismo producto de 3000 (Supuestamente)

**(No cumple)**


**4.** Se escapa lo que se imprime al navegador? No, porque login.php imprime el nombre del usuario directo en el mensaje de bienvenida y buscar_producto.php imprime el nombre del producto igual y como en el registro tampoco se filtra lo que entra, entonces alguien puede registrarse con un nombre que tenga etiquetas html o js y eso queda guardado en la base y se ejecuta cada vez que se muestra

**(No cumple)**


**5.** Los formularios incluyen token de un solo uso? Ninguno de los cuatro formularios lo tiene ni el login ni el registro ni el de pedido ni el de búsqueda y tampoco hay en el código nada que genere o verifique un token o sea ese mecanismo no existe

**(No cumple)**


**6.** La base de datos impone restricciones? No, la tabla pedidos declara usuario id y producto id como INT normales, sin FOREIGN KEY y sin NOT NULL, entonces así se pueden guardar pedidos que apuntan a usuarios y productos que no existen

**(No cumple)**


---


## A.16 Gestión de incidentes de seguridad de la información


**1.** Existe un procedimiento escrito de respuesta a incidentes? No hay nada, no existe un SECURITY.md, ni una política ni un plan escrito en ninguna parte del repo

**(No cumple)**


**2.** Si un cliente reportara hoy un problema, ¿por dónde lo haría? No hay forma de hacerlo, la aplicación no publica correo de contacto, formulario de reporte ni aviso legal

**(No cumple)**


**3.** Existe un mecanismo para registrar debilidades detectadas? No desde un comienzo, pero teniendo en cuenta los trabajos, creé docs/ con archivos md HALLAZGOS y otros para ir documentando el proyecto y sus problemas

**(Cumple parcial)**


**4.** Los eventos se clasifican por severidad? El archivo HALLAZGOS.md tiene una columna de gravedad con escala que está aplicada a los hallazgos pero la escala no está definida formalmente y nunca staging y menos prod

**(Cumple parcial)**


**5.** Se puede investigar un incidente? Al no existir ningún registro de eventos queda imposible recolectar evidencia, no se podría determinar si ocurrió ni cuándo ni qué se accedió

**(No cumple)**


**6.** Los hallazgos alimentan mejoras? Si pero en parte, teniendo en cuenta que en sistema no tiene planeación, lo que se ha documentado es solo informativo pero no se ha puesto en práctica donde se van priorizando las correcciones, es un ejercicio real de lecciones que vamos aprendiendo pero no se ha traducido en ninguna corrección en el código

**(Cumple parcial)**


---


## Documentación y justificación del hallazgo de autenticidad


El formulario de acceso de login.php recibe usuario y clave por POST y los mete tal cual, sin ningún tratamiento, dentro de la cadena sql


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


Prueba ejecutada (bypass de autenticación), en el campo usuario y en el campo clave escribimos ' OR ''=' y le dimos ingresar, la consulta que le llega al gestor queda así


```sql
SELECT * FROM usuarios WHERE usuario = '' OR ''='' AND clave = '' OR ''=''
```


La condición ''='' siempre es verdadera entonces el where deja de filtrar y la consulta devuelve todas las filas de la tabla usuarios, y como el código solo pregunta si num_rows es mayor que 0 y después agarra la primera fila con fetch_assoc, el sistema hace tres cosas


1. Concede el acceso sin conocer ninguna contraseña

2. Asigna a la sesión la identidad de la primera fila de la tabla, que según compraseguras.sql es admin

3. Responde "Bienvenido, admin. Acceso concedido."


Resultado: cualquier persona en Internet puede iniciar sesión como admin sin credenciales


Junto con esto encontramos otra cosa que va por el mismo lado y es que la columna usuario de la tabla usuarios no tiene UNIQUE y registro.php tampoco revisa si el nombre ya existe, entonces alguien se puede registrar tranquilamente con el nombre admin y la clave que él quiera, y para el sistema y para cualquier otro usuario las dos cuentas se ven iguales porque lo único que se muestra en pantalla es el nombre y el nombre ya no identifica a nadie


### Por qué esto compromete la autenticidad


La autenticidad es que alguien sea quien dice ser, o sea que la identidad que reclama la persona haya sido verificada de verdad, y la confidencialidad es otra cosa distinta que es que la información solo la pueda ver quien está autorizado a verla. Nosotros clasificamos este hallazgo como de autenticidad y no de confidencialidad por estas razones


**1.** El sistema dejó de verificar identidad y lo que verifica ahora es existencia, el if no está preguntando si la persona es quien dice ser sino si la consulta devolvió al menos una fila, y como la consulta se puede manipular desde afuera el atacante no rompe el secreto de la contraseña sino que elimina la necesidad de tener contraseña, en ningún momento llega a saber que la clave era admin123, simplemente hace que la pregunta de verificación deje de importar, o sea que el mecanismo de prueba de identidad no fue descifrado sino anulado


**2.** La identidad que afirma el sistema después del bypass es falsa, las variables de sesión quedan con los datos de admin y de ahí en adelante todo lo que haga el atacante va a quedar atribuido a admin, entonces el sistema funcionando bien según su propia lógica está afirmando algo falso sobre quién está actuando, y eso además se lleva por delante el no repudio porque el admin de verdad no podría negar unas acciones que el sistema le puso a su nombre


**3.** Lo del nombre duplicado traslada la falla al usuario final, si pueden existir dos cuentas admin entonces el nombre que se ve en pantalla dejó de identificar a alguien, y un cliente que hable con el admin falso no tiene con qué distinguirlo del verdadero, el sistema le está mostrando una identidad que no es auténtica como si lo fuera


La diferencia con la confidencialidad se ve mejor sobre este mismo caso, si el atacante hubiera entrado a la base de datos y hubiera leído admin / admin123 en texto plano, que es lo que ya reportamos en criptografía, eso sí habría sido una falla de confidencialidad porque obtuvo información secreta que no debía conocer, acá pasa lo contrario, el secreto sigue guardado y aún así el acceso se produce. Resumiéndolo como lo entendimos nosotros, confidencialidad rota es "supe tu contraseña" y autenticidad rota es "no necesité tu contraseña y el sistema cree que soy tú"


Las dos cosas están mal en compra segura pero por causas distintas, la confidencialidad por guardar las claves en texto plano y la autenticidad por no tener una verificación de identidad confiable, y la prueba de que son separadas es que arreglar el almacenamiento de las contraseñas no repara el bypass, y arreglar el bypass no repara que las contraseñas estén expuestas


### Como se corrige


```php
// 1) consulta preparada, los datos ya no se interpretan como código sql
$stmt = $conn->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();

// 2) acá sí se verifica la identidad contra el hash
if ($fila && password_verify($clave, $fila['clave'])) {
    session_regenerate_id(true);                 // 3) para que no quede fijada la sesión
    $_SESSION['usuario_id'] = $fila['id'];
    echo "Bienvenido, " . htmlspecialchars($fila['usuario']);
} else {
    error_log("Login fallido: usuario={$usuario} ip={$_SERVER['REMOTE_ADDR']}"); // 4) la traza que pedía A.12
    echo "Usuario o clave incorrectos.";         // 5) mensaje genérico, no decir cuál de los dos falló
}
```


Y en la base de datos hay que acompañarlo con ALTER TABLE usuarios MODIFY clave VARCHAR(255) NOT NULL, ADD UNIQUE KEY uk_usuario (usuario); lo primero para que quepa el hash de 60 caracteres y lo segundo para que no puedan volver a existir dos cuentas con el mismo nombre


---


## Discusión final


### 1. Si tuvieran que corregir un solo dominio primero, cuál sería y por qué


Nosotros corregiríamos primero adquisición, desarrollo y mantenimiento de sistemas, y dentro de ese dominio puntualmente lo de separar código de datos en las cuatro consultas


El criterio que usamos no fue cuál dominio tiene más hallazgos porque en eso todos empatan en no cumple, sino cuál mezcla de gravedad, de qué tan fácil es explotarlo y de cuánto trabajo cuesta arreglarlo da el mejor resultado, y ahí gana este dominio por tres cosas


La primera es que es el único cuyos hallazgos se pueden explotar desde afuera sin tener nada, ni cuenta ni sesión ni acceso a la red, el bypass del login se hace escribiendo una cadena en un campo de un formulario que está público. Los otros hallazgos son graves pero necesitan una condición antes, por ejemplo para aprovechar que las claves están en texto plano primero hay que llegar a la base de datos, y la forma más fácil de llegar a la base de datos es justamente la inyección


La segunda es que es la corrección que más cubre con menos trabajo, pasar las cuatro consultas a sentencias preparadas es un cambio acotado y cierra de una sola vez el bypass del login, la inyección de buscar_producto.php, la del registro y la de pedido.php, o sea un solo tipo de cambio cierra cuatro puertas


Y la tercera, que es la que más nos pesó, es que mientras la inyección siga ahí los otros controles no se sostienen aunque los implementemos, si mañana hacemos los logs de operaciones pero la app sigue conectándose con root y sigue siendo inyectable, el atacante puede borrar los mismos logs y el control queda existiendo pero sin servir, y si implementamos roles pero el atacante entra directo como admin saltándose el login, el control de roles tampoco sirve. Por eso este dominio va de primero, porque es la base sobre la que los otros se vuelven creíbles


El orden que proponemos después es control de acceso para cerrar la verificación de sesión en cada archivo y meter roles, después criptografía para hashear las claves y sacar los secretos del código, después seguridad de las operaciones para tener trazas, y de último gestión de incidentes que es el más de proceso que de código


### 2. Qué significan los hallazgos que quedaron en cumplimiento parcial


    Los tres cumple parcial nos quedaron todos en gestión de incidentes, en el mecanismo para registrar debilidades, en la clasificación por severidad y en que los hallazgos alimenten mejoras, y quedaron ahí por una razón concreta y es que esas tres cosas sí están pasando pero no las está haciendo el sistema, las estamos haciendo nosotros como ejercicio de clase


    O sea, sí hay un registro de debilidades pero es la carpeta docs con el HALLAZGOS.md que creamos nosotros, sí hay una clasificación por severidad pero es una columna de gravedad que llenamos a mano y cuya escala nunca quedó definida formalmente, y sí hay hallazgos que van a alimentar mejoras pero hasta ahora es informativo porque no se ha traducido en una sola corrección dentro del código. Nada de eso vive dentro de la aplicación ni existiría si nosotros no estuviéramos haciendo la auditoría


    Eso es lo que significa un cumplimiento parcial y por eso nos parece hasta más peligroso que un no cumple limpio, porque el no cumple es honesto, dice que ahí no hay nada y nadie se confía, en cambio el cumple parcial se puede leer como que el control ya está y que solo falta pulirlo, cuando lo que hay en realidad es una actividad informal, que no está escrita, que no es repetible, que depende de que una persona se acuerde de hacerla y que no tiene un responsable asignado. Si mañana el proyecto cambia de manos el no cumple sigue siendo igual de falso que ayer, pero el cumple parcial se desaparece sin que nadie se dé cuenta porque lo que lo sostenía era una persona y no un procedimiento


    Lo que sacamos de ahí es que un cumple parcial no es medio control, es un control que todavía no existe pero que por ahora alguien está supliendo a mano, y para volverlo un cumple toca escribir el procedimiento, decir quién responde, decir en cuánto tiempo se responde y dejar el registro dentro del sistema y no dentro de un trabajo de clase


---


## Reto de profundización


### El reto del auditor externo


La objeción es que un auditor externo revisa nuestro informe y dice que los cinco dominios que evaluamos no son independientes, que en el fondo todos los hallazgos se reducen a un solo defecto que es que la aplicación no separa código de datos, y que entonces estaríamos contando cinco veces el mismo problema para inflar la auditoría, y que bastaría con reportar el hallazgo de las consultas porque los otros cuatro dominios se corregirían solos


Nuestra respuesta es que la objeción está mezclando dos cosas distintas, que es tener una causa común con ser el mismo control. La primera parte se la aceptamos, sí hay una causa común detrás de todo y es que este sistema nunca tuvo un proceso de desarrollo con seguridad en mente, pero de ahí no se sigue que los controles sobren, y lo mostramos con la prueba que corresponde en una auditoría que es mirar qué pasa si se corrige, si arreglo un dominio y el otro se queda igual de incumplido entonces los dominios son independientes


**1.** Corregir las consultas no arregla los otros cuatro. Supongamos que le hacemos caso al auditor y pasamos las cuatro consultas a sentencias preparadas, que es lo que él dice que resuelve todo, al día siguiente el sistema queda así, las claves siguen guardadas en texto plano y las credenciales siguen viajando por http sin cifrar entonces criptografía sigue en no cumple, no se escribió una sola línea de log ni existe la carpeta para hacerlo entonces seguridad de las operaciones sigue en no cumple, la tabla usuarios sigue sin columna de rol y los archivos internos siguen sin verificar que haya sesión iniciada así que alguien puede entrar directo a pedido.php sin pasar por el login y control de acceso sigue en no cumple, y sigue sin existir un procedimiento escrito de respuesta a incidentes ni un canal para reportar entonces gestión de incidentes queda igual. Parametrizar consultas no hashea contraseñas, no genera trazas, no crea roles y no redacta procedimientos, cuatro dominios quedaron intactos


**2.** Al revés tampoco funciona. Si en vez de eso hacemos lo de criptografía y hasheamos todas las claves con password_hash, el bypass de ' OR ''=' sigue funcionando exactamente igual, porque el atacante nunca necesitó la contraseña, o sea que un control de criptografía bien hecho puede convivir con una autenticidad completamente rota, que es lo mismo que documentamos en el hallazgo de autenticidad de este informe, y si los dominios fueran el mismo eso no podría pasar


**3.** Hay hallazgos que ni siquiera son inyección. El del precio que viaja en el input hidden es el mejor ejemplo, le cambiamos el hidden a 1 desde el navegador y el sistema guardó el pedido por un peso, y ahí no hubo ninguna comilla ni ninguna consulta mal armada, el atacante mandó un dato perfectamente válido que es un número entero y el servidor lo aceptó como verdad en lugar de ir a mirar el precio real en la tabla productos. Con sentencias preparadas ese fallo sigue ahí igualito porque es un problema de confiar en el cliente y de lógica de negocio, no de sintaxis sql, o sea que ni siquiera dentro de un mismo dominio los hallazgos se reducen a lo que dice el auditor


**4.** Un dominio puede incumplir sin que exista una sola vulnerabilidad explotable, y esto creemos que es lo que se le escapa a la objeción. La norma no es una lista de vulnerabilidades sino una lista de controles, gestión de incidentes está incumplido porque no hay un procedimiento escrito ni un correo de contacto ni un formulario de reporte, y eso seguiría incumplido aunque el código estuviera perfecto y no tuviera un solo error de programación, porque lo que falta ahí no es código sino un documento, un responsable y un tiempo de respuesta. Lo mismo con la cuenta root, ahí no hay un bug, hay una decisión de configuración. Un auditor que junte los cinco dominios en uno solo está evaluando vulnerabilidades y no está evaluando el estándar, y su informe dejaría por fuera todo lo que no se ve en el código fuente


**5.** Los responsables y los tiempos también son distintos, y eso en la práctica se nota. Parametrizar las consultas y validar entradas lo hace un desarrollador y se puede cerrar en días, sacar los secretos del código, montar https y darle a la aplicación una cuenta de mínimo privilegio lo hace quien administre la infraestructura, definir roles y niveles de acceso hay que definirlo primero con el negocio antes de programarlo, y escribir el procedimiento de incidentes con su canal de reporte y su escala de severidad le toca a la coordinación del proyecto y no a nadie que toque código. Si juntáramos los cinco dominios en un solo hallazgo no habría a quién asignarle qué y el plan de corrección quedaría imposible de ejecutar y de verificar


Entonces la conclusión de la defensa es que varios hallazgos compartan la misma causa raíz no los vuelve un solo hallazgo, los vuelve un patrón, y reportar el patrón sirve pero no reemplaza reportar cada control. La independencia queda demostrada porque para cada par de dominios existe al menos un estado del sistema en el que uno cumple y el otro no, por eso mantenemos la evaluación separada por dominio, y por eso mismo el orden de corrección que proponemos empieza por las consultas, no porque eso resuelva a los demás sino porque mientras siga abierto los demás controles no se pueden sostener aunque se implementen


---


## Conclusiones


**1.** La app de compra segura tiene un nivel de cumplimiento de los cinco dominios muy bajo


**2.** La falla más grave es de autenticidad es el formulario de acceso que puede ser evadido por completo con la cadena ' OR '1'='1, dando privilegios de admin a cualquier visitante


**3.** La corrección que se debe de tomar con mayor prioridad son las cuatro consultas sql que con un esfuerzo mínimo cierra la mayoría de los hallazgos que vimos antes y protege la app
