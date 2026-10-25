# SiGeRU — Sistema completo (Apache/XAMPP, sin puertos, sin archivos .js)

Backend de 3 APIs REST en PHP 8 + Frontend en HTML5/CSS.
Todo corre bajo **Apache (XAMPP)** usando `.htaccess`: no se usan los servidores
`php -S` ni los puertos 9000/9001/9002.

El JavaScript (los `fetch` a las APIs) va **embebido dentro de cada HTML**, en una
etiqueta `<script>`. No hay archivos `.js` externos.

Los datos viven en memoria (sin base de datos todavía), como pide la letra.

## Estructura

```
sigeru/                         ← esta carpeta va dentro de htdocs/ de XAMPP
├── api-usuarios/               API REST usuarios
│   ├── api.php
│   ├── .htaccess
│   └── app/ (models, controllers)
├── api-gestion/                API REST contenedores
├── api-recoleccion/            API REST camiones
├── frontend/
│   ├── index.html              Login + panel (con JS embebido)
│   ├── registro.html           Registro de vecinos (con JS embebido)
│   └── css/estilos.css
└── base-de-datos/
    └── base.sql                DDL del modelo físico (para 2da entrega)
```

## Cómo instalar y correr (XAMPP)

1. Instalá **XAMPP** (trae Apache + PHP 8 + mod_rewrite ya activo).
2. Copiá la carpeta `sigeru/` completa dentro de `xampp/htdocs/`.
   Debe quedar: `xampp/htdocs/sigeru/...`
3. Abrí el **panel de control de XAMPP** y arrancá **Apache** (botón Start).
4. En el navegador entrá a:
   - Panel (login):        http://localhost/sigeru/frontend/index.html
   - Registro de vecinos:  http://localhost/sigeru/frontend/registro.html

No hay que arrancar nada más: no hay puertos 9000/9001/9002. Todo corre en el
Apache de XAMPP (puerto 80, el normal).

## Cómo se comunica el frontend con las APIs

Dentro de cada HTML, el `<script>` define las rutas relativas a las APIs:

```js
const API_USUARIOS    = '../api-usuarios';
const API_GESTION     = '../api-gestion';
const API_RECOLECCION = '../api-recoleccion';
```

El frontend está en `/sigeru/frontend/`, así que `..` sube a `/sigeru/` y entra
a la carpeta de cada API. El `.htaccess` de cada API manda la petición a `api.php`,
que resuelve la ruta REST y responde JSON. El `fetch` procesa ese JSON y arma la tabla.

Ejemplo real dentro del HTML:
```js
const resp = await fetch(API_USUARIOS + '/usuarios');   // GET listado
const usuarios = await resp.json();
```

## Endpoints (REST)

- API Usuarios:    `POST /login`, `POST /usuarios`, `GET /usuarios`
- API Gestión:     `GET /contenedores`, `POST /contenedores`, `GET /contenedores/zonas`
- API Recolección: `GET /camiones`, `POST /camiones`

## Cuentas de prueba (contraseña: `1234`)

| Email                     | Rol           |
|---------------------------|---------------|
| admin@sigeru.uy           | Administrador |
| recoleccion@sigeru.uy     | Operario      |
| clasificacion@sigeru.uy   | Operario      |
| vertedero@sigeru.uy       | Operario      |
| vecino@gmail.com          | Vecino        |

## Si algo no funciona

- **Las tablas salen con "Error al conectar"**: verificá que Apache esté corriendo
  en XAMPP y que la carpeta esté en `htdocs/sigeru/` (no en otro nombre).
- **Error 404 al llamar la API**: confirmá que `mod_rewrite` esté activo en Apache.
  En XAMPP viene activo; si lo desactivaste, se activa en `httpd.conf`
  descomentando `LoadModule rewrite_module modules/mod_rewrite.so`.
- **Cambiaste el nombre de la carpeta**: si no la llamás `sigeru`, ajustá la URL
  del navegador. Las rutas relativas `../api-...` siguen funcionando igual.

## Persistencia (archivo JSON) y eliminación de usuarios

