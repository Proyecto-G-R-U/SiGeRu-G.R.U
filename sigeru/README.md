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
- API Gestión:     `GET /contenedores`, `POST /contenedores`
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
