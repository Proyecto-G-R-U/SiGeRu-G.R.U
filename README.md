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