**Registro y login ahora funcionan de verdad.** Los usuarios se guardan en
`api-usuarios/data/usuarios.json`. Ese archivo se crea solo la primera vez (con
las cuentas de prueba) y sobrevive entre peticiones, así que:

- Un vecino que se auto-registra queda guardado y **puede iniciar sesión** después.
- Un usuario que crea el administrador también queda guardado y **puede loguearse**.

No es una base de datos formal (no hay MySQL ni SQL), es un archivo plano. En la
2da entrega se reemplaza SOLO `RepositorioUsuarios.php` por una versión con MySQL;
el resto del código no cambia.

> La carpeta `api-usuarios/data/` tiene su propio `.htaccess` que bloquea el
> acceso web directo, para que el archivo con los hashes no sea accesible por URL.

**Eliminar usuarios (solo administrador).** En la pestaña Usuarios, si iniciaste
sesión como administrador, aparece un botón "Eliminar" en cada fila (menos en la
tuya). El backend verifica que quien pide el borrado sea realmente un admin antes
de eliminar (ruta `POST /usuarios/eliminar`).

### Para reiniciar los datos de prueba
Borrá el archivo `api-usuarios/data/usuarios.json`. Se regenera solo con las
cuentas iniciales en la próxima petición.

## Flotas, cuadrillas y asignación de camiones

Se agregó, con el mismo patrón de persistencia JSON (sin base de datos):

**Cuadrillas** (en la sección Usuarios, solo admin). Agrupan operarios. Se crean,
listan y eliminan. Se guardan en `api-usuarios/data/cuadrillas.json`.

**Flotas** (nueva sección Flotas, solo admin). Agrupan camiones. Se ven como un
panel gráfico: una tarjeta por flota con sus camiones dentro. Al clickear un
camión se muestra la cuadrilla asignada y sus operarios. Se guardan en
`api-recoleccion/data/flotas.json`.

**Camiones** (formalizado). Ahora persisten en `api-recoleccion/data/camiones.json`
e incluyen dos relaciones:
- `flotaId`: a qué flota pertenece (se elige al crear el camión).
- `cuadrillaId`: qué cuadrilla tiene asignada (se elige al crear, o se cambia
  después con el selector en la tabla de Camiones).

El modelo de relaciones es: Flota (1) → Camiones (N); Camión (1) → Cuadrilla (1);
Cuadrilla (1) → Operarios (N).

### Endpoints nuevos
- API Usuarios:    `GET/POST /cuadrillas`, `POST /cuadrillas/eliminar`
- API Recolección: `GET/POST /flotas`, `POST /flotas/eliminar`,
                   `POST /camiones/eliminar`, `POST /camiones/asignar-cuadrilla`

### Reiniciar datos
Borrá los `.json` de las carpetas `data/` (en api-usuarios y api-recoleccion) y se
regeneran con los datos de prueba.

## MIGRACIÓN A MYSQL (adelanto de la 2da entrega)

La persistencia en archivos JSON fue **reemplazada por MySQL** (PDO). Los
datos ahora viven en la base `sigeru`, compartida por las 3 APIs.

### Puesta en marcha (XAMPP)
1. En el panel de XAMPP, arrancar **Apache** y **MySQL** (ahora los dos).
2. Importar la base UNA vez: entrar a `http://localhost/phpmyadmin`,
   pestaña **Importar**, elegir `base-de-datos/base.sql` y ejecutar.
   (Crea la base `sigeru`, las 5 tablas y los datos de prueba.)
3. Entrar a `http://localhost/sigeru/frontend/index.html` como siempre.

Los usuarios de prueba (contraseña `1234`) los crea el propio sistema la
primera vez que se usa la API (no van en el SQL porque el hash de la
contraseña debe generarlo PHP). Ver `RepositorioUsuarios::sembrarSiVacio()`.

### Qué cambió en el código
- `RepositorioJson` (base abstracta) fue reemplazada por `RepositorioSql`:
  misma idea de herencia, pero consulta MySQL con PDO en vez de archivos.
