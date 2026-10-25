# Pruebas de SiGeRU

Hay dos conjuntos de pruebas, que se complementan:

| Archivo | Qué prueba | Cómo se ejecuta |
|---|---|---|
| `SiGeRU.postman_collection.json` | **Las APIs REST funcionando**: endpoints, códigos HTTP, permisos por rol, reglas de negocio | Postman |
| `correr-tests.php` | **La lógica de las clases** sin tocar la base: herencia, contraseñas, modelos | PHP |

---

## 1. Pruebas de API con Postman (65 peticiones, 116 comprobaciones)

### Preparación
1. Arrancar **Apache** y **MySQL** en XAMPP.
2. Importar `base-de-datos/base.sql` en phpMyAdmin (deja la base con los datos de prueba).
3. Abrir Postman → **Import** → elegir `SiGeRU.postman_collection.json`.

### Ejecución
La colección trae una variable `baseUrl` que vale `http://localhost/sigeru`.
Si el proyecto está en otra carpeta, cambiarla en la pestaña *Variables* de la colección.

Para correr todo junto: botón **Run** de la colección (Collection Runner) → **Run SiGeRU**.

> **Importante:** ejecutar las carpetas **en orden**. La carpeta 1 inicia sesión y
> guarda los tokens que usan todas las demás; las carpetas 5 y 6 crean registros que
> luego usan sus propias peticiones.

Al terminar, el runner muestra cuántas comprobaciones pasaron. Las pruebas
**limpian lo que crean**: al final la base queda como estaba.

### Qué cubre cada carpeta

**1. Autenticación** — login correcto de admin, vecino y operario; token de 64
caracteres; que el hash de la contraseña nunca viaje en la respuesta; rechazo de
credenciales inválidas y de datos faltantes.

**2. Seguridad por roles** — el requisito de la letra. Comprueba que:
- sin token la API responde **401** y no filtra datos,
- un token inventado tampoco sirve,
- un vecino con sesión válida recibe **403** al intentar acciones de administrador,
- un operario recibe **403** al intentar crear camiones,
- pero el vecino **sí** puede consultar contenedores (los necesita para el mapa),
- y el administrador accede normalmente.

**3. ABM de usuarios** — alta, listado, modificación y baja; rechazo de email
duplicado (409) y de campos vacíos (400). Incluye una prueba clave: después de
modificar un usuario sin enviar contraseña, **puede seguir iniciando sesión**
(o sea, editar no borra la contraseña).

**4. ABM de contenedores** — alta con coordenadas, validación de ubicación
obligatoria, código duplicado, modificación y baja. Comprueba que **integridad y
llenado son independientes**: un contenedor puede estar operativo y desbordado.

**5. Instalaciones e inventario** — creación de una planta (verifica que la
jerarquía de subclases asigne bien el tipo), maquinaria asociada, y sobre todo:
al eliminar la instalación, **su inventario no se borra** sino que queda sin
asignar (clave foránea `ON DELETE SET NULL`).

**6. Ciclo de vida de una incidencia** — el recorrido completo: el vecino
reporta → aparece entre las activas, abierta y sin cuadrilla → el vecino no
puede gestionarla (403) → el admin le asigna cuadrilla → la pasa a en curso →
la resuelve → **desaparece de las activas y aparece en el historial con su fecha
de resolución sellada**.

**7. Flota, cuadrillas y rutas** — listados y, sobre todo, las dos reglas de
exclusividad: una cuadrilla no puede estar en dos camiones, y un operario no
puede estar en dos cuadrillas.

**8. Cierre de sesión** — comprueba que el token se invalida **del lado del
servidor**: después del logout, el mismo token recibe 401.

---

## 2. Pruebas unitarias con PHP (61 comprobaciones)

Prueban la lógica de las clases sin base de datos: la jerarquía de herencia, que
las clases abstractas no puedan instanciarse, el hash y verificación de
contraseñas, que el hash nunca se serialice, y las reglas de cada modelo.

Desde la terminal:

    php tests/correr-tests.php

O desde el navegador:

    http://localhost/sigeru/tests/correr-tests.php

Se usó un runner propio (`Test.php`) en lugar de PHPUnit porque PHPUnit se
instala con Composer, que no viene incluido en XAMPP; así las pruebas corren en
cualquier máquina sin instalar nada. Las ideas son las mismas: casos de prueba,
aserciones y reporte final.