- Los 5 repositorios ahora traducen sus operaciones a SQL con consultas
  preparadas (protección contra inyección SQL).
- La relación cuadrilla-operarios cambió de un array embebido a la columna
  `usuario.cuadrilla_id`: la exclusividad ahora la garantiza el diseño de
  la base (un usuario tiene UNA sola cuadrilla_id).
- Las claves foráneas con `ON DELETE SET NULL` liberan automáticamente a
  operarios y camiones cuando se elimina su cuadrilla o flota.
- Controladores, modelos de dominio y frontend: **sin cambios**. Esa era la
  gracia de separar el repositorio del resto.

### Reiniciar datos
Para volver a los datos de prueba: reimportar `base.sql` en phpMyAdmin
(borra y recrea todo).

### Credenciales de conexión
XAMPP por defecto: host `localhost`, usuario `root`, sin contraseña.
Se cambian en `app/models/RepositorioSql.php` de cada API.

## INSTALACIONES E INVENTARIO (2da entrega)

Nueva sección **Instalaciones** en el panel del administrador, que cubre los
requerimientos 3.1.1.2.6 (centros de acopio, plantas de clasificación y
vertederos con capacidad máxima) y 3.1.1.2.7 (inventario de maquinaria y
stock de repuestos).

### Modelo de clases (nueva jerarquía)
    Instalacion (abstracta)
     ├── CentroAcopio
     ├── PlantaClasificacion
     └── Vertedero

Las tres se guardan en la MISMA tabla `instalacion`, distinguidas por la
columna `tipo` — mismo criterio que usuario/rol (single table inheritance).
Al leer de la base, `RepositorioInstalaciones::aObjeto()` rearma la
subclase correcta según esa columna.

### Maquinaria
Cada ítem (`maquinaria`, `herramienta` o `repuesto`) pertenece a UNA
instalación. Si se elimina la instalación, la FK con ON DELETE SET NULL
deja los ítems sin asignar en vez de borrarlos (aparecen en la tabla
"Sin instalación asignada").

### Endpoints nuevos (API Gestión)
- `GET/POST /instalaciones`, `POST /instalaciones/eliminar`,
  `POST /instalaciones/modificar`
- `GET/POST /maquinaria`, `POST /maquinaria/eliminar`,
  `POST /maquinaria/modificar`

### Ubicación en el mapa
Cada instalación guarda sus coordenadas (lat/lng), igual que los
contenedores. Al crearla se marca la ubicación haciendo click en un mapa, y
el listado incluye un mapa con todas, con un **pin de color por tipo**:
celeste = centro de acopio, violeta = planta de clasificación, marrón =
vertedero. Esto cubre lo que pide la letra ("mapa interactivo con ubicación
de contenedores, incidencias y centros de acopio") y deja preparado el
requerimiento 3.1.3.2 (puntos de reciclaje en el mapa del vecino).

### IMPORTANTE: hay que reimportar la base
Este cambio agrega dos tablas nuevas (`instalacion` y `maquinaria`), así
que hay que volver a importar `base-de-datos/base.sql` en phpMyAdmin.
Recordá que el script empieza con DROP DATABASE: se borran los datos
actuales y se recrean los de prueba.

## INCIDENCIAS (2da entrega)

Nueva sección **Incidencias**, visible para TODOS los roles. Cubre los
requerimientos 3.1.1.3.1 a 3.1.1.3.4 y 3.1.3.4 (reporte de vecinos).

### Para cualquier usuario (vecino, operario o admin)
Un mapa de Montevideo con todos los contenedores. Se hace click en un
contenedor para seleccionarlo y luego en "Reportar problema": se elige tipo
(rotura, desborde, falta de recolección u otro), gravedad (baja/media/alta)
y se escribe una breve descripción.
Los contenedores con problemas activos se ven con un **anillo rojo**.

### Solo para el administrador
Debajo del mapa gestiona las incidencias activas:
- cambiar el estado (Abierta / En curso),
- asignar la **cuadrilla responsable**,
- botón **Resolver**: la marca como resuelta, sella la fecha y la manda al
  historial (deja de aparecer en el mapa activo),
- eliminarla.

Más abajo ve el **historial** de incidencias resueltas, con la cuadrilla
que la atendió y las fechas de reporte y resolución.

### Decisión de diseño
Reportar una incidencia NO cambia el estado del contenedor: son cosas
distintas. Si cualquier usuario pudiera alterar el estado del contenedor
reportando, no habría control. El estado lo administra el admin desde su
sección Contenedores.

### Endpoints nuevos (API Gestión)
- `GET /incidencias` (activas, para el mapa)
- `GET /incidencias/historial` (resueltas)
- `POST /incidencias` (crear — cualquier usuario)
- `POST /incidencias/estado`, `POST /incidencias/asignar-cuadrilla`,
  `POST /incidencias/eliminar` (admin)

### IMPORTANTE: reimportar la base
Se agregó la tabla `incidencia`, así que hay que volver a importar
`base-de-datos/base.sql` en phpMyAdmin.

### Ubicación de las instalaciones en el mapa
Las instalaciones ahora guardan coordenadas (lat/lng). Al crearlas se marca
la ubicación con un click en el mapa, igual que los contenedores, y se ven
todas juntas en un mapa con pines de distinto color según su tipo:
celeste = centro de acopio, violeta = planta de clasificación,
marrón = vertedero.

Esto cubre lo que pide la letra (el mapa interactivo debe mostrar también
los centros de acopio) y deja preparado el requerimiento 3.1.3.2 (mapa con
"puntos de reciclaje" para los vecinos).

## NIVEL DE LLENADO Y GUÍA DE RECICLAJE (2da entrega)

### Integridad y llenado separados
El requerimiento 3.1.3.3 distingue "estado de llenado **o integridad**", así
que ahora son dos campos independientes:
- `estado` = integridad física: **operativo** / **roto**
- `nivel_llenado` = cuánto tiene: **vacío** / **lleno** / **desbordado**

Antes "desbordado" era un estado, lo que generaba contradicciones (un
contenedor podía estar "desbordado" y a la vez sano). Ahora un contenedor
puede estar operativo pero desbordado, o roto pero vacío.

En el mapa, el **color del pin refleja la integridad** (verde = operativo,
amarillo = roto) y el **llenado se ve en el popup** al hacer click.

### Guía de reciclaje
Nueva sección **Guía de reciclaje**, visible para los vecinos
(requerimiento 3.1.3.5). Explica qué va y qué NO va en cada tipo de
contenedor (reciclables y mezclados), más consejos previos a tirar y qué
hacer con residuos especiales.

La guía es exclusiva de los vecinos: los operarios no la ven en su menú.
Para el operario de clasificación, la información sobre qué residuo maneja
cada instalación se muestra en su propio panel, no como guía educativa.

### IMPORTANTE: reimportar la base
Cambió la tabla `contenedor` (nueva columna `nivel_llenado` y el ENUM de
`estado`), así que hay que volver a importar `base-de-datos/base.sql`.

## SEGURIDAD POR ROLES Y TESTS (2da entrega)

### Autenticación por token
Antes la restricción por rol vivía solo en el frontend (el menú), pero
cualquiera que conociera una URL podía llamar a la API. Ahora:

1. Al iniciar sesión, la API genera un **token** aleatorio de 64 caracteres
   (`random_bytes`) y lo guarda en `usuario.token`.
2. El frontend lo guarda en sessionStorage y lo envía en cada pedido con la
   cabecera `Authorization: Bearer <token>`.
3. Cada endpoint protegido resuelve el token contra la base y comprueba el
   rol antes de ejecutar nada.
4. Al cerrar sesión, `POST /logout` borra el token: deja de ser válido del
   lado del servidor, no solo en el navegador.

Respuestas: **401** si no hay sesión, **403** si la hay pero sin permiso.
El frontend detecta el 401 y devuelve al login automáticamente.

Criterio aplicado a los endpoints:
- **Públicos:** login y el auto-registro de vecinos.
- **Con sesión (cualquier rol):** consultar contenedores, instalaciones,
  inventario, incidencias, camiones, flotas y rutas; y reportar incidencias.
- **Solo administrador:** todas las altas, bajas y modificaciones, la
  gestión de incidencias y de cuadrillas.

Nota: el `.htaccess` incluye una regla que conserva la cabecera
`Authorization`, porque Apache la descarta al reescribir la URL.

### Pruebas
Carpeta `tests/` con dos conjuntos complementarios (ver tests/LEEME.md):

**Pruebas de API con Postman** — `SiGeRU.postman_collection.json`:
65 peticiones y 116 comprobaciones sobre las tres APIs REST. Cubren
autenticación, la separación de endpoints por rol (401/403), el ABM de cada
entidad, el ciclo completo de una incidencia, las reglas de exclusividad y la
integridad referencial. Se importa en Postman y se corre con el Collection
Runner. Las pruebas limpian lo que crean.

**Pruebas unitarias en PHP** — 72 comprobaciones sobre la lógica del backend.

Ejecutar:
    php tests/correr-tests.php
o abrir en el navegador:
    http://localhost/sigeru/tests/correr-tests.php

Cubren: la jerarquía de herencia de usuarios e instalaciones, que las
clases abstractas no se puedan instanciar, el hash y la verificación de
contraseñas, que el hash nunca se serialice al frontend, la independencia
entre integridad y llenado de los contenedores, el ciclo de vida de las
incidencias y las relaciones de camiones, cuadrillas y maquinaria.

Se implementó un runner propio (`tests/Test.php`) en vez de PHPUnit porque
PHPUnit requiere Composer, que no viene con XAMPP; así las pruebas corren
en cualquier máquina sin instalar nada. Las ideas son las mismas: casos,
aserciones y reporte de pasadas/falladas.

### IMPORTANTE: reimportar la base
Se agregó la columna `usuario.token`, así que hay que volver a importar
`base-de-datos/base.sql`.

## MAPA INTERACTIVO DE ARMADO DE RUTAS (3ra entrega)

La sección **Rutas** del administrador ahora arma el recorrido sobre un mapa
en vez de usar selectores desplegables.

### Cómo funciona
1. Se elige en el desplegable **qué ruta se está armando**.
2. En el mapa aparecen todos los contenedores y todas las instalaciones.
3. Click en un **contenedor** → se suma al recorrido (o se quita si ya estaba).
   Las paradas se dibujan con un pin numerado según el orden del recorrido.
4. Click en una **instalación** → queda fijada como **destino final**, el lugar
   donde el camión descarga al terminar. Click de nuevo para quitarlo.
5. El trayecto se dibuja uniendo las paradas con una línea verde, y el tramo
   final hasta el destino con una línea punteada celeste.
6. Debajo del mapa, la lista ordenada de paradas permite **subir o bajar**
   cada una con las flechas, o quitarla.

### Modelo de datos
- `ruta.instalacion_id` — destino final (FK a `instalacion`, ON DELETE SET NULL:
  si se elimina la instalación, la ruta sobrevive sin destino).
- `ruta_contenedor.orden` — posición de cada parada. Reordenar intercambia el
  `orden` con la parada vecina, así la numeración queda sin huecos.

### Endpoints nuevos (API Recolección)
- `POST /rutas/mover-contenedor` — recibe `{ rutaId, contenedorId, direccion }`
  donde dirección es -1 (subir) o 1 (bajar). Responde 400 si la parada ya está
  en un extremo.
- `POST /rutas/asignar-destino` — recibe `{ rutaId, instalacionId }`.
  Con `instalacionId` en null, la ruta queda sin destino.

Nota de arquitectura: la API de recolección valida la instalación consultando
la tabla `instalacion` de la base compartida, sin llamar a la API de gestión.
Es el mismo criterio que usa `RepositorioCuadrillas` al consultar `usuario`:
las APIs no se hablan entre sí, comparten la base.

### IMPORTANTE: reimportar la base
Se agregó la columna `ruta.instalacion_id`, así que hay que volver a importar
`base-de-datos/base.sql`.

### Trayecto real por las calles (OSRM)

El recorrido ya no se dibuja con líneas rectas entre paradas: se calcula el
camino real siguiendo las calles con **OSRM** (Open Source Routing Machine),
un servicio de ruteo libre. Se usa su servidor público
`router.project-osrm.org`, que no requiere registro ni clave de API.

Cómo funciona:
1. Al cambiar el recorrido se dibujan primero líneas rectas tenues, para que
   la respuesta sea inmediata.
2. En paralelo se le pide a OSRM el camino por calles; cuando llega, reemplaza
   esas rectas por el trazado real (verde para las paradas, celeste punteado
   para el tramo final hacia el destino).
3. Debajo del mapa se muestran la **distancia total en km** y el **tiempo
   estimado de manejo**, que OSRM devuelve junto con la geometría.

Dos detalles de implementación que vale conocer:

- **Orden de coordenadas:** OSRM trabaja en `lng,lat` y Leaflet en `lat,lng`.
  El código invierte el par en ambos sentidos. Es el error más común al
  integrar los dos y se verificó explícitamente.
- **Respuestas fuera de orden:** si se tocan varios contenedores seguidos, las
  respuestas pueden llegar desordenadas y dibujar un trayecto viejo encima del
  nuevo. Cada pedido se numera (`pedidoTrazado`) y al volver se compara: si ya
  hay uno más reciente en curso, la respuesta vieja se descarta.

Si OSRM no responde (sin internet o servidor caído), quedan las líneas rectas
y un aviso lo explica. El mapa nunca queda vacío.

Limitación a tener presente: el servidor público de OSRM es un demo, pensado
para pruebas. Para un uso real convendría levantar una instancia propia o
contratar un servicio de ruteo.


## ZONAS: AGRUPACIÓN POR MUNICIPIO (3ra entrega)

### Para qué está

El contenedor ya guardaba `direccion`, `lat` y `lng`. Eso alcanza para
ubicarlo en el mapa, pero no para **agruparlo**, y hay tres requerimientos
que piden exactamente eso:

| Req.      | Qué pide                                        |
|-----------|-------------------------------------------------|
| 3.1.1.3.4 | historial de incidencias por zona geográfica    |
| 3.1.4.1   | listado de incidencias filtrado por zona        |
| 3.1.4.3   | zonas críticas: cuál acumula más incidencias    |

Los tres terminan en un `GROUP BY zona`. Con `lat`/`lng` no se puede hacer
(son puntos sueltos) y con `direccion` tampoco, porque es texto libre:
"Pocitos, 26 de Marzo" y "26 de marzo y Pocitos" contarían como dos zonas
distintas. Por eso la zona es un campo propio.

### Por qué va en el contenedor y no en la incidencia

La incidencia ya apunta a un contenedor, así que la zona sale con un JOIN:

```sql
SELECT c.zona, COUNT(*) AS total
FROM incidencia i
JOIN contenedor c ON c.id = i.contenedor_id
GROUP BY c.zona
ORDER BY total DESC;
```

Guardarla también en `incidencia` sería duplicarla: si después se mueve un
contenedor de municipio, las incidencias viejas quedarían con la zona vieja.

Tampoco se deduce de la ruta: un contenedor puede estar en varias rutas o en
ninguna, así que su zona quedaría ambigua o vacía. La zona es una propiedad
de **dónde está el contenedor**, no de quién lo recolecta.

### Qué valores

Los 8 municipios de Montevideo (A, B, C, CH, D, E, F y G), que es como la
Intendencia organiza el servicio de limpieza.

La lista está en **dos lugares y nada más**:

- `Contenedor::ZONAS` en `api-gestion/app/models/Contenedor.php`
- `const ZONAS` en `frontend/index.html`

Para adaptar el sistema a otra ciudad (requerimiento 1.1.2, flexibilidad en
entornos urbanos) se cambian esas dos listas. La base no se toca: la columna
es un `VARCHAR(80)`, no un ENUM, justamente para no atarla a Montevideo.

La API valida contra esa lista y rechaza con **400** cualquier municipio
inventado. Si entrara texto libre, los informes se llenarían de zonas
fantasma. Vacío sí es válido y se guarda como `NULL`: significa "todavía sin
zona asignada", que es el estado normal de un contenedor recién cargado.

### Vocabulario unificado

`cuadrilla`, `flota` y `ruta` ya tenían un campo `zona`, pero cada una con su
propio vocabulario ("Zona Norte", "Pocitos"). Ahora las cuatro usan la misma
lista de municipios y el mismo `<select>`, así que un informe puede cruzarlas
sin traducir nombres.

### Endpoint nuevo

```
GET /contenedores/zonas   → { "todas": [...], "enUso": [...] }
```

`todas` es la lista completa (para el alta). `enUso` son sólo los municipios
que ya tienen contenedores: lo va a usar el filtro de los informes para no
ofrecer zonas vacías.

### Si ya tenés la base cargada

`base.sql` ya crea la columna, así que si importás de cero no hay nada que
hacer. Si no querés perder los datos cargados, ejecutá
`base-de-datos/migracion-zona.sql` desde phpMyAdmin: agrega la columna,
completa la zona de los contenedores de prueba y pasa `cuadrilla`, `flota` y
`ruta` al vocabulario de municipios.

La migración se puede correr **las veces que quieras**: pregunta al motor si
la columna ya existe y, si está, avisa y sigue en vez de cortar con el error
`#1060 - Nombre de columna duplicado`. Tampoco pisa zonas que hayas cargado
a mano: sólo traduce los valores viejos conocidos.

Probada contra MariaDB 10.11 (el motor de XAMPP) en los tres escenarios:
base nueva, base vieja sin la columna, y la misma migración corrida dos
veces seguidas.

### La zona se completa sola

Al marcar el punto del contenedor en el mapa, el municipio se calcula solo y
el desplegable queda completo. No hay que elegirlo a mano.

**Cómo funciona.** El frontend lleva embebidos los contornos de los 8
municipios. Cuando hacés click, un algoritmo de punto-en-polígono (ray
casting: traza un rayo horizontal desde el punto y cuenta cuántas veces cruza
el borde; impar = adentro) decide en cuál cayó. Si el punto queda justo sobre
un límite y no entra en ninguno, toma el municipio más cercano dentro de
150 m. Si está fuera de Montevideo, deja la zona vacía.

Todo pasa en el navegador: **no se consulta ningún servidor**. Funciona sin
internet y no depende de que un servicio externo esté disponible, a
diferencia del trazado de rutas, que sí necesita OSRM.

**De dónde salen los contornos.** Del shapefile oficial "Límites de
Municipios de Montevideo" que publica la Intendencia en el catálogo nacional
de datos abiertos. Viene en coordenadas UTM 21S, así que está reproyectado a
latitud/longitud, y simplificado conservando los límites compartidos entre
municipios vecinos: si dos municipios comparten un borde, ese borde se
simplifica una sola vez y no quedan huecos ni solapamientos. Pasó de 111.038
puntos a 2.161, unos 46 KB.

**Qué precisión tiene.** Se comparó el clasificador contra los contornos sin
simplificar sobre 20.000 puntos repartidos por todo Montevideo:

| Resultado          | Cantidad            |
|--------------------|---------------------|
| Acierta            | 19.987 (**99,94%**) |
| Municipio equivocado | 13                |
| Sin zona           | 0                   |

Los 13 errores están a menos de 18 m de un límite real (mediana: 3,4 m). O
sea: sólo se equivoca con contenedores pegados a una frontera municipal, y
para eso el desplegable sigue siendo editable. Tarda 0,008 ms por punto, así
que el campo se completa antes de que sueltes el mouse.

**Un error que encontró.** Al probarlo contra los datos de prueba saltó que
el contenedor CNT-403, cargado como "Rambla y Buceo" en Municipio E, tenía
coordenadas que según los límites oficiales caen en Municipio CH. Estaba mal
la semilla, no el algoritmo. Se corrigió moviéndolo a Malvín, que sí está en
el Municipio E. Es exactamente el tipo de error que la asignación automática
evita.
