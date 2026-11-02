# Guía de auditoría, uso y pruebas — Proyecto Casino (Laravel)

Asignatura: Bases de Datos, Administración y Aplicaciones — Pontificia Universidad Javeriana.
Integrantes: Juan David Ramírez, Alejandro Doncel y Dana Katalina Díaz.
Fecha de la auditoría: 2026-11-01. Código auditado: rama `main`, commit `1102802`. Ese mismo día se corrigieron y verificaron los tres defectos encontrados (DEF-01, DEF-02 y DEF-03, §18); esta guía describe la aplicación ya corregida.

Fuentes de verdad:

- **P** = `2026.3-ProyectoCasinoxFrameworks (1).pdf` (requisitos del Proyecto Framework).
- **C** = `2026.09.15-Parcial_1-contexto (1).pdf` (reglas de negocio del casino). El archivo `2026.09.15-Parcial_1-contexto (1)(1).pdf` que menciona el encargo no existe en la carpeta de descargas; se usó `(1).pdf`, que es la copia más reciente.
- Las decisiones de interpretación del equipo (D-01 … D-24) están en [matriz-cumplimiento.md](matriz-cumplimiento.md), sección 8. Esta guía las cita cuando una regla de C admite más de una lectura.

Cómo leer los estados:

| Estado | Significado |
|---|---|
| **APROBADO** | Implementado y verificado con evidencia de esta auditoría. |
| **PARCIAL** | Implementado, pero falta un comportamiento o hay un defecto. |
| **INCUMPLIDO** | No implementado, o contradice el requisito. |
| **NO VERIFICADO** | No hay evidencia suficiente. |
| **BLOQUEADO** | No se pudo probar por una limitación identificada. |

---

## 1. Resumen ejecutivo

### Veredicto

| Pregunta | Respuesta corta | Estado |
|---|---|---|
| ¿Está hecho en Laravel? | Sí: Laravel Framework 13.39.0 sobre PHP 8.4.1, con la estructura, el enrutador, Eloquent, Blade, migraciones y el servidor de Laravel. | APROBADO |
| ¿Las contraseñas se guardan con SHA-256? | Sí, literalmente: la columna `users.password` guarda los 64 caracteres hexadecimales de SHA-256(contraseña). Se comprobó en los 9 usuarios de demostración y en un usuario creado desde la interfaz. No se usa bcrypt. | APROBADO |
| ¿Login y sesiones? | Login «Zero Knowledge» con desafío de un solo uso; la contraseña nunca viaja. Sesión regenerada al entrar, invalidada al salir; una cookie de una sesión cerrada ya no sirve. | APROBADO |
| ¿Control por roles? | 4 roles. Menú distinto por rol y, sobre todo, el **servidor** responde 403 a las rutas y operaciones de otros roles (8 usuarios × 22 páginas y 12 operaciones de escritura probadas sin navegador). | APROBADO |
| ¿Casos de uso (mínimo 3)? | 4 implementados y verificados: mesas, rondas, apuestas y ganancias. Suspensiones existe como funcionalidad adicional (no como CRUD completo). Consignaciones/retiros no existe (es opcional). | Mínimo cumplido |
| ¿Funcionalidades adicionales (mínimo 3 + interfaz)? | Las 4 de la lista: notificaciones, gestión de empleados, historial de clientes y validación de autorizaciones/suspensiones, más la interfaz web. | APROBADO |
| ¿Reglas de negocio? | Todas verificadas, incluidas las suspensiones RN-14, RN-15, RN-17 y RN-18 provocadas en vivo. Los dos defectos encontrados, DEF-01 (póker) y DEF-02 (turnos contiguos), ya están corregidos y verificados. | APROBADO |
| ¿Pruebas automáticas? | 125 pruebas, 661 aserciones, 0 fallos (6,10 s) en la VM tras las correcciones (en la auditoría eran 122). | APROBADO |
| ¿Funciones fuera de alcance? | No hay: ni suscripciones, ni facturación, ni integraciones externas, ni analítica. Todo lo adicional se justifica con C. | APROBADO |

### Problemas encontrados

| Id | Gravedad | Resumen | Estado | Detalle |
|---|---|---|---|---|
| DEF-01 | Media-baja | En póker, un crupier podía registrar —con una solicitud fabricada, no desde la interfaz— una apuesta en una ronda cerrada de un turno anterior de su mesa, incluso de otro crupier. | Corregido y verificado | §18 |
| DEF-02 | Media | Con los datos del seeder, el Administrador no podía crear un turno contiguo al turno sembrado (por ejemplo, 10:00–12:00 después de 08:00–10:00): salía un falso «se solapa». | Corregido y verificado | §18 |
| DEF-03 | Baja (cosmético) | Las páginas 404 («Not Found») y 419 («Page Expired») estaban en inglés. | Corregido y verificado | §18 |
| OBS-01…10 | Baja / informativo | Observaciones de presentación, seguridad y criterios de interpretación para defender en la exposición. | Abiertas | §18 |

Durante la auditoría no se corrigió nada (regla del encargo). Después, con la aprobación del grupo, se corrigieron DEF-01, DEF-02 y DEF-03. Cada uno tiene una prueba automática nueva (se comprobó que las de DEF-01 y DEF-02 fallan con el código anterior) y una verificación en vivo ([verificacion-correcciones-2026-11-01.txt](evidencias/auditoria/verificacion-correcciones-2026-11-01.txt)).

### Cómo se hizo la auditoría

1. Se leyeron P y C completos y todo el código (rutas, controladores, solicitudes de validación, servicios, modelos, migraciones, vistas y pruebas).
2. En la VM se ejecutaron `php84 artisan --version`, `route:list`, `composer show laravel/framework`, `about`, `db:show`, `config:show` y la batería `php84 artisan test`, que usa una base SQLite en memoria y no toca los datos.
3. Para no tocar la base real que usa el grupo, se levantó una **copia aislada** de la aplicación en la VM: el mismo código de `~/casino` servido en `127.0.0.1:8001` con su propia base `~/casino-auditoria/database.sqlite`, cargada con el seeder y una contraseña de prueba temporal. Se accedió por túnel en `http://127.0.0.1:8011`. Antes se guardó una copia de la base real en `~/casino-respaldos/`, y se comprobó que el archivo de la base real no cambió durante toda la auditoría.
4. Permisos, sesiones y SHA-256 se probaron **contra el servidor** con `curl` y la sesión real de cada usuario: resultados en [evidencias/auditoria/resultados-servidor-2026-11-01.txt](evidencias/auditoria/resultados-servidor-2026-11-01.txt).
5. Los flujos de uso se recorrieron en un navegador Edge controlado por script, a 1280×800 (y a 375 px de ancho para celular), y se guardaron **71 capturas reales** en [evidencias/auditoria/](evidencias/auditoria/). No hay capturas fabricadas ni retocadas.
6. El registro de errores de Laravel (`storage/logs/laravel.log`) no tuvo ningún error durante la auditoría.

---

## 2. Cómo ejecutar la aplicación

### 2.1. Dónde corre

| Elemento | Valor |
|---|---|
| Servidor | VM Rocky Linux 9.8, `10.43.98.50`, usuario `estudiante` (acceso por llave SSH). |
| Carpeta | `~/casino` |
| PHP | PHP 8.4.1 de usuario en `~/.local/php84`, invocado como `php84`. El PHP 8.0 del sistema no se usa (D-18). |
| Base de datos | SQLite en `~/casino/database/database.sqlite` (D-17). |
| Servidor web | `php84 artisan serve --host=127.0.0.1 --port=8000`: solo escucha en la propia VM; no se abre ningún puerto (D-19). |
| Acceso | Túnel SSH desde el PC: el navegador del PC abre `http://localhost:<puerto>`. |

### 2.2. Paso a paso para un integrante

1. Abre una terminal en tu PC y crea el túnel. Déjala abierta mientras pruebas:

   ```bash
   ssh -N -L 8010:127.0.0.1:8000 estudiante@10.43.98.50
   ```

2. Si el servidor no está corriendo en la VM (la página no carga), inícialo en otra terminal:

   ```bash
   ssh estudiante@10.43.98.50 'cd ~/casino && nohup ~/.local/bin/php84 artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &'
   ```

3. Abre `http://localhost:8010` en el navegador del PC.
   - Usa `localhost`, no la IP de la VM: el cifrado del navegador (Web Crypto) solo funciona en `localhost` o `https`. Con otra dirección, el login muestra «La autenticación segura requiere entrar por https o localhost.».
4. Consulta la contraseña de las cuentas de demostración. No está en el repositorio (D-21), solo en el `.env` de la VM:

   ```bash
   ssh estudiante@10.43.98.50 'grep ^CASINO_DEMO_PASSWORD= ~/casino/.env'
   ```

5. Entra con cualquiera de los usuarios de §2.3.

### 2.3. Usuarios de demostración (los crea el seeder)

Todos usan la contraseña de `CASINO_DEMO_PASSWORD`. Esta guía nunca la escribe.

| Usuario | Rol | Datos que trae |
|---|---|---|
| `admin` | Administrador | Sin perfil de jugador. |
| `crupier1` | Crupier | Competencias: Ruleta y Blackjack. Salario 3000. En el turno en curso atiende R-01. |
| `crupier2` | Crupier | Competencias: Blackjack y Póker. Salario 3000. En el turno en curso atiende B-01. |
| `crupier3` | Crupier | Competencias: Ruleta y Póker. Salario 1000. En el turno en curso atiende P-01. Tiene perfil de jugador `EMP-CLI-003` **en investigación**. |
| `supervisor1` | Supervisor | Competencias: los tres juegos. Supervisa R-01 y P-01 en el turno en curso. |
| `supervisor2` | Supervisor | Competencias: Ruleta y Blackjack. Supervisa B-01 en el turno en curso. |
| `jugador1` | Jugador | `CLI-001`, saldo 1050, **activo**. |
| `jugador2` | Jugador | `CLI-002`, saldo 4800, **suspendido** por pausa voluntaria. |
| `jugador3` | Jugador | `CLI-003`, saldo 400, **suspendido** por pérdida diaria. |

### 2.4. Datos que deja el seeder (relativos al momento en que se ejecuta)

- Mesas: R-01 Ruleta (mínimo 10, máximo 500), B-01 Blackjack (20 / 1000), P-01 Póker (50 / 2000).
- **Turno en curso**: empieza en la hora en punto en que se ejecutó el seeder y dura 2 horas. Ejemplo: si se carga a las 08:16, el turno va de 08:00 a 10:00.
  - Después de esa hora, los crupieres ya no tienen mesa.
  - Para seguir probando, crea un turno nuevo con sus asignaciones (§11.11 y §11.12) o vuelve a cargar los datos (§2.5).
- R-01 tiene una ronda **abierta** (número 3) con una apuesta pendiente: `CLI-001 · Rojo · 50 × 2`.
- P-01 tiene una ronda cerrada («Mesa terminada») con una apuesta perdida de `CLI-003` por 1100: con esa pérdida, CLI-003 quedó suspendido.
- Turnos anteriores:
  - hace 5, 4 y 3 días en P-01, con crupier2 y supervisor1, con el **concepto pendiente**: sirven para probar «Mis conceptos»;
  - dos turnos de ayer con conceptos ya registrados, entre ellos una «Sospecha de fraude».
- Historial de `EMP-CLI-003` (crupier3):
  - una suspensión por pérdida de empleado, levantada con un abono de 500;
  - una investigación activa, que empezó cuando ganó 4200 con una apuesta de 600 × 7.

### 2.5. Volver a cargar los datos de demostración (borra todo lo que haya)

Hazlo solo de común acuerdo entre los integrantes y siempre con copia previa:

```bash
ssh estudiante@10.43.98.50 'cd ~/casino && mkdir -p ~/casino-respaldos && cp -p database/database.sqlite ~/casino-respaldos/database-$(date +%Y%m%d-%H%M%S).sqlite && ~/.local/bin/php84 artisan migrate:fresh --seed --force'
```

### 2.6. Ejecutar las pruebas automáticas

No tocan los datos: usan una base en memoria (`phpunit.xml`, `DB_DATABASE=:memory:`).

```bash
ssh estudiante@10.43.98.50 'cd ~/casino && ~/.local/bin/php84 artisan test'
```

---

## 3. Verificación de Laravel y arquitectura

### 3.1. Evidencias de que el framework es Laravel

| Evidencia | Qué se observó | Dónde |
|---|---|---|
| `php84 artisan --version` | `Laravel Framework 13.39.0` | Ejecutado en la VM el 2026-11-01. |
| `php84 -v` | `PHP 8.4.1 (cli)` | VM. |
| `composer show laravel/framework` | `versions : * v13.39.0`, licencia MIT, `homepage : https://laravel.com` | VM. |
| `composer.json` | `"laravel/framework": "^13.17"`, `"laravel/tinker": "^3.0"`; desarrollo: phpunit 12, pint, faker, mockery, collision, pail y pao, todos de la plantilla de Laravel. | `composer.json` |
| `artisan` | Consola de Laravel en la raíz; con ella se migra, se siembra, se sirve y se prueba. | `artisan` |
| Arranque | `Application::configure(...)->withRouting(...)->withMiddleware(...)`; registra el alias de middleware `rol`. | `bootstrap/app.php:9-22` |
| Rutas | 54 rutas propias en `routes/web.php`, con `Route::resource` y grupos de middleware `guest`, `auth` y `rol:*`. | `routes/web.php:23-64`; salida en [route-list-2026-11-01.txt](evidencias/auditoria/route-list-2026-11-01.txt) |
| Controladores | 19 (17 de la aplicación, 1 base y 2 de autenticación, que heredan de `App\Http\Controllers\Controller`). | `app/Http/Controllers/` |
| Validación | 13 *Form Requests* de Laravel. | `app/Http/Requests/` |
| Modelos | 11 modelos Eloquent con relaciones (`hasMany`, `belongsTo`, `belongsToMany`). | `app/Models/` |
| Servicios | 5 servicios con la lógica de negocio y transacciones `DB::transaction` con bloqueo `lockForUpdate`. | `app/Services/` |
| Notificaciones | 4 clases `Illuminate\Notifications\Notification` por el canal `database`. | `app/Notifications/` |
| Migraciones y seeder | 11 migraciones (3 de la plantilla y 8 del casino) y un `DatabaseSeeder`. | `database/` |
| Vistas | 36 plantillas Blade con layouts, `@csrf`, `@method` y `old()`. | `resources/views/` |
| Idioma | Traducciones en `lang/es/validation.php`; `Locale: es`. | `lang/es/` |
| Pruebas | 19 archivos de pruebas de Laravel (`RefreshDatabase`, `actingAs`, `travelTo`). | `tests/Feature/` |
| Ejecución | `php artisan serve` (servidor de Laravel) sobre `public/index.php`. | §2 |

La aplicación no solo depende de Laravel: está escrita con sus piezas.
- Las rutas llevan a controladores, que validan con *Form Requests* y delegan en servicios.
- Los datos se acceden con Eloquent sobre migraciones.
- Las vistas son Blade.
- La autorización usa middleware.
- Las notificaciones usan el sistema de Laravel.
- Las pruebas usan las herramientas de prueba de Laravel.

### 3.2. Entorno observado (`php84 artisan about`, `db:show`, `config:show`)

| Dato | Valor |
|---|---|
| Aplicación / entorno | `Casino`, `local` |
| Modo depuración | **ENABLED** (ver OBS-02) |
| Zona horaria / idioma | `America/Bogota` (D-20) / `es` |
| Base de datos | SQLite 3.43.2, `database/database.sqlite`, 20 tablas |
| Sesión | driver `database`, duración 120 min, `http_only=true`, `same_site=lax`, `expire_on_close=false` |
| Caché / colas | `database` / `database` (las notificaciones se envían sin cola) |
| Umbral de pérdida diaria | `casino.umbral_perdida_diaria = 1000.00` (variable `CASINO_UMBRAL_PERDIDA_DIARIA`, D-09) |

### 3.3. Arquitectura

```
Navegador ──HTTP──> routes/web.php ──middleware (guest | auth | rol:…)──> Controlador
                                                                        │
                       Form Request (validación de formato) <───────────┤
                       Servicio (reglas de negocio, transacción) <──────┤
                       Modelos Eloquent ──> SQLite                      │
                       Notificación (canal database) <──────────────────┤
                       Vista Blade <────────────────────────────────────┘
public/js/zk.js: calcula SHA-256 en el navegador (login y altas de contraseña).
```

Modelo de datos (tablas del casino): `users` (usuario, SHA-256, rol), `empleados` (documento, salario), `juegos` (con `registro_detallado`), `competencias` (empleado↔juego), `jugadores` (código único, documento, edad, saldo), `mesas`, `turnos`, `asignaciones` (turno + mesa + crupier + supervisor + concepto), `rondas`, `apuestas`, `suspensiones`, `abonos` y `notifications`. Las de la plantilla son `sessions`, `cache`, `jobs` y relacionadas. Restricciones relevantes:
- `jugadores.codigo_cliente` y `jugadores.documento` únicos;
- `asignaciones` únicas por (`turno_id`, `mesa_id`) y por (`turno_id`, `crupier_id`);
- `rondas` únicas por (`mesa_id`, `numero`);
- `enum` (que SQLite guarda como restricción CHECK) para rol, concepto, estado de la apuesta y motivo de la suspensión;
- claves foráneas con `restrictOnDelete` para no perder historial.

---

## 4. Checklist de requisitos técnicos

| # | Requisito (fuente) | Cómo se cumple | Evidencia | Estado |
|---|---|---|---|---|
| T-01 | Usar exclusivamente el framework asignado (P §2, §7) | Laravel 13 en todo el sistema; no hay otro framework. | §3.1 | APROBADO |
| T-02 | Base de datos relacional o NoSQL (P §7) | SQLite relacional con claves foráneas, índices únicos y restricciones CHECK. | `db:show`, migraciones | APROBADO |
| T-03 | Persistencia de datos (P §7) | Todo se guarda en `database.sqlite`; los datos sobreviven entre sesiones y reinicios del servidor. | Altas de la auditoría consultadas después en la base | APROBADO |
| T-04 | Manejar correctamente la lógica de negocio (P §7) | Servicios con transacciones y bloqueo de filas; todas las reglas de C verificadas (§8). | §8 | APROBADO (DEF-01 y DEF-02 corregidos) |
| T-05 | Arquitectura coherente con el framework (P §7) | MVC de Laravel con *Form Requests*, servicios, middleware y notificaciones. | §3.3 | APROBADO |
| T-06 | Autenticación de usuarios Zero Knowledge (P §3) | El navegador envía SHA-256(SHA-256(clave) + nonce); la clave no viaja. | §5 | APROBADO |
| T-07 | Control de acceso por roles (P §3, §4) | Middleware `rol` y comprobaciones por registro. | §6 | APROBADO |
| T-08 | Interfaz web funcional (P §3, §6) | 36 vistas Blade con menú por rol, mensajes de éxito y error, y formularios. A 375 px, las vistas del crupier se ajustan (AUD-70); en las del administrador, el menú de 7 opciones desborda (OBS-05). | 71 capturas | APROBADO |
| T-09 | Código fuente completo e instrucciones de instalación (P §8) | Repositorio con `README.md` (requisitos, instalación, cuentas, pruebas, despliegue en la VM). | `README.md` | APROBADO |
| T-10 | Documento técnico: framework, arquitectura, modelo de datos, funcionalidades y dificultades (P §8) | [documento-tecnico.md](documento-tecnico.md) tiene las secciones 1 a 6 con esos temas. | Encabezados del documento | APROBADO (contenido no auditado línea por línea) |
| T-11 | Evidencia: datos de prueba y capturas (P §8) | Seeder reproducible, 11 capturas del 29/10, 69 capturas de esta auditoría y salida de pruebas. | [evidencias/](evidencias/) | APROBADO |
| T-12 | Grupo de 3 estudiantes (P §10) | Tres integrantes en el README y el documento técnico. | `README.md` | APROBADO |
| T-13 | Exposición con análisis crítico del framework (P §10) | Aún no está preparado (pendiente por decisión del grupo). No es parte de la aplicación. | — | NO VERIFICADO (pendiente) |

---

## 5. Checklist de autenticación, SHA-256 y sesiones

### 5.1. Login

| Comprobación | Implementación | Prueba realizada | Esperado | Observado | Evidencia | Estado |
|---|---|---|---|---|---|---|
| Pantalla de login | `GET /login` (`LoginController@create`, `LoginController.php:14-20`) genera un nonce aleatorio de 32 bytes, lo guarda en la sesión y lo pone en un campo oculto. Vista `auth/login.blade.php`. | Abrir `/login`. | Formulario con Usuario, Contraseña y botón «Entrar». | Así. | AUD-01 | APROBADO |
| Credenciales válidas | `LoginController@store` (`:22-40`) → `AutenticacionZeroKnowledge::autenticar` (`app/Services/AutenticacionZeroKnowledge.php:10-24`); si coincide, `Auth::login` y `session()->regenerate()` (`:36-37`). | Entrar como cada uno de los 9 usuarios. | Redirección a `/inicio`. | `302 → /inicio` para los 9 usuarios. | AUD-03, resultados §1 | APROBADO |
| Rechazo de credenciales incorrectas | El mismo servicio devuelve `null`; mensaje genérico (`LoginController.php:30-34`). | Clave incorrecta para `admin`; usuario inexistente. | Volver a `/login` con «Usuario o contraseña incorrectos.» | Así en los dos casos; no revela si el usuario existe. | AUD-02 | APROBADO |
| Manejo de errores | `LoginRequest.php:18` exige que la respuesta sea hexadecimal de 64 caracteres; si el navegador no tiene Web Crypto, `zk.js:59-63` muestra un aviso. | Enviar la clave en claro como respuesta. | Rechazo. | «El formato del campo respuesta no es válido.» | resultados §5 | APROBADO |
| Protección de rutas privadas | Grupo `auth` (`routes/web.php:28`). | `GET` a las 22 páginas sin sesión. | Redirección a `/login`. | `302 → login` en las 22. | resultados §2 | APROBADO |
| Acceso a las funciones tras autenticarse | Menú por rol (`layouts/partials/menu.blade.php:2-53`). | Entrar con cada rol. | Menú del rol. | Admin: 7 opciones; crupier: Inicio y Mi mesa; supervisor: Inicio, Mis conceptos y Ganancias; jugador: Inicio, Mi cuenta y Mi historial. | AUD-03, AUD-20, AUD-33, AUD-37 | APROBADO |

**Procedimiento manual (Persona A o B):**
1. Abre `http://localhost:8010/login`.
2. Escribe `admin` y una contraseña inventada, y pulsa «Entrar». Debe aparecer el recuadro rojo «Usuario o contraseña incorrectos.» (captura AUD-02).
3. Repite con la contraseña de demostración. Debe abrir «Inicio» con el texto «Sesión iniciada como admin (Administrador)» y el menú «Inicio · Empleados · Jugadores · Mesas · Turnos · Ganancias · Suspensiones» (AUD-03).
4. Para ver que la contraseña no viaja:
   - Abre las herramientas de desarrollo (F12) en la pestaña «Red» y vuelve a entrar.
   - En la solicitud `POST /login`, la carga útil solo tiene `_token`, `nonce`, `respuesta` (64 hexadecimales) y `username`. No hay ningún campo con la contraseña.

### 5.2. Almacenamiento de contraseñas con SHA-256

**Qué algoritmo se usa:** SHA-256, y nada más. No se usa `Hash::make`, `bcrypt()` ni Argon2. La búsqueda en `app/`, `database/` y `routes/` no encontró ninguna de esas llamadas, y el modelo `User` no tiene el *cast* `hashed` (`app/Models/User.php:21-24`). El valor `hashing.driver = bcrypt` de la configuración es el predeterminado de Laravel y la aplicación no lo usa.

**Dónde se aplica:**

| Momento | Archivo y línea | Qué hace |
|---|---|---|
| Alta o cambio de contraseña en la interfaz | `public/js/zk.js:25-39` | Exige al menos 8 caracteres y que la confirmación coincida. Calcula `hash.value = SHA-256(clave)`, vacía los campos de contraseña y envía solo `password_hash`. |
| Validación en el servidor | `JugadorStoreRequest.php:30`, `EmpleadoStoreRequest.php:25`, `*UpdateRequest` | `password_hash` debe cumplir `regex:/^[0-9a-f]{64}$/`. |
| Guardado | `JugadorController.php:59`, `EmpleadoController.php:33`, `:62-64`, `JugadorController.php:89-91` | `User::create(['password' => $datos['password_hash'], ...])`: se guarda tal cual el SHA-256. |
| Datos de demostración | `database/seeders/DatabaseSeeder.php:26-34` | `$password = hash('sha256', $demo);` |
| Columna | `database/migrations/0001_01_01_000000_create_users_table.php:14` | `$table->string('password', 64);` |
| Verificación en el login | `AutenticacionZeroKnowledge.php:12-23` | Toma el nonce de la sesión (una sola vez), calcula `SHA-256(hashGuardado + nonce)` y compara con `hash_equals`. |

Fragmento real de la verificación (`app/Services/AutenticacionZeroKnowledge.php:12-21`):

```php
$nonce = $request->session()->pull('login_nonce');          // un solo uso
...
$hashGuardado = $user?->password ?? str_repeat('0', 64);    // SHA-256(clave) guardado
$respuestaEsperada = hash('sha256', $hashGuardado.$nonce);
$valida = hash_equals($respuestaEsperada, $respuesta);      // comparación en tiempo constante
```

Y en el navegador (`public/js/zk.js:66-67`):

```js
const h1 = await digest(password.value);          // SHA-256(clave)
response.value = await digest(h1 + nonce);         // SHA-256(SHA-256(clave) + nonce)
```

**Cómo se construye el valor almacenado:** `hex(SHA-256(UTF-8(contraseña)))`, en minúsculas y sin sal.

**Comprobación con datos reales (copia de auditoría):**
- Para los 9 usuarios del seeder se comparó `users.password` con SHA-256 de la contraseña de prueba. Resultado: `longitud=64 hex_minusculas=si igual_a_sha256(clave_de_prueba)=si parece_bcrypt=no`.
- Además, el usuario `jugador4` se creó **desde el formulario de la interfaz** (AUD-19). Su valor guardado también es exactamente SHA-256 de la contraseña escrita.
- Ver [resultados-servidor §6](evidencias/auditoria/resultados-servidor-2026-11-01.txt).
- No se imprimió ningún hash ni contraseña.

**¿Cumple literalmente «almacenamiento de contraseñas usando SHA-256»?** Sí. **APROBADO.**

Límites que conviene conocer y defender (no incumplen el requisito; ver OBS-03 y OBS-04):
- SHA-256 sin sal: dos usuarios con la misma contraseña tienen el mismo valor guardado (se ve en los 9 usuarios de demostración).
- SHA-256 es un algoritmo rápido, más débil ante ataques de diccionario que bcrypt o Argon2.
- En este esquema, quien obtenga el valor guardado puede responder el desafío sin conocer la contraseña. Es propio de este diseño de Zero Knowledge con SHA-256, no un error de implementación.
- El mínimo de 8 caracteres solo lo comprueba el navegador (D-23): el servidor recibe un hash y no puede saber la longitud original.

**Cómo verificarlo manualmente sin exponer contraseñas** (en la VM, sobre un usuario de prueba creado por ustedes; la clave se pasa por una variable de entorno y no se imprime):

```bash
ssh -t estudiante@10.43.98.50 'cd ~/casino && read -rsp "Clave del usuario de prueba: " CLAVE && CLAVE="$CLAVE" ~/.local/bin/php84 artisan tinker --execute="echo hash_equals(hash(\"sha256\", getenv(\"CLAVE\")), App\Models\User::where(\"username\", \"jugador4\")->value(\"password\")) ? \"coincide\" : \"no coincide\";"'
```

### 5.3. Manejo de sesiones

| Comprobación | Implementación | Prueba | Observado | Estado |
|---|---|---|---|---|
| Inicio de sesión | `Auth::login($user)` y `session()->regenerate()` (`LoginController.php:36-37`): id nuevo para evitar fijación de sesión. | `AutenticacionTest::test_rf_aut_02_login_regenera_el_id_de_sesion`. | Pasa. | APROBADO |
| Mantenimiento | Sesiones de Laravel en la tabla `sessions` (driver `database`); cookie `http_only`, `same_site=lax`; 120 minutos de inactividad. | Con la sesión abierta, `GET /empleados`. | 200. | APROBADO |
| Cierre de sesión | `LogoutController.php:14-16`: `Auth::logout()`, `session()->invalidate()` y `regenerateToken()`. El botón «Cerrar sesión» de la cabecera hace un `POST /logout` con token CSRF. | Pulsar «Cerrar sesión». | `302 → /login`. | APROBADO |
| Ruta protegida después de cerrar sesión | Grupo `auth`. | Tras cerrar sesión, `GET /empleados`. | `302 → /login`. | APROBADO (AUD-04) |
| Reutilizar la cookie de una sesión cerrada | La sesión se invalida en el servidor. | Se guardó la cookie antes del cierre y se reutilizó después. | `302 → /login`. | APROBADO |
| Reutilizar un desafío capturado (repetición) | `session()->pull('login_nonce')` borra el nonce al usarlo (`AutenticacionZeroKnowledge.php:12`). | Reenviar el mismo `nonce` y `respuesta` desde otra sesión. | `302 → /login`, sin sesión. | APROBADO |
| Enviar SHA-256(clave) sin nonce | La respuesta esperada incluye el nonce. | Enviar `respuesta = SHA-256(clave)`. | Rechazado. | APROBADO |
| `POST /logout` sin token | Protección CSRF de Laravel. | `curl -X POST /logout`. | 419 «La sesión expiró. Vuelve a cargar la página e inténtalo de nuevo.» (en inglés antes de corregir DEF-03). | APROBADO |
| Usuario con sesión abierta que entra a `/login` | Middleware `guest`. | Abrir `/login` estando dentro. | `302 → /`, que redirige a `/inicio`. | APROBADO |

Código responsable: `routes/web.php:24-28` (grupos `guest` y `auth`), `LoginController`, `LogoutController`, `AutenticacionZeroKnowledge`, `config/session.php`.

**Prueba manual reproducible:**
1. Entra como `admin`.
2. Copia en otra pestaña la dirección `http://localhost:8010/empleados`.
3. Pulsa «Cerrar sesión».
4. Recarga la otra pestaña: debe mostrar el login (AUD-04).

---

## 6. Matriz de permisos por rol

Roles existentes (`app/Enums/Rol.php:7-10`): `administrador`, `crupier`, `supervisor` y `jugador`. Un empleado de cualquier rol puede tener además un **perfil de jugador** (D-13, RN-16): entonces ve «Mi cuenta» y «Mi historial», pero no puede apostar por sí mismo; lo registra un crupier como a cualquier cliente.

Cómo se aplica:
- Por ruta: el middleware `rol:` (`app/Http/Middleware/VerificarRol.php:17`, `routes/web.php:37, 49, 53, 63`) responde 403.
- Por registro:
  - el crupier solo opera la mesa de su turno en curso (`RondaController.php:57-65`, `ApuestaController.php:54-60`);
  - el supervisor solo registra conceptos de sus asignaciones (`ConceptoController.php:27`) y solo ve ganancias de las asignaciones que supervisó (`GananciaController.php:20-26, 37-39`);
  - cada usuario solo lee y marca sus propias notificaciones (`NotificacionController.php:18`).

### 6.1. Matriz funcional (observada en el servidor, no solo en el menú)

| Funcionalidad | Administrador | Crupier | Supervisor | Jugador |
|---|---|---|---|---|
| Gestión de mesas | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Turnos y asignaciones | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Gestión de rondas | Denegado (403) | Permitido, solo en su mesa y su turno en curso | Denegado (403) | Denegado (403) |
| Gestión de apuestas (registrar, modificar, anular) | Denegado (403); las consulta en el historial de cada jugador | Permitido, solo en su mesa y su turno en curso | Denegado (403) | Denegado (403); consulta las suyas en «Mi historial» |
| Gestión de ganancias | Permitido (todas las mesas) | Denegado (403) | Permitido, solo rondas de asignaciones que supervisó | Denegado (403) |
| Gestión de empleados | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Registro de jugadores | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Notificaciones | Permitido (propias) | Permitido (propias) | Permitido (propias) | Permitido (propias); ajenas: 404 |
| Historial de clientes | Permitido (todos) | Solo el propio si tiene perfil (crupier3); otros: 403 | Denegado (sin perfil de jugador: 403) | Permitido (solo el propio) |
| Suspensiones: consulta y fin de investigación | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Suspensiones: pausa voluntaria | Denegado (sin perfil: 403) | Solo con perfil de jugador | Denegado (sin perfil: 403) | Permitido (la propia) |
| Abonos de deuda de empleados | Permitido | Denegado (403) | Denegado (403) | Denegado (403) |
| Conceptos del supervisor | Lo ve en el detalle del turno (solo lectura) | Denegado (403) | Permitido, solo sus asignaciones con el turno terminado | Denegado (403) |

### 6.2. Resultado de la prueba contra el servidor (`GET`, sin navegador)

Copia de [resultados-servidor §2](evidencias/auditoria/resultados-servidor-2026-11-01.txt). 200 = permitido; 403 = denegado; `302→login` = sin sesión.

| Ruta | Sin sesión | admin | crupier1 | crupier2 | crupier3 | supervisor1 | supervisor2 | jugador1 | jugador2 |
|---|---|---|---|---|---|---|---|---|---|
| `/inicio` | 302→login | 200 | 200 | 200 | 200 | 200 | 200 | 200 | 200 |
| `/empleados`, `/empleados/create`, `/empleados/1/edit` | 302→login | 200 | 403 | 403 | 403 | 403 | 403 | 403 | 403 |
| `/jugadores`, `/create`, `/1`, `/1/edit`, `/4/abonos` | 302→login | 200 | 403 | 403 | 403 | 403 | 403 | 403 | 403 |
| `/mesas`, `/mesas/create`, `/mesas/1/edit` | 302→login | 200 | 403 | 403 | 403 | 403 | 403 | 403 | 403 |
| `/turnos`, `/turnos/create`, `/turnos/6` | 302→login | 200 | 403 | 403 | 403 | 403 | 403 | 403 | 403 |
| `/ganancias` | 302→login | 200 | 403 | 403 | 403 | 200 | 200 | 403 | 403 |
| `/suspensiones` | 302→login | 200 | 403 | 403 | 403 | 403 | 403 | 403 | 403 |
| `/mis-conceptos` | 302→login | 403 | 403 | 403 | 403 | 200 | 200 | 403 | 403 |
| `/mi-mesa` | 302→login | 403 | 200 | 200 | 200 | 403 | 403 | 403 | 403 |
| `/mi-cuenta`, `/mi-historial` | 302→login | 403 | 403 | 403 | 200 | 403 | 403 | 200 | 200 |
| `/notificaciones` | 302→login | 200 | 200 | 200 | 200 | 200 | 200 | 200 | 200 |

Operaciones de escritura (resultados §3 y §4).
- Los roles sin permiso reciben **403 antes de que se valide o se guarde nada**. Así ocurre con `POST /empleados`, `POST /jugadores`, `PUT /jugadores/1`, `POST /mesas`, `DELETE /mesas/1`, `POST /turnos`, `POST /turnos/6/asignaciones`, `POST /jugadores/4/abonos`, `POST /mis-conceptos/10`, `POST /rondas/8/apuestas` y `POST /rondas/8/cerrar`.
- Los roles permitidos que envían datos vacíos reciben el formulario con errores de validación.
- Las operaciones sobre registros ajenos con datos válidos también se rechazan, y se comprobó en la base que no cambiaron nada:
  - supervisor2 registra un concepto en una asignación de supervisor1 → 403;
  - crupier2 cierra la ronda de R-01 o apuesta en ella → 403;
  - crupier3 abre una ronda en B-01 → 403;
  - crupier1 anula una ronda de P-01 → 403;
  - admin o supervisor1 piden una pausa → 403;
  - jugador1 o supervisor1 finalizan una investigación → 403;
  - crupier1 registra un jugador → 403;
  - jugador1 marca como leída una notificación del admin → 404.

### 6.3. Recorrido por rol

**Administrador** (`admin`)
1. Entra en `/login` con `admin`.
2. Menú: Inicio · Empleados · Jugadores · Mesas · Turnos · Ganancias · Suspensiones. En la cabecera: «Notificaciones N», «admin · Administrador» y «Cerrar sesión» (AUD-03).
3. Puede:
   - gestionar empleados, jugadores, mesas, turnos y asignaciones;
   - consultar ganancias e historiales;
   - consultar suspensiones y finalizar investigaciones;
   - registrar abonos.
4. URL no autorizada: `/mi-mesa` o `/mis-conceptos` → página «403 No tienes permiso para ver esta página.».
5. Solicitud no autorizada: `POST /rondas/8/apuestas` → 403.
6. No puede abrir, cerrar ni registrar rondas o apuestas, ni registrar conceptos, ni cambiar el saldo de un jugador después del alta: el formulario de edición no tiene el campo y el servidor lo ignora (AUD-55, D-12).

**Crupier** (`crupier1`, `crupier2`, `crupier3`)
1. Entra con su usuario.
2. Menú: Inicio · Mi mesa (más Mi cuenta y Mi historial si tiene perfil de jugador, como crupier3).
3. Puede:
   - abrir, cerrar y anular rondas;
   - registrar, modificar y anular apuestas;
   - registrar apuestas de póker tras conocer el resultado.

   Todo **solo en la mesa que tiene asignada en el turno en curso**. Fuera de turno ve «No tienes una mesa asignada en el turno en curso.».
4. URL no autorizada: `/empleados` → 403 (AUD-15).
5. Solicitud no autorizada sobre otra mesa (`POST /rondas/8/cerrar` siendo crupier2) → 403.
6. En póker solo registra apuestas en rondas de su propia asignación. Una solicitud fabricada sobre una ronda de un turno anterior recibe 403 (DEF-01, corregido).

**Supervisor** (`supervisor1`, `supervisor2`)
1. Entra con su usuario.
2. Menú: Inicio · Mis conceptos · Ganancias (AUD-33).
3. Puede:
   - registrar el concepto de cada asignación que supervisó, una vez terminado el turno y una sola vez;
   - ver las ganancias solo de las rondas de sus asignaciones (AUD-36).
4. URL no autorizada: `/turnos` → 403.
5. Concepto en una asignación ajena → 403. Concepto en un turno sin terminar → «Solo se puede registrar el concepto después de terminar el turno.».
6. No puede modificar un concepto ya registrado: «El concepto ya fue registrado.».

**Jugador** (`jugador1`, `jugador2`, `jugador3`)
1. Entra con su usuario.
2. Menú: Inicio · Mi cuenta · Mi historial (AUD-37).
3. Puede:
   - ver su saldo y su estado;
   - pedir una pausa de 24 horas;
   - ver su historial y sus notificaciones.
4. URL no autorizada: `/jugadores/1` → 403.
5. Solicitud no autorizada: `POST /suspensiones/2/finalizar` → 403.
6. No puede registrar apuestas, cambiar su saldo ni ver datos de otros jugadores.

**Capturas que demuestran las diferencias entre roles:** AUD-03 (admin), AUD-20 (crupier), AUD-33 (supervisor), AUD-37 (jugador) y AUD-15 (403). Para repetirlas: entra con cada usuario y captura la página «Inicio» con el menú visible; después escribe en la barra `http://localhost:8010/empleados` con un usuario que no sea admin.

---

## 7. Checklist de los seis casos de uso posibles

P §5 exige al menos 3 casos de uso, y «gestión implica todo lo relativo a CRUD con las restricciones planteadas en el contexto».

| Caso de uso | Implementado | CRUD correspondiente | Roles | Pruebas | Estado |
|---|---|---|---|---|---|
| Gestión de mesas | Sí | Crear, listar, editar y borrar mesas. El borrado se bloquea si la mesa tiene asignaciones. Incluye la asignación a crupier por turno (turnos y asignaciones). | Administrador | `MesasTest` (3), `TurnosTest` (6), `AsignacionesTest` (8); AUD-07, 56-58, 59-65 | APROBADO |
| Gestión de rondas | Sí | Crear (abrir), consultar (Mi mesa), actualizar (cerrar con resultado) y borrar (anular una ronda abierta sin apuestas). | Crupier | `RondasTest` (7), `ResultadoTest` (5); AUD-20, 25-28 | APROBADO |
| Gestión de apuestas | Sí | Crear (registrar), consultar, actualizar (modificar una apuesta pendiente) y borrar (anular, con devolución del saldo). | Crupier | `ApuestasTest` (8), `ResultadoTest` (5); AUD-21-24, 29-32 | APROBADO (DEF-01 corregido) |
| Gestión de ganancias | Sí, como informe | Crear: automático al resolver apuestas. Consultar: informe por fechas y mesa. Editar y borrar no aplican: es un dato derivado de las apuestas. | Administrador; Supervisor (sus asignaciones) | `GananciasTest` (3); AUD-11, 36 | APROBADO (interpretación D-24) |
| Gestión de suspensiones | Parcial como caso de uso; completo como funcionalidad adicional 4 | Crear: automático (RN-14, RN-17, RN-18) y por pausa voluntaria. Consultar: lista. Actualizar: fin de investigación y abonos. No hay alta ni borrado manual por el Administrador. | Administrador, Jugador, sistema | `SuspensionesTest` (6), `PausaTest` (4), `AbonosTest` (12); AUD-10, 32, 41-48, 67-69 | PARCIAL como caso de uso (no se cuenta para el mínimo) |
| Consignaciones y retiros | No (D-12). Los abonos solo reducen la deuda de un empleado y no cambian el saldo. | — | — | — | No implementado; es opcional y no afecta el mínimo |

**Conclusión:**
- **Implementados**: 4 (mesas, rondas, apuestas y ganancias), más suspensiones como funcionalidad adicional.
- **Verificados en esta auditoría**: los 4.
- **Cumplen el mínimo obligatorio** (3): los 4.
- **Con defectos**: ninguno abierto. Apuestas (DEF-01) y la parte de turnos de mesas (DEF-02) tuvieron un defecto cada una, ya corregido.
- **No implementado**: consignaciones/retiros.

### 7.1. Gestión de mesas

- **A. Propósito.** Registrar las mesas con su juego y sus límites por jugada, y asignarlas por turno a un crupier y un supervisor competentes (C: «está asignada a un Crupier durante un turno específico»).
- **B. Roles.** Solo el Administrador: es configuración del casino.
- **C. Acceso.** Menú «Mesas» (`/mesas`). Para turnos: menú «Turnos» → «Ver asignaciones».
- **D. Procedimiento.** §11.10 (mesas), §11.11 (turnos) y §11.12 (asignaciones).
- **E. Resultado esperado.**
  - «Mesa registrada correctamente.» y la fila nueva en `mesas` (AUD-57).
  - «Asignación registrada correctamente.» y la fila en `asignaciones` (AUD-63).
- **F. Pruebas negativas, todas verificadas:**

  | Prueba | Mensaje | Captura |
  |---|---|---|
  | Mínimo mayor que el máximo | «El campo apuesta máxima debe ser mayor o igual que 50.» | AUD-56 |
  | Código repetido | Rechazado | `MesasTest` |
  | Borrar una mesa con asignaciones | «No se puede borrar una mesa con asignaciones registradas.» | — |
  | Cambiar el juego de una mesa con asignaciones | «No se puede cambiar el juego de una mesa con asignaciones.» | AUD-58 |
  | Turno de más de 2 horas | «La duración del turno no puede superar 120 minutos.» | AUD-59 |
  | Turno que cruza la medianoche | Rechazado | — |
  | Turno que se solapa con otro | Rechazado | — |
  | Turno contiguo al turno sembrado | Se acepta (antes de corregir DEF-02 se rechazaba) | AUD-65 (antes) |
  | RN-08: sin competencia en el juego | Rechazado | AUD-60 |
  | RN-05: crupier que repite mesa en la jornada | Rechazado | AUD-61 |
  | RN-04: crupier con dos mesas en el turno | Rechazado | AUD-62 |
  | RN-04: mesa repetida en el turno | Rechazado | — |
  | Editar o borrar un turno con asignaciones | Rechazado | AUD-64 |

- **G. Código.**
  - Mesas: `MesaController.php:11-53` y `MesaRequest.php:16-31`.
  - Turnos: `TurnoController.php:12-65` y `TurnoRequest.php:16-52`.
  - Asignaciones: `AsignacionController.php:14-33` y `AsignacionService.php:14-48`.
- **H. Evidencias.** AUD-07, AUD-08, AUD-09, AUD-56 a AUD-65.

### 7.2. Gestión de rondas

- **A. Propósito.** Registrar las partidas de cada mesa: número correlativo, hora de inicio, hora de cierre y resultado (C).
- **B. Roles.** El crupier asignado a la mesa en el turno en curso (D-14). Si una ronda quedó abierta al terminar un turno, la cierra el crupier del turno siguiente.
- **C. Acceso.** Menú «Mi mesa» (`/mi-mesa`).
- **D. Procedimiento.** §11.15 a §11.18.
- **E. Resultado esperado.**
  - Al abrir: «Ronda abierta correctamente.» y «Ronda N · Abierta», con N = máximo + 1 (AUD-27).
  - Al cerrar: «Ronda cerrada correctamente.» y «Resultado: … · Cerrada dd/mm/aaaa hh:mm». En la base quedan `hora_cierre` y `resultado`, y las apuestas resueltas (AUD-26).
- **F. Pruebas negativas.**

  | Prueba | Mensaje |
  |---|---|
  | Segunda ronda abierta en la misma mesa | «La mesa ya tiene una ronda abierta.» |
  | Anular una ronda con apuestas o cerrada | «Solo se anula una ronda abierta sin apuestas.» |
  | Cerrar otra vez una ronda cerrada | «La ronda ya está cerrada.» (verificado; no paga dos veces) |
  | Crupier de otra mesa o fuera de turno | 403 |

- **G. Código.**
  - `RondaController.php:15-65`.
  - `ResultadoService::cerrar` (`ResultadoService.php:15-40`).
  - `Asignacion::enCursoDelCrupier` (`Asignacion.php:21-34`).
- **H. Evidencias.** AUD-20, AUD-25, AUD-26, AUD-27, AUD-28.

### 7.3. Gestión de apuestas

- **A. Propósito.** Registrar las apuestas de los jugadores con validación estricta de los límites, el saldo y el estado del jugador. Resolverlas al cerrar la ronda.
- **B. Roles.** Crupier de la mesa en el turno en curso. El jugador no apuesta por sí mismo: C describe que el registro se hace en la mesa.
- **C. Acceso.** «Mi mesa» → formulario «Registrar apuesta» dentro de la ronda abierta. En póker: «Registrar apuesta con resultado conocido» dentro de una ronda cerrada.
- **D. Procedimiento.** §11.16 y §11.19.
- **E. Resultado esperado.**
  - «Apuesta registrada correctamente.»
  - La apuesta aparece como `CLI-001 · Rojo · 20.00 × 2.00 · Pendiente · Premio 0.00`.
  - El saldo del jugador baja en el monto (D-03).
  - Al cerrar: Ganada con premio = monto × factor acreditado, o Perdida.
- **F. Pruebas negativas, todas verificadas:**

  | Prueba | Mensaje | Captura |
  |---|---|---|
  | Monto bajo el mínimo o sobre el máximo | «El monto debe estar entre el mínimo y el máximo de la mesa.» | AUD-21 |
  | Saldo insuficiente | «El saldo del jugador es insuficiente.» | AUD-30 |
  | Código de cliente inexistente | «El código de cliente no existe.» | — |
  | Jugador suspendido | «El jugador está suspendido: Pausa voluntaria hasta …» | AUD-22 |
  | Empleado en investigación | «El jugador está suspendido: Investigación.» | AUD-31 |
  | Póker con la ronda abierta | «En este juego las apuestas se registran cuando se conoce el resultado.» | — |
  | Modificar o anular una apuesta resuelta (comprobado en el código; la interfaz no muestra esos botones) | Modificar: 404. Anular: «Solo se anulan apuestas pendientes de una ronda abierta.» | — |

- **G. Código.**
  - `ApuestaController.php:15-60`.
  - `ApuestaService.php:14-102`: validación en `:71-102`; límites en `:83-85`; bloqueo por suspensión en `:92-95`; saldo en `:96-99`.
  - `ApuestaRequest.php:16-22`.
- **H. Evidencias.** AUD-21 a AUD-24, AUD-29 a AUD-32.
- **Defecto corregido.** DEF-01 (§18): una ronda cerrada solo admite apuestas de la asignación que la abrió (`ApuestaController.php:60-61`).

### 7.4. Gestión de ganancias

- **A. Propósito.** Mostrar, por mesa y periodo, lo apostado, lo pagado en premios, lo consolidado a favor del casino (apuestas perdidas) y el neto (apostado − premios).
- **B. Roles.** El Administrador ve todas las mesas. El supervisor ve solo las rondas de las asignaciones que supervisó (D-24).
- **C. Acceso.** Menú «Ganancias» (`/ganancias`).
- **D. Procedimiento.** §11.13.
- **E. Resultado esperado.** Tabla con las columnas Mesa, Apuestas resueltas, Total apostado, Premios pagados, Consolidado casino y Neto casino, más la fila Total.
  - Verificado con el filtro `R-01`, de hoy, tras las pruebas: 3 apuestas, 110 apostado, 140 en premios, 40 consolidado y neto −30. Coincide con las apuestas registradas (50 + 20 a Rojo ganadas y 40 a Negro perdida).
- **F. Pruebas negativas.**
  - «Desde» posterior a «Hasta» → «El campo fecha desde debe ser una fecha anterior o igual a fecha hasta.».
  - Supervisor → no ve mesas ni rondas ajenas (`GananciasTest`).
- **G. Código.** `GananciaController.php:13-78` y `GananciaRequest.php:14-30`.
- **H. Evidencias.** AUD-11 (administrador) y AUD-36 (supervisor).

### 7.5. Gestión de suspensiones (como caso de uso: parcial)

Se cubre como funcionalidad adicional «Validación de autorizaciones/suspensiones» (§9.4 y §8.6).
- Implementado: suspensiones automáticas y por pausa, la lista con historial, el fin de investigación y los abonos que reactivan.
- No implementado: alta y borrado manual de suspensiones por el Administrador. C no lo pide; dice que el sistema suspende automáticamente.

### 7.6. Consignaciones y retiros

No implementado (D-12): el saldo inicial se fija al registrar al jugador y después solo cambia por apuestas y premios. No se inventa una guía para esta funcionalidad.

---

## 8. Auditoría de las reglas de negocio (C)

### 8.1. Jugadores

| Regla | Código | Prueba (datos) | Resultado observado | Estado |
|---|---|---|---|---|
| Código único de cliente | `JugadorStoreRequest.php:24`, índice único en `jugadores.codigo_cliente` | Registrar `CLI-001` otra vez. | «El valor del campo código de cliente ya está en uso.» (AUD-18) | APROBADO |
| Documento de identidad (obligatorio, único) | `JugadorStoreRequest.php:25`, índice único | `JugadoresTest::test_rn_01_codigo_y_documento_son_unicos` | Pasa. | APROBADO |
| Nombre completo | `JugadorStoreRequest.php:26` | `test_rn_01_campos_obligatorios` | Pasa. | APROBADO |
| Edad | `JugadorStoreRequest.php:27` (`min:18`), `jugadores.edad` | Registrar 17 años. | El navegador bloquea («El valor debe ser mayor o igual que 18.»). Sin esa validación, el servidor responde «El campo edad debe ser como mínimo 18.» (AUD-16). Con 18 se registra (AUD-19). | APROBADO |
| Saldo actual en fichas | `jugadores.saldo decimal(14,2)`, `JugadorStoreRequest.php:28` (≥ 0) | Alta con saldo 1500. | Saldo 1500.00; después solo cambia por apuestas (AUD-55: la edición no tiene el campo saldo). | APROBADO |
| Menores no participan | Igual que la edad, y no existe otro camino de alta. | — | Ningún jugador menor de 18 años puede existir. | APROBADO |

### 8.2. Mesas y empleados

| Regla | Código | Prueba (datos) | Resultado observado | Estado |
|---|---|---|---|---|
| Identificador único de mesa | `MesaRequest.php:20`, índice único `mesas.codigo` | `MesasTest::test_rn_03_codigo_unico_y_minimo_no_mayor_que_maximo` | Pasa. | APROBADO |
| Tipo de juego | `mesas.juego_id` → `juegos` (Ruleta, Blackjack, Póker) | Alta de R-02 «Ruleta». | Guardada (AUD-57). | APROBADO |
| Mínimo y máximo por jugada | `MesaRequest.php:20` (`gte:apuesta_minima`) | R-02 con mínimo 50 y máximo 10. | Rechazo (AUD-56); 10/100 aceptado. | APROBADO |
| Competencia del crupier | `AsignacionService.php:36-38` | crupier2 (sin Ruleta) en R-01. | «El crupier no tiene competencia en el juego de esta mesa.» (AUD-60) | APROBADO |
| Competencia del supervisor | `AsignacionService.php:39-41` | supervisor2 (sin Póker) en P-01. | «El supervisor no tiene competencia en el juego de esta mesa.» | APROBADO |
| Un crupier, una mesa por turno | `AsignacionService.php:30-32`, índice único (`turno_id`, `crupier_id`) | crupier1, que ya tiene B-01, también en R-02 en el turno de 12:00–14:00. | «El crupier ya tiene una mesa en este turno.» (AUD-62) | APROBADO |
| Una mesa, una asignación por turno | `AsignacionService.php:27-29`, índice único (`turno_id`, `mesa_id`) | B-01 dos veces en el mismo turno. | «La mesa ya tiene una asignación en este turno.» | APROBADO |
| Rotación: no repetir mesa en la jornada | `AsignacionService.php:33-35` | crupier1 en R-01 de nuevo el mismo día. | «El crupier no puede repetir mesa en la misma jornada.» (AUD-61) | APROBADO |
| Turnos de máximo 2 horas | `TurnoRequest.php:37-38` | 10:00–12:30. | «La duración del turno no puede superar 120 minutos.» (AUD-59); `TurnosTest` acepta 2 h y rechaza 2 h 1 min. | APROBADO |
| Turnos sin solape ni cruce de medianoche (D-10) | `TurnoRequest.php:32-33, 44-51` | 09:00–11:00 sobre el turno de 08:00–10:00; 23:00–00:30; turno contiguo 11:00–13:00 tras el sembrado de 09:00–11:00. | Solape y medianoche rechazados; el turno contiguo se acepta. En la auditoría el contiguo se rechazaba (AUD-65, DEF-02, corregido). | APROBADO |
| Concepto del supervisor al final del turno | `ConceptoController.php:24-56`, `ConceptoStoreRequest.php:18` | supervisor1, asignación terminada → «Sospecha de fraude». | «Concepto registrado correctamente.» (AUD-35). En un turno sin terminar: rechazo. Un segundo concepto: «El concepto ya fue registrado.». | APROBADO |
| Solo los cuatro conceptos permitidos | `enum Concepto` (`app/Enums/Concepto.php`) | La lista ofrece exactamente «Genera ganancias / Genera pérdidas / Sospecha de fraude / Comportamiento neutro»; otro valor → «El valor seleccionado para concepto no es válido.» | Así (AUD-34). | APROBADO |
| El concepto no se calcula a partir de las ganancias o pérdidas | Ningún código calcula el concepto: solo se guarda lo que elige el supervisor (`ConceptoController.php:36`). | Se registró «Sospecha de fraude» en un turno de P-01 donde el casino ganó. | Se guardó lo elegido, sin relación con el neto. | APROBADO |

### 8.3. Rondas

| Regla | Código | Observado | Estado |
|---|---|---|---|
| Asociada a una mesa | `rondas.mesa_id` (FK) | Cada ronda pertenece a la mesa de «Mi mesa». | APROBADO |
| Número correlativo | `RondaController.php:23`, índice único (`mesa_id`, `numero`) | R-01: 1, 2, 3 y luego 4 (AUD-27). Una ronda anulada libera su número, así que no quedan huecos. | APROBADO |
| Hora de inicio | `RondaController.php:28` | Se guarda al abrir. | APROBADO |
| Hora de cierre | `ResultadoService.php:30` | «Cerrada 01/11/2026 08:43» (AUD-26). | APROBADO |
| Relación ronda–apuestas | `apuestas.ronda_id` (FK `restrictOnDelete`) | Las apuestas se listan bajo su ronda; no se borra una ronda con apuestas. | APROBADO |

### 8.4. Apuestas

| Regla | Código | Prueba | Observado | Estado |
|---|---|---|---|---|
| Jugador, ronda, tipo, monto y factor | `ApuestaService.php:19-26` | CLI-001 · Rojo · 20 × 2 en la ronda 3 de R-01. | Guardada con los cinco datos. | APROBADO |
| Mínimo | `ApuestaService.php:83-85` | Monto 5 en R-01 (mínimo 10). | Rechazada (AUD-21). | APROBADO |
| Máximo | Ídem | Monto 600 en R-01 (máximo 500). | Rechazada. | APROBADO |
| Saldo suficiente | `ApuestaService.php:96-99` | CLI-001 con 1270 apuesta 1500 en P-01. | «El saldo del jugador es insuficiente.» (AUD-30) | APROBADO |
| Bloqueo por suspensión | `ApuestaService.php:92-95` → `AutorizacionJugadorService::motivoBloqueo` (`:17-31`) | CLI-002 (pausa). | Rechazada con el motivo y la fecha (AUD-22). | APROBADO |
| Bloqueo por investigación | Ídem | EMP-CLI-003 en investigación. | Rechazada (AUD-31). | APROBADO |
| Juegos sin registro detallado (póker) | `ApuestaService.php:74-76`, `:29-31`; `juegos.registro_detallado` (D-05) | Ronda de P-01 abierta → apuesta por `POST`. | «En este juego las apuestas se registran cuando se conoce el resultado.». Con la ronda cerrada: formulario con Estado Ganada/Perdida, que se procesa al guardar (AUD-28, AUD-29). Una ronda de un turno anterior da 403 (DEF-01, corregido). | APROBADO |

### 8.5. Resultados y ganancias

| Regla | Código | Prueba | Observado | Estado |
|---|---|---|---|---|
| El crupier registra el resultado | `RondaController.php:49-55`, `RondaCerrarRequest.php:16` | «Rojo 12» y marcar el tipo ganador «Rojo» (D-04). | Ronda cerrada con ese resultado (AUD-25, AUD-26). | APROBADO |
| Apuesta acertada → Ganada | `ResultadoService.php:31-35` | Dos apuestas a Rojo. | Ganada. | APROBADO |
| Apuesta fallida → Perdida | Ídem | Negro. | Perdida. | APROBADO |
| Premio = monto × factor | `ResultadoService.php:53` | 50 × 2 y 20 × 2. | Premio 100.00 y 40.00. | APROBADO |
| Acreditación inmediata al saldo | `ResultadoService.php:54-55` | CLI-001: 1050 − 20 + 100 + 40. | Saldo 1170.00 en la base. | APROBADO |
| Pérdidas a favor del casino | `GananciaController.php:50-52` (D-03: el monto se descuenta al apostar) | Negro 40 perdida. | «Consolidado casino» 40 en R-01. | APROBADO |
| Sin pagos duplicados | `ResultadoService.php:19-21`, `:46-48` | Reenviar el cierre de la ronda ya cerrada. | «La ronda ya está cerrada.»; el saldo no cambió. | APROBADO |

### 8.6. Suspensiones

Umbral diario: C dice «umbral configurado» y no da un número. La aplicación lo lee de `CASINO_UMBRAL_PERDIDA_DIARIA`; la VM usa 1000.00 como valor de ejemplo (D-09). No es un valor de los documentos.

| Regla | Código | Datos y pasos | Observado | Estado |
|---|---|---|---|---|
| Pérdida diaria mayor que el umbral → Suspendido 24 h | `AutorizacionJugadorService.php:64-66` (neta del día, D-06) | CLI-AUD-1 pierde 40 en R-01 y 1010 en P-01 (neto −1050 > 1000). | Apuesta guardada y cuenta «Suspendido: Pérdida diaria hasta 02/11/2026 08:44»; la siguiente apuesta se rechaza (AUD-32, AUD-41). Notificación al jugador y al administrador (AUD-42). | APROBADO |
| Pausa voluntaria → 24 h | `AutorizacionJugadorService.php:118-133` | jugador1 → «Solicitar pausa de 24 horas». | «Tu pausa de 24 horas comenzó.» y estado Suspendido (AUD-46); el crupier no pudo registrarle una apuesta (AUD-47). | APROBADO |
| Bloqueo durante las 24 horas | `suspensionesActivas` (`:152-160`) | — | Fin = inicio + 24 h. Que se levanta sola a las 24 h está cubierto por `SuspensionesTest::test_rn_14_tras_24_horas_vuelve_a_apostar` (simula el tiempo); no se puede ver en vivo en una sesión. | APROBADO (automática) |
| Empleado con pérdidas > 2 salarios → Suspendido | `AutorizacionJugadorService.php:73-76`, `deudaEmpleado` (`:41-49`) | Empleado de prueba con salario 100 (límite 200) pierde 250 en P-01. | Suspendido por «Pérdida de empleado»; la siguiente apuesta se rechaza (AUD-67). | APROBADO |
| Rehabilitación automática al subsanar la deuda | `evaluarTrasAbono` (`:83-100`), `registrarAbono` (`:102-116`) (D-07) | Abono de 100 → deuda 150 < 200. | «Abono registrado.» y suspensión levantada sola; notificaciones «Tu cuenta fue suspendida…» y «Un abono redujo la deuda y tu cuenta fue reactivada.» (AUD-68, AUD-69). | APROBADO |
| Empleado con ganancias > 1 salario → Investigación | `AutorizacionJugadorService.php:77-79`, `gananciaEmpleadoDesdeUltimaInvestigacion` (`:51-57`) | Tras finalizar la investigación de crupier3 (salario 1000), gana 600 × 3 en B-01 (neto +1200). | Vuelve a «Investigación» (AUD-45, AUD-48). | APROBADO |
| Bloqueo hasta que finalice la investigación | `motivoBloqueo` (prioriza la investigación); `SuspensionController::finalizar` (`:22-33`) (D-08) | EMP-CLI-003 en investigación → apuesta rechazada (AUD-31). El Administrador → «Finalizar investigación». | «Investigación finalizada.», el estado vuelve a Activo y llega la notificación de reactivación (AUD-43, AUD-44). | APROBADO |

---

## 9. Auditoría de las funcionalidades adicionales (P §6)

| Funcionalidad | Implementada | Qué requisito satisface | Rol | Estado |
|---|---|---|---|---|
| Interfaz de usuario (obligatoria) | Sí | P §6 | Todos | APROBADO |
| 1. Notificaciones | Sí | P §6.1 | Todos (propias) | APROBADO |
| 2. Gestión de empleados | Sí | P §6.2; C (crupieres, supervisores, competencias, salario) | Administrador | APROBADO |
| 3. Historial de clientes | Sí | P §6.3 | Administrador (todos), jugador (el propio) | APROBADO |
| 4. Validación de autorizaciones/suspensiones | Sí | P §6.4; C (autoexclusión y prevención de riesgo) | Sistema, Administrador, jugador | APROBADO |

Resultado: **4 de 4** funcionalidades adicionales, más la interfaz. Supera el mínimo de 3.

### 9.1. Notificaciones: verificación especial

Son notificaciones **reales y automáticas**: clases de Laravel que se guardan en la tabla `notifications` (canal `database`) cuando ocurre el evento, dentro de `DB::afterCommit`. No son mensajes estáticos.

| Evento | Quién lo genera (código) | Destinatario | Verificado en vivo |
|---|---|---|---|
| Apuesta resuelta (Ganada o Perdida) | `ResultadoService.php:61-63` → `ApuestaResuelta` | El jugador | Sí: jugador1 recibió «Tu apuesta de la ronda 3 fue resuelta: Ganada.» (AUD-40) |
| Cuenta suspendida automáticamente (pérdida diaria, deuda de empleado, investigación) | `AutorizacionJugadorService.php:166-173` → `CuentaSuspendida` | El jugador y **todos** los administradores | Sí: «La cuenta de Laura Prueba fue suspendida por Pérdida diaria hasta …» (AUD-42) y «Tu cuenta fue suspendida por Pérdida de empleado.» (AUD-69) |
| Cuenta reactivada por un abono | `AutorizacionJugadorService.php:94-98` → `CuentaReactivada` | El jugador-empleado | Sí (AUD-69) |
| Investigación finalizada | `SuspensionController.php:28-30` → `CuentaReactivada` | El jugador-empleado | Sí: «El Administrador finalizó la investigación y tu cuenta fue reactivada.» (AUD-44) |
| Concepto «Sospecha de fraude» | `ConceptoController.php:37-51` → `ConceptoSospechaFraude` | Todos los administradores | Sí: «Se registró una sospecha de fraude en la mesa P-01.» (AUD-42) |
| Pausa voluntaria | No notifica, por diseño (la pide el propio jugador) | — | `NotificacionesTest::test_fa_01_pausa_voluntaria_no_notifica` |
| Otro concepto (no fraude) | No notifica | — | `test_fa_01_otro_concepto_no_notifica` |

- **Dónde se muestran.** En el enlace «Notificaciones N» de la cabecera, donde N es el número de no leídas, y en `/notificaciones`, con «Marcar como leída» y «Marcar todas como leídas». Se comprobó que el contador pasó de 8 a 0 (AUD-40).
- **Se guardan.** En la copia de auditoría, la tabla `notifications` tenía 19 filas al empezar y 32 a mitad de las pruebas; cada evento de la tabla anterior añadió las suyas.
- **Cómo demostrarlo.**
  1. Cierra una ronda con apuestas (crupier).
  2. Entra como el jugador: el contador de la cabecera aumenta y el mensaje aparece arriba de la lista.

### 9.2. Gestión de empleados

- **Qué hace.**
  - Alta de empleados con su cuenta de acceso (usuario y contraseña SHA-256), rol, salario mensual y competencias por juego.
  - Edición: cambiar la contraseña es opcional.
  - Borrado, protegido: no se borra a sí mismo, ni a un empleado con perfil de jugador o con asignaciones.
  - «Registrar como jugador»: perfil de jugador vinculado al empleado (RN-16).
- **Requisito.** P §6.2; C: competencias, salario (RN-17, RN-18) y roles.
- **Rol y acceso.** Administrador → menú «Empleados».
- **Pruebas realizadas.**
  - Documento repetido → «El valor del campo documento ya está en uso.» (AUD-49).
  - Contraseñas distintas → «Las contraseñas no coinciden.».
  - Alta correcta (AUD-50) y edición de competencias (AUD-51).
  - El usuario creado pudo entrar.
  - Perfil de jugador vinculado (AUD-52).
  - Borrado bloqueado por el perfil de jugador o por asignaciones (AUD-53); borrado correcto tras quitar el perfil.
- **Código.** `EmpleadoController.php:14-91`, `EmpleadoStoreRequest.php`, `EmpleadoUpdateRequest.php` y `JugadorController.php:38-67`.
- **Estado.** APROBADO.

### 9.3. Historial de clientes

- **Qué hace.** Muestra:
  - los datos del jugador;
  - los totales de apuestas resueltas (cantidad, total apostado, total en premios y resultado neto);
  - todas sus apuestas con mesa, juego, ronda, tipo, monto, factor, estado y premio;
  - sus suspensiones;
  - sus abonos, si es empleado.
- **Rol y acceso.**
  - El Administrador ve el de cualquier jugador: «Jugadores» → «Historial» (AUD-13).
  - El jugador ve solo el suyo: «Mi historial» (AUD-39).
- **Prueba.** El historial de jugador1 tras las pruebas mostraba 8 apuestas resueltas, 520 apostado, 840 en premios y neto +320; su saldo era 1320 = 1000 + 320.
  - 1000 es el saldo inicial que el seeder le asigna antes de jugar sus apuestas de demostración, que lo dejan en 1050.
- **Código.** `JugadorController.php:24-36`, `MiHistorialController.php:12-31` y las vistas `jugadores/show.blade.php` y `jugadores/partials/historial.blade.php`.
- **Estado.** APROBADO.

### 9.4. Validación de autorizaciones y suspensiones

- **Qué hace.**
  - Antes de cada apuesta, comprueba si el jugador tiene una suspensión activa; si la tiene, la rechaza con el motivo y la fecha de fin.
  - Después de cada apuesta resuelta, evalúa RN-14, RN-17 y RN-18.
  - El jugador puede pedir una pausa.
  - El Administrador ve las suspensiones activas y el historial, finaliza investigaciones y registra abonos.
- **Rol y acceso.**
  - Sistema: automático.
  - Jugador: «Mi cuenta».
  - Administrador: «Suspensiones» y «Jugadores» → «Abonos».
- **Pruebas.** §8.6 (todas en vivo).
- **Código.** `AutorizacionJugadorService.php`, `SuspensionController.php`, `AbonoController.php` y `MiCuentaController.php`.
- **Estado.** APROBADO.

### 9.5. Funcionalidades presentes que no están en la lista de P §6

| Funcionalidad | Justificación |
|---|---|
| Turnos y asignaciones | C: las mesas se asignan a un crupier por turno, con supervisor, competencias, rotación y duración. Imprescindible. |
| Conceptos del supervisor («Mis conceptos») | C: «Al final de cada turno el supervisor emitirá un concepto». Imprescindible. |
| Registro de jugadores | C: «los jugadores deben registrarse obligatoriamente». Imprescindible. |
| Abonos de deuda de empleados | C: «en la medida en que se subsane la deuda … automáticamente podrá volver a jugar» (D-07). Justificado. |
| Mi cuenta (saldo, estado y pausa) | C: la pausa voluntaria la solicita el jugador. Justificado. |

---

## 10. Auditoría de alcance y complejidad

| Funcionalidad o componente | Existe | Solicitado | Indispensable | Evidencia | Evaluación |
|---|---|---|---|---|---|
| Login Zero Knowledge con SHA-256 | Sí | P §3, §4 | Sí | §5 | Obligatorio y correctamente implementado |
| Sesiones | Sí | P §4 | Sí | §5.3 | Obligatorio y correctamente implementado |
| Roles y middleware | Sí | P §4 | Sí | §6 | Obligatorio y correctamente implementado |
| Gestión de mesas, turnos y asignaciones | Sí | P §5.1; C | Sí | §7.1 | Obligatorio y correctamente implementado |
| Gestión de rondas | Sí | P §5.2 | Sí | §7.2 | Obligatorio y correctamente implementado |
| Gestión de apuestas | Sí | P §5.3 | Sí | §7.3 | Obligatorio y correctamente implementado (DEF-01 corregido) |
| Gestión de ganancias | Sí | P §5.4 | Sí | §7.4 | Obligatorio y correctamente implementado |
| Suspensiones, pausa y abonos | Sí | P §6.4; C | Sí | §8.6 | Obligatorio y correctamente implementado |
| Notificaciones | Sí | P §6.1 | Sí (elegida) | §9.1 | Adicional justificado |
| Gestión de empleados | Sí | P §6.2 | Sí (elegida) | §9.2 | Adicional justificado |
| Historial de clientes | Sí | P §6.3 | Sí (elegida) | §9.3 | Adicional justificado |
| Conceptos del supervisor | Sí | C | Sí | §8.2 | Obligatorio y correctamente implementado |
| Datos de demostración (seeder) | Sí | P §8 («datos de prueba») | Sí | §2.4 | Auxiliar técnicamente necesario |
| Pruebas automáticas (PHPUnit) | Sí | — | No, pero valiosas | §17 | Auxiliar técnicamente necesario |
| Tablas `jobs`, `job_batches`, `failed_jobs` y `cache` | Sí | — | No (las notificaciones no usan cola) | migraciones `0001_01_01_00000{1,2}` | Auxiliar: vienen de la plantilla de Laravel; inofensivas |
| Dependencias de desarrollo `laravel/pail` y `laravel/pao` | Sí | — | No | `composer.json` | Auxiliar: de la plantilla; no afectan la ejecución |
| Suscripciones, facturación, fidelización, integraciones externas, analítica, APIs, colas, Vite o npm | **No** | — | — | `composer.json`, rutas | Sin funciones fuera de alcance |

**Complejidad.**
- Dependencias de producción mínimas: `laravel/framework` y `laravel/tinker`.
- Sin JavaScript de terceros (un solo archivo propio, `public/js/zk.js`) y CSS propio (`public/css/app.css`).
- No se recomienda simplificar nada. Opcionalmente, para la entrega, se pueden dejar las migraciones de colas de la plantilla, porque son estándar en Laravel.

---

## 11. Manual paso a paso de todas las funcionalidades existentes

Inventario real, obtenido del menú (`layouts/partials/menu.blade.php`), las 54 rutas y las vistas:

| # | Funcionalidad | Rol | Ruta |
|---|---|---|---|
| 11.1 | Inicio de sesión | Todos | `/login` |
| 11.2 | Cierre de sesión | Todos | botón «Cerrar sesión» |
| 11.3 | Inicio y notificaciones | Todos | `/inicio`, `/notificaciones` |
| 11.4 | Registrar y listar empleados | Administrador | `/empleados` |
| 11.5 | Editar y borrar empleados | Administrador | `/empleados/{id}/edit` |
| 11.6 | Registrar y listar jugadores | Administrador | `/jugadores` |
| 11.7 | Registrar a un empleado como jugador | Administrador | `/jugadores/create?empleado={id}` |
| 11.8 | Editar, borrar y ver el historial de un jugador | Administrador | `/jugadores/{id}` |
| 11.9 | Abonos de deuda de empleados | Administrador | `/jugadores/{id}/abonos` |
| 11.10 | Mesas | Administrador | `/mesas` |
| 11.11 | Turnos | Administrador | `/turnos` |
| 11.12 | Asignaciones de un turno | Administrador | `/turnos/{id}` |
| 11.13 | Ganancias | Administrador, Supervisor | `/ganancias` |
| 11.14 | Suspensiones y fin de investigación | Administrador | `/suspensiones` |
| 11.15 | Abrir y anular una ronda | Crupier | `/mi-mesa` |
| 11.16 | Registrar una apuesta | Crupier | `/mi-mesa` |
| 11.17 | Modificar y anular una apuesta | Crupier | `/apuestas/{id}/editar` |
| 11.18 | Cerrar una ronda con resultado | Crupier | `/mi-mesa` |
| 11.19 | Póker: registrar una apuesta con resultado conocido | Crupier | `/mi-mesa` |
| 11.20 | Mis conceptos | Supervisor | `/mis-conceptos` |
| 11.21 | Mi cuenta y pausa voluntaria | Jugador (o empleado con perfil) | `/mi-cuenta` |
| 11.22 | Mi historial | Jugador (o empleado con perfil) | `/mi-historial` |

En todas las guías, «la aplicación» es `http://localhost:8010` (§2.2) y los datos de prueba son ficticios. Los botones de borrar empleados, borrar jugadores, pedir pausa y finalizar investigaciones abren un cuadro de confirmación del navegador: pulsa «Aceptar». Los de borrar mesas, turnos o asignaciones, y los de anular rondas o apuestas, no piden confirmación (OBS-07).

### 11.1. Inicio de sesión

- **Objetivo.** Entrar con un usuario; la contraseña nunca sale del navegador.
- **Rol.** Cualquiera.
- **Precondiciones.** Túnel abierto; conocer la contraseña de demostración (§2.2) o la de un usuario creado por ustedes.
- **Pasos.**
  1. Abre la aplicación: carga «Iniciar sesión».
  2. Escribe el usuario en «Usuario» (por ejemplo, `admin`).
  3. Escribe la contraseña en «Contraseña».
  4. Pulsa «Entrar».
- **Resultado esperado.** Página «Inicio» con «Sesión iniciada como admin (Administrador)» y el menú del rol.
- **Resultado observado.** Así, con los 9 usuarios (AUD-03, AUD-33, AUD-37).
- **Errores que se pueden comprobar.**
  - Contraseña equivocada → «Usuario o contraseña incorrectos.» (AUD-02).
  - Abrir la aplicación por la IP de la VM → aviso de que se requiere `https` o `localhost`.
- **Evidencia.** AUD-01, AUD-02, AUD-03.
- **Código.** `LoginController.php:14-40`, `AutenticacionZeroKnowledge.php` y `public/js/zk.js:47-74`.
- **Estado.** APROBADO.

### 11.2. Cierre de sesión

- **Objetivo.** Terminar la sesión.
- **Rol.** Cualquiera con sesión.
- **Pasos.**
  1. Pulsa «Cerrar sesión», arriba a la derecha.
  2. Escribe en la barra `http://localhost:8010/empleados`.
- **Resultado esperado.** Vuelve a «Iniciar sesión»; la URL protegida también lleva al login.
- **Resultado observado.** Así (AUD-04). Reutilizar la cookie anterior tampoco da acceso.
- **Código.** `LogoutController.php:12-19`.
- **Estado.** APROBADO.

### 11.3. Inicio y notificaciones

- **Objetivo.** Ver los avisos del sistema: apuestas resueltas, suspensiones, reactivaciones y sospechas de fraude.
- **Rol.** Cualquiera; cada uno ve las suyas.
- **Precondiciones.** Que haya ocurrido algún evento (§9.1).
- **Pasos.**
  1. Entra.
  2. Mira el número junto a «Notificaciones» en la cabecera: son las no leídas.
  3. Pulsa «Notificaciones».
  4. Pulsa «Marcar como leída» en un aviso, o «Marcar todas como leídas».
- **Resultado esperado.** «Notificación marcada como leída.» o «Todas las notificaciones fueron marcadas como leídas.», y el contador de la cabecera baja (a 0 en el segundo caso).
- **Resultado observado.** Así: jugador1 pasó de 8 a 0 (AUD-40).
- **Error que se puede comprobar.** Marcar una notificación ajena (por `POST` con su id) → 404.
- **Evidencia.** AUD-12 y AUD-42 (administrador), AUD-40 (jugador), AUD-44 y AUD-69 (empleado).
- **Código.** `NotificacionController.php:9-29`, `app/Notifications/*` y `notificaciones/index.blade.php`.
- **Estado.** APROBADO.

### 11.4. Registrar y listar empleados

- **Objetivo.** Dar de alta crupieres, supervisores y administradores con su cuenta, salario y competencias.
- **Rol.** Administrador.
- **Pasos.**
  1. Menú «Empleados»: tabla con Documento, Nombre, Rol, Salario mensual, Competencias, Perfil de jugador y Acciones (AUD-05).
  2. Pulsa «Registrar empleado».
  3. Llena «Documento», «Nombre completo», «Rol», «Salario mensual en fichas», marca las casillas de «Competencias por juego», y llena «Nombre de usuario», «Contraseña» y «Confirmar contraseña».
  4. Pulsa «Guardar empleado».
- **Datos de prueba.** Documento `A-EMP-01`; nombre `Diana Prueba`; rol Crupier; salario `2500`; competencias Ruleta; usuario `a_crupier`; contraseña de al menos 8 caracteres elegida por ustedes y anotada fuera del repositorio.
- **Resultado esperado.** «Empleado registrado correctamente.» y la fila nueva; el usuario puede entrar (§11.1).
- **Resultado observado.** Así (AUD-50); el usuario creado entró y vio «No tienes una mesa asignada en el turno en curso.».
- **Errores que se pueden comprobar.**
  - Documento ya usado (`EMP-crupier1`) → «El valor del campo documento ya está en uso.» (AUD-49).
  - Contraseñas distintas → «Las contraseñas no coinciden.».
  - Menos de 8 caracteres → «La contraseña debe tener al menos 8 caracteres.».
  - Usuario repetido → rechazo.
- **Código.** `EmpleadoController.php:21-41` y `EmpleadoStoreRequest.php:15-27`.
- **Estado.** APROBADO.

### 11.5. Editar y borrar empleados

- **Pasos para editar.**
  1. «Empleados» → «Editar» en la fila.
  2. Cambia los campos. «Nueva contraseña (opcional)» se puede dejar vacía para conservar la actual.
  3. Pulsa «Guardar empleado».
- **Resultado observado (editar).** «Empleado actualizado correctamente.»; se añadió la competencia Blackjack (AUD-51).
- **Pasos para borrar.** «Empleados» → «Borrar» → «Aceptar».
- **Resultado esperado (borrar).** «Empleado y cuenta borrados correctamente.» si no tiene perfil de jugador ni asignaciones.
- **Errores que se pueden comprobar.**
  - Borrar a `Crupier Uno` → «No se puede borrar el empleado porque tiene asignaciones registradas.» (AUD-53).
  - Borrar a un empleado con perfil de jugador → «Borra antes el perfil de jugador.».
  - Borrarse a sí mismo → «No puedes borrar el empleado de la sesión abierta.».
- **Código.** `EmpleadoController.php:43-91` y `EmpleadoUpdateRequest.php`.
- **Estado.** APROBADO.

### 11.6. Registrar y listar jugadores

- **Objetivo.** Registrar a los clientes (C exige registro previo) con su cuenta y saldo inicial.
- **Rol.** Administrador.
- **Pasos.**
  1. Menú «Jugadores»: tabla con Código, Documento, Nombre, Edad, Saldo, Es empleado, Estado y Acciones (AUD-06).
  2. Pulsa «Registrar jugador».
  3. Llena «Documento», «Nombre completo», «Código de cliente», «Edad», «Saldo inicial en fichas», «Nombre de usuario», «Contraseña» y «Confirmar contraseña».
  4. Pulsa «Guardar jugador».
- **Datos de prueba.** Documento `1020000101`; nombre `Laura Prueba`; código `CLI-B01`; edad `18`; saldo `1500`; usuario `b_jugador`; contraseña de al menos 8 caracteres.
- **Resultado esperado.** «Jugador registrado correctamente.» y la fila con Estado «Activo».
- **Resultado observado.** Así (AUD-19).
- **Errores que se pueden comprobar.**
  - Edad 17: el navegador lo impide («El valor debe ser mayor o igual que 18.»); el servidor también lo rechaza (AUD-16).
  - Código repetido (`CLI-001`) → «El valor del campo código de cliente ya está en uso.» (AUD-18).
  - Contraseña corta → AUD-17.
- **Verificación en la base (opcional).** §5.2: el valor guardado es SHA-256 de la contraseña.
- **Código.** `JugadorController.php:17-67` y `JugadorStoreRequest.php`.
- **Estado.** APROBADO.

### 11.7. Registrar a un empleado como jugador

- **Objetivo.** Que un empleado apueste en su tiempo libre como cualquier cliente (C, RN-16).
- **Rol.** Administrador.
- **Precondiciones.** Un empleado sin perfil de jugador.
- **Pasos.**
  1. «Empleados» → «Registrar como jugador» en su fila.
  2. La página indica «Perfil vinculado a … (documento …)»; llena «Código de cliente», «Edad» y «Saldo inicial en fichas».
  3. Pulsa «Guardar jugador».
- **Resultado esperado.** El jugador aparece con «Es empleado: Sí» y la acción «Abonos».
- **Resultado observado.** Así (AUD-52).
- **Estado.** APROBADO.

### 11.8. Editar, borrar y ver el historial de un jugador

- **Pasos.**
  1. «Jugadores» → «Historial»: datos, totales, apuestas, suspensiones y abonos (AUD-13).
  2. «Editar»: Documento, Nombre completo, Código de cliente, Edad, Nombre de usuario y «Nueva contraseña (opcional)». **No hay campo de saldo**: el saldo solo cambia por apuestas (AUD-55, D-12).
  3. «Borrar» → «Aceptar».
- **Resultado esperado.** Edición guardada; borrado solo si el jugador no tiene apuestas.
- **Resultado observado.**
  - `CLI-AUD-1`, con apuestas → «No se puede borrar un jugador que tiene apuestas registradas.» (AUD-54).
  - Un perfil sin apuestas → «Perfil de jugador borrado correctamente.».
- **Código.** `JugadorController.php:24-36, 69-115` y `JugadorUpdateRequest.php`.
- **Estado.** APROBADO.

### 11.9. Abonos de deuda de empleados

- **Objetivo.** Registrar pagos de un empleado-jugador para reducir su deuda (pérdidas netas). Si la deuda baja de 2 salarios, la suspensión se levanta sola (RN-17, D-07).
- **Rol.** Administrador.
- **Precondiciones.** Un jugador que sea empleado.
- **Pasos.**
  1. «Jugadores» → «Abonos» en la fila del empleado: muestra «Deuda actual», «Límite de dos salarios» y «Abonos anteriores».
  2. Escribe el monto en «Monto del abono».
  3. Pulsa «Registrar abono».
- **Datos de prueba.** Ver la prueba compartida C-04 (§14).
- **Resultado esperado.** «Abono registrado.», la deuda baja y, si queda en el límite o por debajo, la suspensión por pérdida de empleado termina y el empleado recibe una notificación.
- **Resultado observado.** Deuda de 250 con límite 200 → abono de 100 → deuda 150 → reactivado (AUD-68, AUD-69).
- **Errores que se pueden comprobar.**
  - Abono mayor que la deuda → «El abono debe ser mayor que cero y no superar la deuda actual.» (servidor).
  - Si la deuda es negativa (ganancias netas), el navegador bloquea el envío (OBS-01, AUD-66).
  - Abonos de un jugador no empleado → 403.
- **Código.** `AbonoController.php:12-28` y `AutorizacionJugadorService.php:83-116`.
- **Estado.** APROBADO.

### 11.10. Mesas

- **Objetivo.** Registrar las mesas con su juego y sus límites.
- **Rol.** Administrador.
- **Pasos.**
  1. Menú «Mesas»: Código, Juego, Apuesta mínima, Apuesta máxima y Acciones (AUD-07).
  2. Pulsa «Registrar mesa».
  3. Llena «Código», «Juego», «Apuesta mínima» y «Apuesta máxima».
  4. Pulsa «Guardar mesa».
  5. Para cambiar una mesa: «Editar» → «Guardar mesa». Para quitarla: «Borrar».
- **Datos de prueba.** `A-R02`, Ruleta, 10, 100.
- **Resultado esperado.** «Mesa registrada correctamente.» / «Mesa actualizada correctamente.» / «Mesa borrada correctamente.».
- **Resultado observado.** Así (AUD-57); se creó y borró la mesa `X-01`.
- **Errores que se pueden comprobar.**
  - Mínimo 50 y máximo 10 → «El campo apuesta máxima debe ser mayor o igual que 50.» (AUD-56).
  - Borrar R-01 → «No se puede borrar una mesa con asignaciones registradas.».
  - Cambiar el juego de R-01 → «No se puede cambiar el juego de una mesa con asignaciones.» (AUD-58).
- **Código.** `MesaController.php` y `MesaRequest.php`.
- **Estado.** APROBADO.

### 11.11. Turnos

- **Objetivo.** Definir los turnos del día (máximo 2 horas).
- **Rol.** Administrador.
- **Pasos.**
  1. Menú «Turnos»: Fecha, Inicio, Fin y Acciones (Ver asignaciones, Editar, Borrar) (AUD-08).
  2. Pulsa «Registrar turno».
  3. Llena «Fecha», «Hora de inicio» y «Hora de fin».
  4. Pulsa «Guardar turno».
- **Datos de prueba.** Hoy, desde la hora de fin del turno sembrado hasta 2 horas después (los turnos contiguos se aceptan).
- **Resultado esperado.** «Turno registrado correctamente.».
- **Resultado observado.** Así.
- **Errores que se pueden comprobar.**
  - 10:00–12:30 → «La duración del turno no puede superar 120 minutos.» (AUD-59).
  - 09:00–11:00 sobre el turno de 08:00–10:00 → «El turno se solapa con otro turno de la misma fecha.».
  - 23:00–00:30 → «La hora de fin debe ser posterior al inicio y no puede cruzar medianoche.».
  - Editar o borrar un turno con asignaciones → «No se puede editar un turno con asignaciones.» / «No se puede borrar un turno con asignaciones.» (AUD-64).
  - Un turno contiguo (10:00–12:00 después de 08:00–10:00) sí se acepta. Antes de corregir DEF-02 se rechazaba con un falso solape (AUD-65).
- **Código.** `TurnoController.php` y `TurnoRequest.php`.
- **Estado.** APROBADO (DEF-02 corregido).

### 11.12. Asignaciones de un turno

- **Objetivo.** Asignar a cada mesa un crupier y un supervisor competentes durante un turno.
- **Rol.** Administrador.
- **Precondiciones.** El turno existe.
- **Pasos.**
  1. «Turnos» → «Ver asignaciones»: tabla Mesa, Juego, Crupier, Supervisor y Concepto (AUD-09).
  2. En «Añadir asignación», elige «Mesa», «Crupier» y «Supervisor».
  3. Pulsa «Asignar».
  4. Para quitar una asignación sin rondas: «Borrar».
- **Datos de prueba** (turno nuevo del mismo día que el sembrado, respetando la rotación):

  | Mesa | Crupier | Supervisor |
  |---|---|---|
  | B-01 | Crupier Uno | Supervisor Dos |
  | R-01 | Crupier Tres | Supervisora Uno |
  | P-01 | Crupier Dos | Supervisora Uno |

- **Resultado esperado.** «Asignación registrada correctamente.».
- **Resultado observado.** Así (AUD-63); el borrado de una asignación sin rondas mostró «Asignación borrada correctamente.».
- **Errores que se pueden comprobar.**

  | Intento | Mensaje | Captura |
  |---|---|---|
  | R-01 con Crupier Dos | «El crupier no tiene competencia en el juego de esta mesa.» | AUD-60 |
  | P-01 con Supervisor Dos | «El supervisor no tiene competencia en el juego de esta mesa.» | — |
  | R-01 con Crupier Uno el mismo día | «El crupier no puede repetir mesa en la misma jornada.» | AUD-61 |
  | Crupier Uno en una segunda mesa del mismo turno | «El crupier ya tiene una mesa en este turno.» | AUD-62 |
  | Una mesa ya asignada | «La mesa ya tiene una asignación en este turno.» | — |
  | Borrar una asignación con rondas | Rechazado | — |

- **Código.** `AsignacionController.php` y `AsignacionService.php:14-48`.
- **Estado.** APROBADO.

### 11.13. Ganancias

- **Objetivo.** Conocer el resultado del casino por mesa y periodo.
- **Rol.** Administrador (todas las mesas) o Supervisor (sus asignaciones).
- **Pasos.**
  1. Menú «Ganancias». Por defecto muestra el día de hoy.
  2. Cambia «Desde», «Hasta» y, opcionalmente, «Mesa».
  3. Pulsa «Filtrar».
- **Datos de prueba.** Desde = hace 5 días, Hasta = hoy, Mesa = Todas.
- **Resultado esperado.** Tabla por mesa y fila Total, con Apuestas resueltas, Total apostado, Premios pagados, Consolidado casino y Neto casino.
- **Resultado observado.** Así (AUD-11, AUD-36). El filtro por R-01 coincide con las apuestas registradas (§7.4). El supervisor2 solo vio B-01 y R-01, que son las mesas que supervisó.
- **Error que se puede comprobar.** «Desde» posterior a «Hasta» → «El campo fecha desde debe ser una fecha anterior o igual a fecha hasta.».
- **Código.** `GananciaController.php` y `GananciaRequest.php`.
- **Estado.** APROBADO.

### 11.14. Suspensiones y fin de investigación

- **Objetivo.** Ver quién no puede apostar y por qué, y finalizar investigaciones.
- **Rol.** Administrador.
- **Pasos.**
  1. Menú «Suspensiones».
  2. En «Suspensiones activas» verás Jugador, Motivo, Inicio, Fin y Acción; abajo, el «Historial».
  3. En una fila con motivo «Investigación», pulsa «Finalizar investigación» → «Aceptar».
- **Resultado esperado.** «Investigación finalizada.»; la fila pasa al historial con fecha de fin; el empleado vuelve a «Activo» y recibe la notificación.
- **Resultado observado.** Así (AUD-43, AUD-44).
- **Error que se puede comprobar.** Las demás suspensiones no tienen botón: terminan solas (24 horas) o con abonos.
- **Código.** `SuspensionController.php:12-33` y `suspensiones/tabla.blade.php`.
- **Estado.** APROBADO.

### 11.15. Abrir y anular una ronda

- **Objetivo.** Iniciar una partida en la mesa asignada.
- **Rol.** Crupier con asignación en el turno en curso.
- **Pasos.**
  1. Menú «Mi mesa»: «R-01 · Ruleta», «Apuesta mínima: 10.00 · Máxima: 500.00» (AUD-20).
  2. Si no hay ronda abierta, pulsa «Abrir ronda».
  3. Para descartar una ronda abierta sin apuestas, pulsa «Anular ronda».
- **Resultado esperado.** «Ronda abierta correctamente.» con «Ronda N · Abierta», y «Ronda anulada correctamente.».
- **Resultado observado.** Así: la ronda 4 de R-01 se abrió y se anuló (AUD-27).
- **Errores que se pueden comprobar.**
  - Mientras haya una ronda abierta no aparece «Abrir ronda»; por `POST` → «La mesa ya tiene una ronda abierta.».
  - Anular una ronda con apuestas → «Solo se anula una ronda abierta sin apuestas.».
  - Fuera de turno → «No tienes una mesa asignada en el turno en curso.».
- **Código.** `RondaController.php:15-47`.
- **Estado.** APROBADO.

### 11.16. Registrar una apuesta (Ruleta, Blackjack)

- **Rol.** Crupier.
- **Precondiciones.** Ronda abierta en su mesa; jugador activo con saldo.
- **Pasos.**
  1. «Mi mesa» → sección «Registrar apuesta» de la ronda abierta.
  2. Llena «Código de cliente», «Tipo de apuesta» (texto libre: `Rojo`, `Pleno 17`, `Par`…), «Monto» y «Factor de pago».
  3. Pulsa «Registrar apuesta».
- **Datos de prueba.** `CLI-001`, `Rojo`, `20`, `2`.
- **Resultado esperado.** «Apuesta registrada correctamente.»; la apuesta aparece como «CLI-001 · Rojo · 20.00 × 2.00 · Pendiente · Premio 0.00» con «Modificar» y «Anular»; el saldo del jugador baja 20.
- **Resultado observado.** Así (AUD-23).
- **Errores que se pueden comprobar (todos observados).**

  | Datos | Mensaje | Captura |
  |---|---|---|
  | Monto `5` | «El monto debe estar entre el mínimo y el máximo de la mesa.» | AUD-21 |
  | Monto `600` | Mismo mensaje | — |
  | `CLI-002` | «El jugador está suspendido: Pausa voluntaria hasta …» | AUD-22 |
  | `CLI-999` | «El código de cliente no existe.» | — |
  | Saldo insuficiente | «El saldo del jugador es insuficiente.» | AUD-30 |

  Tras un rechazo, el formulario conserva lo escrito.
- **Código.** `ApuestaController.php:15-25` y `ApuestaService.php:14-35, 71-102`.
- **Estado.** APROBADO.

### 11.17. Modificar y anular una apuesta

- **Rol.** Crupier.
- **Precondiciones.** Apuesta pendiente en una ronda abierta.
- **Pasos para modificar.**
  1. Pulsa «Modificar» en la apuesta: página «Modificar apuesta», con «Jugador: CLI-…», «Tipo de apuesta», «Monto» y «Factor de pago» (AUD-24).
  2. Cambia los valores.
  3. Pulsa «Guardar».
- **Pasos para anular.** Pulsa «Anular» en la apuesta.
- **Resultado esperado.** «Apuesta modificada correctamente.» (el saldo se ajusta por la diferencia) y «Apuesta anulada correctamente.» (se devuelve el monto).
- **Resultado observado.** CLI-AUD-1: 1500 − 30 − 10 + 10 (anulación) − 10 (de 30 a 40) = 1460. Coincide con la base.
- **Código.** `ApuestaController.php:27-52` y `ApuestaService.php:37-69`.
- **Estado.** APROBADO.

### 11.18. Cerrar una ronda con resultado

- **Rol.** Crupier.
- **Precondiciones.** Ronda abierta, normalmente con apuestas.
- **Pasos.**
  1. En la ronda abierta, escribe el resultado en «Resultado» (por ejemplo, `Rojo 12`).
  2. En «Tipos ganadores», marca los tipos de apuesta que coinciden con el resultado (aparecen los tipos registrados en la ronda; D-04).
  3. Pulsa «Cerrar ronda».
- **Datos de prueba.** Resultado `Rojo 12`; marcar `Rojo`; no marcar `Negro`.
- **Resultado esperado.** «Ronda cerrada correctamente.».
  - Las apuestas a Rojo quedan «Ganada · Premio = monto × factor», y el premio se acredita al saldo.
  - Las demás quedan «Perdida · Premio 0.00».
  - Cada jugador recibe una notificación.
- **Resultado observado.** Así (AUD-25, AUD-26): Rojo 50 × 2 → premio 100; Rojo 20 × 2 → premio 40; Negro 40 → Perdida. Saldo de CLI-001: 1170.
- **Error que se puede comprobar.** Reenviar el cierre → «La ronda ya está cerrada.»; no se paga dos veces.
- **Código.** `RondaController.php:49-55` y `ResultadoService.php:15-67`.
- **Estado.** APROBADO.

### 11.19. Póker: registrar una apuesta con resultado conocido

- **Objetivo.** C: en juegos sin registro detallado, las apuestas se registran cuando se conoce el resultado.
- **Rol.** Crupier de P-01.
- **Pasos.**
  1. «Mi mesa» (P-01) → «Abrir ronda». Con la ronda abierta **no aparece** formulario de apuesta (AUD-28).
  2. Al terminar la mano, escribe el «Resultado» (por ejemplo, `Escalera de color`) y pulsa «Cerrar ronda».
  3. En la ronda cerrada aparece «Registrar apuesta con resultado conocido»: Código de cliente, Tipo de apuesta, Monto, Factor de pago y «Estado» (Ganada o Perdida).
  4. Pulsa «Registrar apuesta».
- **Datos de prueba.** `CLI-001`, `Mano ganadora`, `100`, `2`, Ganada.
- **Resultado esperado.** «Apuesta registrada correctamente.»; queda «Ganada · Premio 200.00» y el saldo se ajusta al instante (−100 + 200).
- **Resultado observado.** Así (AUD-29).
- **Errores que se pueden comprobar.**
  - Apuesta con la ronda abierta (por `POST`) → «En este juego las apuestas se registran cuando se conoce el resultado.».
  - Mínimo 50 y máximo 2000, saldo y suspensiones, igual que en §11.16.
- **Código.** `ApuestaController.php:15-25, 54-62`, `ApuestaService.php:29-31, 74-76` y `mi-mesa/index.blade.php`.
- **Estado.** APROBADO (DEF-01 corregido: solo rondas de la asignación en curso).

### 11.20. Mis conceptos (supervisor)

- **Objetivo.** Emitir el concepto subjetivo sobre el trabajo del crupier al terminar el turno.
- **Rol.** Supervisor.
- **Pasos.**
  1. Menú «Mis conceptos»: tabla Fecha, Turno, Mesa, Crupier y Concepto (AUD-34).
     - En los turnos en curso dice «Pendiente de finalizar turno».
     - En los turnos terminados sin concepto hay una lista «Seleccione concepto» y el botón «Registrar concepto».
  2. Elige «Genera ganancias», «Genera pérdidas», «Sospecha de fraude» o «Comportamiento neutro».
  3. Pulsa «Registrar concepto».
- **Datos de prueba.** El turno más antiguo de P-01 (hace 5 días) → «Sospecha de fraude».
- **Resultado esperado.** «Concepto registrado correctamente.»; la fila muestra el concepto y ya no tiene formulario. Si es «Sospecha de fraude», los administradores reciben una notificación.
- **Resultado observado.** Así (AUD-35, AUD-42).
- **Errores que se pueden comprobar.**
  - Concepto en un turno en curso (por `POST`) → «Solo se puede registrar el concepto después de terminar el turno.».
  - Segundo concepto → «El concepto ya fue registrado.».
  - Asignación de otro supervisor → 403.
- **Código.** `ConceptoController.php` y `ConceptoStoreRequest.php`.
- **Estado.** APROBADO.

### 11.21. Mi cuenta y pausa voluntaria

- **Objetivo.** Que el jugador consulte su saldo y su estado, y pida una pausa de 24 horas (autoexclusión).
- **Rol.** Jugador, o empleado con perfil de jugador.
- **Pasos.**
  1. Menú «Mi cuenta»: Código de cliente, Nombre completo, Saldo y Estado (AUD-38).
  2. Pulsa «Solicitar pausa de 24 horas» → «Aceptar».
- **Resultado esperado.** «Tu pausa de 24 horas comenzó.», el estado pasa a «Suspendido» con «Pausa voluntaria hasta …», y ningún crupier puede registrarle apuestas.
- **Resultado observado.** Así (AUD-46, AUD-47).
- **Errores que se pueden comprobar.**
  - Pedir pausa con otra suspensión activa → «Ya tienes una suspensión activa.».
  - Usuario sin perfil de jugador → 403.
- **Código.** `MiCuentaController.php` y `AutorizacionJugadorService.php:118-133`.
- **Estado.** APROBADO.

### 11.22. Mi historial

- **Objetivo.** Que el jugador vea todas sus apuestas, sus totales y sus suspensiones.
- **Rol.** Jugador, o empleado con perfil de jugador.
- **Pasos.** Menú «Mi historial»: «Datos del jugador», «Totales de apuestas resueltas», «Apuestas» (Fecha, Mesa, Juego, Ronda, Tipo, Monto, Factor, Estado y Premio) y «Suspensiones».
- **Resultado observado.** Datos correctos de jugador1 (AUD-39).
- **Error que se puede comprobar.** Un jugador no puede ver el historial de otro: `/jugadores/{id}` → 403.
- **Código.** `MiHistorialController.php`.
- **Estado.** APROBADO.

---

## 12. Plan de pruebas manuales — preparación y Persona A

### 12.1. Preparación conjunta (las dos personas, 15 minutos)

1. **Ventana de tiempo.** Las pruebas del crupier necesitan un turno en curso. Acuerden una franja de 2 horas y, justo al empezar, la Persona A vuelve a cargar los datos de demostración (§2.5).
   - El turno en curso irá desde la hora en punto de la carga hasta 2 horas después. Anoten esa hora de fin.
   - La Persona B debe terminar sus pruebas de crupier (B-01 a B-13) antes de esa hora.
2. **Acceso.**
   - Si los dos tienen acceso SSH a la VM, cada uno abre su túnel (§2.2) desde su PC.
   - Si solo un PC tiene la llave, trabajen en ese PC con dos navegadores distintos (por ejemplo, Chrome para A y Edge para B), o con una ventana normal y otra de incógnito: cada navegador mantiene su propia sesión.
3. **Usuarios.** Los 9 de §2.3, todos con la contraseña de `CASINO_DEMO_PASSWORD`. La Persona A creará además usuarios nuevos y entregará a B, en privado (no en el repositorio ni en el chat del grupo), la contraseña de los que B necesite.
4. **Registros que crea cada persona.**
   - A: todo con prefijo `A-` o `CLI-A…`.
   - B: no crea registros de configuración; usa los que A crea para B (`CLI-B01`, `CLI-B02`, `A-EMP-02`) y los sembrados.
5. **Datos que nadie debe modificar.**
   - Las contraseñas de los usuarios sembrados.
   - Las mesas R-01, B-01 y P-01 (límites y juego).
   - El turno en curso y sus tres asignaciones.
   - `CLI-001`: solo B lo usa, y su pausa es lo último que se prueba.
   - `EMP-CLI-003`: solo se toca en la prueba compartida C-03.
6. **Comprobar que no interfieren.**
   - Antes de cada prueba compartida, avísense por chat.
   - Si un saldo no coincide con §15, revisen el historial del jugador (§11.8): ahí aparece cada apuesta con su hora y su mesa.
7. **Registro.** Cada uno llena su parte de la hoja de resultados (§14.3) y guarda sus capturas en `docs/evidencias/sustentacion/` con el identificador de la prueba en el nombre (por ejemplo, `A-04-empleado-registrado.png`).

**Prueba compartida inicial C-01 (antes de empezar):** la Persona A registra, desde «Jugadores» → «Registrar jugador»:

| Código | Documento | Nombre | Edad | Saldo | Usuario |
|---|---|---|---|---|---|
| `CLI-B01` | `1020000101` | Bruno Prueba | 25 | 1500 | `b_jugador1` |
| `CLI-B02` | `1020000102` | Carla Prueba | 30 | 1500 | `b_jugador2` |

Además:
- en «Empleados» registra a `A-EMP-02`, «Mario Prueba», rol Crupier, **salario 100**, sin competencias, usuario `a_empleado2`;
- después, «Registrar como jugador»: código `EMP-CLI-A02`, edad 30, saldo 1000.

Por último, comunica a B que están creados y le pasa en privado las contraseñas de `b_jugador2` y `a_empleado2`.

### 12.2. Plan de la Persona A (administración, configuración, supervisión y seguridad)

| Id | Objetivo | Rol | Datos previos | Pasos | Resultado esperado | Captura | Criterio de aprobación |
|---|---|---|---|---|---|---|---|
| A-01 | Login y rechazo | — / Administrador | Usuarios sembrados | 1. Abrir `/login`. 2. `admin` + contraseña inventada → «Entrar». 3. `admin` + contraseña real → «Entrar». | 2: «Usuario o contraseña incorrectos.» 3: «Inicio», «Sesión iniciada como admin (Administrador)» y menú de 7 opciones. | A-01a (error), A-01b (inicio) | Los dos resultados aparecen exactamente así. |
| A-02 | Zero Knowledge visible | Administrador | — | 1. F12 → pestaña Red. 2. Cerrar sesión y volver a entrar. 3. Abrir la solicitud `POST login` → Carga útil. | Solo `_token`, `nonce`, `respuesta` (64 hexadecimales) y `username`. | A-02 | No aparece la contraseña. |
| A-03 | Cierre de sesión | Administrador | Sesión abierta | 1. «Cerrar sesión». 2. Escribir `localhost:8010/empleados`. | Vuelve a «Iniciar sesión». | A-03 | No se ve la página de empleados. |
| A-04 | Roles: 403 en el servidor | Supervisor, Jugador | — | 1. Entrar como `supervisor1` y abrir `/empleados`, `/turnos` y `/mi-mesa`. 2. Entrar como `jugador1` y abrir `/jugadores/1` y `/ganancias`. | «403 No tienes permiso para ver esta página.» en todas. | A-04 | Las cinco dan 403. |
| A-05 | Alta de empleado y validaciones | Administrador | — | 1. «Empleados» → «Registrar empleado». 2. Documento `EMP-crupier1` (repetido), resto válido → Guardar. 3. Documento `A-EMP-01`, contraseñas distintas → Guardar. 4. Contraseñas iguales de al menos 8 caracteres, rol Crupier, salario 2500, competencia Ruleta, usuario `a_crupier` → Guardar. | 2: «El valor del campo documento ya está en uso.» 3: «Las contraseñas no coinciden.» 4: «Empleado registrado correctamente.» y la fila. | A-05 | Los tres mensajes y la fila nueva. |
| A-06 | Editar empleado y usar la cuenta | Administrador, Crupier | A-05 | 1. «Editar» `A-EMP-01` → marcar también Blackjack, dejar la contraseña vacía → Guardar. 2. Cerrar sesión y entrar como `a_crupier`. 3. «Mi mesa». | 1: «Empleado actualizado correctamente.», competencias «Ruleta, Blackjack». 2: entra. 3: «No tienes una mesa asignada en el turno en curso.». | A-06 | La cuenta funciona y no tiene mesa. |
| A-07 | Verificar SHA-256 del usuario creado | — (terminal) | A-05 | Ejecutar el comando de §5.2 con `a_crupier` en lugar de `jugador4` y escribir la contraseña cuando la pida. | `coincide` | A-07 (terminal, sin la contraseña) | Imprime `coincide`. |
| A-08 | Borrados protegidos | Administrador | Datos sembrados | 1. «Empleados» → «Borrar» en Crupier Uno → Aceptar. 2. «Jugadores» → «Borrar» en `CLI-001` → Aceptar. 3. «Empleados» → «Borrar» en `A-EMP-01` → Aceptar. | 1: «No se puede borrar el empleado porque tiene asignaciones registradas.» 2: «No se puede borrar un jugador que tiene apuestas registradas.» 3: «Empleado y cuenta borrados correctamente.» | A-08 | Los tres mensajes. |
| A-09 | Alta de jugador y validaciones | Administrador | — | 1. «Registrar jugador» con código `CLI-A01`, edad 17. 2. Código `CLI-001` (repetido), edad 18. 3. Contraseña `corta`. 4. Datos válidos: `CLI-A01`, documento `1020000201`, «Ana Prueba», 18, saldo 500, usuario `a_jugador`. | 1: el navegador no deja guardar (mínimo 18). 2: «El valor del campo código de cliente ya está en uso.» 3: «La contraseña debe tener al menos 8 caracteres.» 4: «Jugador registrado correctamente.», Estado Activo. | A-09 | Los cuatro resultados. |
| A-10 | Editar jugador sin tocar el saldo | Administrador | A-09 | «Editar» `CLI-A01` → edad 19 → Guardar. | Se guarda; el formulario no tiene campo de saldo y el saldo sigue en 500. | A-10 | Edad 19, saldo 500. |
| A-11 | Mesas | Administrador | — | 1. «Registrar mesa» `A-R02`, Ruleta, mínimo 50, máximo 10. 2. Corregir a 10 y 100 → Guardar. 3. «Editar» `A-R02` → máximo 200. 4. «Registrar mesa» `A-X01`, Blackjack, 20/200, y luego «Borrar» `A-X01`. 5. «Borrar» R-01. 6. «Editar» R-01 → juego Blackjack → Guardar. Conserva `A-R02` para A-13. | 1: «El campo apuesta máxima debe ser mayor o igual que 50.» 2-4: registrada, actualizada, registrada y borrada. 5: «No se puede borrar una mesa con asignaciones registradas.» 6: «No se puede cambiar el juego de una mesa con asignaciones.» | A-11 | Todos los mensajes. |
| A-12 | Turnos | Administrador | Hora de fin F del turno sembrado | 1. «Registrar turno»: hoy, F a F+2 h 30 min. 2. Hoy, F−1 h a F+1 h. 3. Hoy, 23:00 a 00:30. 4. Hoy, F a F+2 h (contiguo al sembrado). | 1: «… no puede superar 120 minutos.» 2: «… se solapa …». 3: «… no puede cruzar medianoche.». 4: «Turno registrado correctamente.» (comprueba la corrección de DEF-02). | A-12 (paso 4) | Los cuatro resultados. |
| A-13 | Asignaciones y reglas | Administrador | Turno del paso A-12.4 | En «Ver asignaciones» de ese turno, «Asignar»: 1. R-01 + Crupier Dos + Supervisora Uno. 2. P-01 + Crupier Dos + Supervisor Dos. 3. R-01 + Crupier Uno + Supervisora Uno. 4. B-01 + Crupier Uno + Supervisor Dos. 5. R-01 + Crupier Tres + Supervisora Uno. 6. A-R02 + Crupier Uno + Supervisora Uno. 7. B-01 + Crupier Dos + Supervisor Dos. 8. P-01 + Crupier Dos + Supervisora Uno. | 1: sin competencia (crupier). 2: sin competencia (supervisor). 3: «no puede repetir mesa en la misma jornada». 4 y 5: registradas. 6: «El crupier ya tiene una mesa en este turno.» 7: «La mesa ya tiene una asignación en este turno.» (también avisa que Crupier Dos repite B-01). 8: registrada. | A-13 (tabla final) | Tres asignaciones finales y los cinco rechazos. |
| A-14 | Turno con asignaciones | Administrador | A-13 | «Turnos» → «Editar» y luego «Borrar» en el turno de A-12.4. | «No se puede editar un turno con asignaciones.» / «No se puede borrar un turno con asignaciones.» | A-14 | Ambos mensajes. |
| A-15 | Conceptos del supervisor | Supervisor, Administrador | Turnos sembrados de hace 3 a 5 días | 1. Entrar como `supervisor1` → «Mis conceptos». 2. En el turno de hoy: dice «Pendiente de finalizar turno». 3. En el turno más antiguo de P-01: «Sospecha de fraude» → «Registrar concepto». 4. Entrar como `admin` → «Notificaciones». | 3: «Concepto registrado correctamente.» 4: «Se registró una sospecha de fraude en la mesa P-01.». En «Turnos» → turno de ese día, la columna Concepto lo muestra. | A-15a, A-15b | El concepto queda y llega la notificación. |
| A-16 | Ganancias | Administrador, Supervisor | Después de las pruebas B-05 y B-09 | 1. `admin` → «Ganancias», Desde = Hasta = hoy, Mesa R-01 → «Filtrar». 2. Desde = mañana, Hasta = hoy. 3. `supervisor2` → «Ganancias». | 1: valores de §15.3. 2: «El campo fecha desde debe ser una fecha anterior o igual a fecha hasta.» 3: solo mesas que supervisó. | A-16 | Coinciden con §15.3. |
| A-17 | Historial del cliente | Administrador | Después de B-09 | «Jugadores» → «Historial» de `CLI-001`. | Saldo y apuestas de §15.2; totales coherentes. | A-17 | Coincide con §15.2. |

---

## 13. Plan de pruebas manuales — Persona B (operación del casino, juego y validaciones)

Todas las pruebas de crupier deben hacerse **antes de la hora de fin del turno en curso**. Para un resultado reproducible, usa exactamente los datos indicados. B no crea configuración: usa los jugadores que A creó en C-01.

| Id | Objetivo | Rol | Datos previos | Pasos | Resultado esperado | Captura | Criterio de aprobación |
|---|---|---|---|---|---|---|---|
| B-01 | Ver la mesa asignada | Crupier (`crupier1`) | Datos recién cargados | 1. Entrar como `crupier1`. 2. «Mi mesa». | «R-01 · Ruleta», «Apuesta mínima: 10.00 · Máxima: 500.00», «Ronda 3 · Abierta» con «CLI-001 · Rojo · 50.00 × 2.00 · Pendiente». | B-01 | Se ve así. |
| B-02 | Registrar una apuesta válida | Crupier | B-01 | En «Registrar apuesta»: `CLI-001`, `Rojo`, `20`, `2` → «Registrar apuesta». | «Apuesta registrada correctamente.» y la apuesta pendiente en la lista. | B-02 | Aparece la apuesta. |
| B-03 | Validaciones de la apuesta | Crupier | B-01 | Registrar, una a una: 1. `CLI-001`, `Pleno 17`, `5`, `36`. 2. Igual con monto `600`. 3. `CLI-999`, `Rojo`, `20`, `2`. 4. `CLI-002`, `Rojo`, `20`, `2`. 5. `CLI-003`, `Rojo`, `20`, `2`. | 1 y 2: «El monto debe estar entre el mínimo y el máximo de la mesa.» 3: «El código de cliente no existe.» 4: «El jugador está suspendido: Pausa voluntaria hasta …» 5: «El jugador está suspendido: Pérdida diaria hasta …». El formulario conserva lo escrito. | B-03 (paso 4) | Los cinco rechazos; ninguna apuesta nueva. |
| B-04 | Modificar y anular | Crupier | C-01 | 1. `CLI-B01`, `Negro`, `30`, `2`. 2. `CLI-B01`, `Pleno 17`, `10`, `36`. 3. «Anular» en Pleno 17. 4. «Modificar» en Negro → Monto `40` → «Guardar». | 1-2: registradas. 3: «Apuesta anulada correctamente.» 4: «Apuesta modificada correctamente.» y «Negro · 40.00 × 2.00». | B-04 | Saldo de CLI-B01 = 1460 (§15.2). |
| B-05 | Cerrar la ronda y pagar | Crupier | B-02, B-04 | 1. Abrir «Mi mesa» también en una **segunda pestaña** y dejarla quieta. 2. En la primera: «Resultado» `Rojo 12`, marcar solo «Rojo» → «Cerrar ronda». | «Ronda cerrada correctamente.»; Rojo 50 → «Ganada · Premio 100.00»; Rojo 20 → «Ganada · Premio 40.00»; Negro 40 → «Perdida · Premio 0.00». | B-05 | Premios = monto × factor; saldo de CLI-001 = 1170. |
| B-06 | Sin pago duplicado | Crupier | B-05 | En la segunda pestaña (todavía con el formulario de cierre): escribir `Rojo 12`, marcar Rojo → «Cerrar ronda». | «La ronda ya está cerrada.»; el saldo de CLI-001 sigue en 1170. | B-06 | No cambia ningún saldo. |
| B-07 | Ronda correlativa y anulación | Crupier | B-05 | 1. «Abrir ronda». 2. «Anular ronda». | 1: «Ronda abierta correctamente.» y «Ronda 4 · Abierta». 2: «Ronda anulada correctamente.». | B-07 | Número 4 = siguiente al 3. |
| B-08 | Vista del jugador | Jugador (`jugador1`) | B-05 | 1. Entrar como `jugador1`. 2. «Mi cuenta». 3. «Mi historial». 4. «Notificaciones» → «Marcar todas como leídas». | 2: saldo 1170.00, estado Activo. 3: las apuestas de B-05 resueltas. 4: avisos «Tu apuesta de la ronda 3 fue resuelta: Ganada.»; el contador queda en 0. | B-08 | Coincide con §15.2. |
| B-09 | Póker y saldo insuficiente | Crupier (`crupier3`) | — | 1. Entrar como `crupier3` → «Mi mesa» (P-01) → «Abrir ronda»: no hay formulario de apuesta. 2. «Resultado» `Escalera de color` → «Cerrar ronda». 3. En «Registrar apuesta con resultado conocido»: `CLI-001`, `Mano ganadora`, `100`, `2`, Estado Ganada → «Registrar apuesta». 4. `CLI-001`, `Mano perdida`, `1500`, `1`, Perdida. | 1: sin formulario mientras está abierta. 3: «Ganada · Premio 200.00». 4: «El saldo del jugador es insuficiente.» | B-09 | Saldo de CLI-001 = 1270. |
| B-10 | Suspensión por pérdida diaria (RN-14) | Crupier, Jugador | C-01 | 1. Como `crupier3`, en la misma ronda de póker: `CLI-B02`, `Mano perdida`, `1010`, `1`, Perdida. 2. `CLI-B02`, `Mano ganadora`, `50`, `2`, Ganada. 3. Entrar como `b_jugador2` → «Mi cuenta» y «Notificaciones». | 1: registrada. 2: «El jugador está suspendido: Pérdida diaria hasta …» (24 h). 3: Estado «Suspendido» y aviso «Tu cuenta fue suspendida por Pérdida diaria hasta …». | B-10 | La segunda apuesta se rechaza. |
| B-11 | Empleado en investigación (RN-18) | Crupier | Datos sembrados | Como `crupier3`: `EMP-CLI-003`, `Mano ganadora`, `100`, `2`, Ganada. | «El jugador está suspendido: Investigación.» | B-11 | Rechazada. |
| B-12 | Límites del crupier | Crupier (`crupier2`) | — | 1. Entrar como `crupier2` → «Mi mesa». 2. Abrir `localhost:8010/empleados`. | 1: solo B-01 · Blackjack. 2: 403. | B-12 | Solo su mesa; 403. |
| B-13 | Pausa voluntaria (RN-15) — **la última de jugador1** | Jugador, Crupier | B-09 terminado | 1. `jugador1` → «Mi cuenta» → «Solicitar pausa de 24 horas» → Aceptar. 2. `crupier1` → «Mi mesa» → «Abrir ronda» → apuesta `CLI-001`, `Rojo`, `20`, `2`. 3. «Anular ronda». | 1: «Tu pausa de 24 horas comenzó.», Estado Suspendido. 2: «El jugador está suspendido: Pausa voluntaria hasta …». 3: ronda anulada. | B-13 | La apuesta se rechaza. |

---

## 14. Pruebas compartidas y secuencia de ejecución

### 14.1. Pruebas compartidas

| Id | Qué demuestra | Orden y responsables | Qué se comunican |
|---|---|---|---|
| C-01 | Datos de B creados por el Administrador | A crea `CLI-B01`, `CLI-B02` y `A-EMP-02` con su perfil `EMP-CLI-A02` (§12.1) → B confirma, desde `crupier1`, que una apuesta de `CLI-B01` se registra (B-04). | A → B: «Jugadores creados» y las dos contraseñas en privado. |
| C-02 | El cierre de ronda alimenta ganancias e historial | B hace B-05 → A hace A-16 (paso 1) y A-17. | B → A: «Ronda 3 de R-01 cerrada a las hh:mm». |
| C-03 | Fin de investigación y nueva investigación (RN-18) | 1. B hace B-11 (rechazo). 2. A: «Suspensiones» → «Finalizar investigación» de EMP-CLI-003 → Aceptar. 3. B entra como `crupier3` → «Mi cuenta» (Activo) y «Notificaciones» («El Administrador finalizó la investigación y tu cuenta fue reactivada.»). 4. B entra como `crupier2` → «Mi mesa» (B-01) → «Abrir ronda» → `EMP-CLI-003`, `21`, `600`, `3` → «Resultado» `21`, marcar `21` → «Cerrar ronda». 5. A → «Suspensiones» y «Notificaciones». | B → A: «B-11 hecho». A → B: «Investigación finalizada». B → A: «Ronda de B-01 cerrada». | 4: «Ganada · Premio 1800.00». 5: EMP-CLI-003 de nuevo en «Investigación» (indefinido) y aviso «La cuenta de Crupier Tres fue suspendida por Investigación.». |
| C-04 | Deuda de empleado y reactivación por abono (RN-17) | 1. B, como `crupier3`, en la ronda de póker cerrada: `EMP-CLI-A02`, `Mano perdida`, `250`, `1`, Perdida → luego otra de `50` → rechazo «Pérdida de empleado». 2. A: «Jugadores» → «Abonos» de `EMP-CLI-A02`: «Deuda actual: 250.00», «Límite de dos salarios: 200.00» → abono `300` → rechazo → abono `100` → «Abono registrado.», deuda 150. 3. B entra como `a_empleado2` → «Notificaciones». | B → A: «Pérdida de 250 registrada». A → B: «Abono hecho». | 1: suspensión. 2: el abono de 300 da «El abono debe ser mayor que cero y no superar la deuda actual.» (el navegador puede bloquearlo antes por el máximo); el de 100 reactiva. 3: «Tu cuenta fue suspendida por Pérdida de empleado.» y «Un abono redujo la deuda y tu cuenta fue reactivada.». |
| C-05 | El Administrador se entera de la suspensión automática | Tras B-10, A → «Notificaciones» y «Suspensiones». | B → A: «B-10 hecho». | «La cuenta de Carla Prueba fue suspendida por Pérdida diaria hasta …» y la fila en «Suspensiones activas». |
| C-06 | La pausa se ve en el Administrador | Tras B-13, A → «Suspensiones». | B → A: «B-13 hecho». | Fila «CLI-001 · Ana Jugadora · Pausa voluntaria». No hay notificación al Administrador (por diseño). |

### 14.2. Secuencia recomendada

| Momento | Persona A | Persona B |
|---|---|---|
| T0 (inicio) | §12.1: cargar los datos y anotar la hora de fin F; C-01 | Abrir el túnel o el navegador; revisar §2.3 |
| T0 + 15 min | A-01 a A-14, en orden. Usa solo registros `A-` y el turno nuevo de A-12; **no** toca el turno en curso | B-01 a B-07, en orden; luego B-08 |
| T0 + 50 min | C-02 (A-16 paso 1 y A-17), C-05 | B-09, B-10, B-11 |
| T0 + 65 min | C-03 (paso 2), C-04 (paso 2) | C-03 (pasos 1, 3 y 4), C-04 (pasos 1 y 3) — **antes de F** |
| T0 + 85 min | A-15, A-16 (pasos 2 y 3), C-06 | B-12, B-13 (al final) |
| Cierre | Revisar la hoja de resultados juntos | Ídem |

Dependencias que obligan a este orden:
- Toda apuesta necesita un turno en curso, una mesa asignada al crupier, una ronda (abierta, o cerrada en póker) y un jugador activo con saldo.
- El jugador lo crea A en C-01; el turno, la asignación y la ronda abierta de R-01 vienen del seeder.

### 14.3. Hoja de resultados

| Prueba | Responsable | Resultado esperado | Resultado observado | Evidencia | Estado |
|---|---|---|---|---|---|
| C-01 | A, B | Jugadores y empleado de prueba creados | | | Pendiente |
| A-01 | A | Login rechazado y aceptado | | | Pendiente |
| A-02 | A | La carga útil no contiene la contraseña | | | Pendiente |
| A-03 | A | Tras el cierre, la URL protegida pide login | | | Pendiente |
| A-04 | A | 403 en cinco URL ajenas | | | Pendiente |
| A-05 | A | 2 rechazos y alta del empleado | | | Pendiente |
| A-06 | A | Edición y uso de la cuenta nueva | | | Pendiente |
| A-07 | A | `coincide` (SHA-256) | | | Pendiente |
| A-08 | A | 2 borrados bloqueados y 1 permitido | | | Pendiente |
| A-09 | A | 3 rechazos y alta del jugador | | | Pendiente |
| A-10 | A | Edad cambiada, saldo igual | | | Pendiente |
| A-11 | A | Validación, CRUD y bloqueos de mesas | | | Pendiente |
| A-12 | A | 3 rechazos y 1 turno contiguo registrado | | | Pendiente |
| A-13 | A | 5 rechazos y 3 asignaciones | | | Pendiente |
| A-14 | A | Turno con asignaciones bloqueado | | | Pendiente |
| A-15 | A | Concepto registrado y notificación | | | Pendiente |
| A-16 | A | Ganancias = §15.3; rango inválido rechazado | | | Pendiente |
| A-17 | A | Historial = §15.2 | | | Pendiente |
| B-01 | B | Mesa R-01 y ronda 3 abierta | | | Pendiente |
| B-02 | B | Apuesta registrada | | | Pendiente |
| B-03 | B | 5 rechazos | | | Pendiente |
| B-04 | B | Anulación y modificación; CLI-B01 = 1460 | | | Pendiente |
| B-05 | B | Premios 100 y 40; CLI-001 = 1170 | | | Pendiente |
| B-06 | B | «La ronda ya está cerrada.» | | | Pendiente |
| B-07 | B | Ronda 4 abierta y anulada | | | Pendiente |
| B-08 | B | Vista del jugador coherente | | | Pendiente |
| B-09 | B | Póker: premio 200; saldo insuficiente | | | Pendiente |
| B-10 | B | CLI-B02 suspendido 24 h | | | Pendiente |
| B-11 | B | Investigación bloquea | | | Pendiente |
| B-12 | B | Solo su mesa; 403 | | | Pendiente |
| B-13 | B | Pausa bloquea apuestas | | | Pendiente |
| C-02 | A, B | Ganancias e historial reflejan B-05 | | | Pendiente |
| C-03 | A, B | Reactivación y nueva investigación | | | Pendiente |
| C-04 | A, B | Suspensión por deuda y reactivación | | | Pendiente |
| C-05 | A, B | Notificación de suspensión al admin | | | Pendiente |
| C-06 | A, B | Pausa visible en Suspensiones | | | Pendiente |

Estados: **Aprobado**, **Fallido**, **Pendiente**, **Bloqueado** (por ejemplo, si terminó el turno en curso).

### 14.4. Cómo registrar un defecto

Cuando una prueba falle, anótenla en la hoja como **Fallido** y agreguen una entrada con este formato (por ejemplo, en `docs/defectos.md`):

```
Id: DEF-xx            Prueba: B-05            Fecha y hora: 2026-11-0x hh:mm
Usuario y rol: crupier1 (Crupier)             Navegador:
Pasos exactos (con los datos usados):
  1. …
Resultado esperado (con la regla de C o P que lo exige):
Resultado observado (texto exacto del mensaje):
Captura: docs/evidencias/sustentacion/B-05-fallo.png
¿Se repite? (sí/no, cuántas veces):
Datos afectados (jugador, saldo antes y después):
```

Para reproducirlo:
1. Vuelvan a cargar los datos (§2.5).
2. Repitan solo los pasos previos que la prueba necesita (§14.2).
3. Ejecuten la prueba fallida.

Si el fallo depende de la hora (turnos), anoten la hora de la VM con:

```bash
ssh estudiante@10.43.98.50 date
```

---

## 15. Datos de prueba y resultados esperados

### 15.1. Estado inicial tras cargar los datos

| Jugador | Usuario | Saldo | Estado |
|---|---|---|---|
| CLI-001 Ana Jugadora | `jugador1` | 1050.00 | Activo (tiene una apuesta pendiente: Rojo 50 × 2 en la ronda 3 de R-01) |
| CLI-002 Bruno Jugador | `jugador2` | 4800.00 | Suspendido: pausa voluntaria (24 h desde la carga) |
| CLI-003 Carla Jugadora | `jugador3` | 400.00 | Suspendido: pérdida diaria (perdió 1100 en P-01) |
| EMP-CLI-003 Crupier Tres | `crupier3` | 4200.00 | Investigación (indefinida) |
| CLI-B01, CLI-B02 (C-01) | `b_jugador1`, `b_jugador2` | 1500.00 cada uno | Activo |
| EMP-CLI-A02 Mario Prueba (C-01) | `a_empleado2` | 1000.00 | Activo (salario 100, límite de deuda 200) |

### 15.2. Saldos esperados después de cada prueba

| Jugador | Movimientos | Saldo final | Estado final |
|---|---|---|---|
| CLI-001 | 1050 − 20 (B-02) + 100 + 40 (B-05) − 100 + 200 (B-09) | **1270.00** | Suspendido por pausa (B-13) |
| CLI-B01 | 1500 − 30 − 10 + 10 (anulación) − 10 (de 30 a 40) | **1460.00** | Activo (Negro 40 perdida) |
| CLI-B02 | 1500 − 1010 | **490.00** | Suspendido por pérdida diaria, 24 h |
| EMP-CLI-003 | 4200 − 600 + 1800 (C-03) | **5400.00** | Investigación |
| EMP-CLI-A02 | 1000 − 250 (C-04); el abono no cambia el saldo | **750.00** | Activo (deuda 150 ≤ 200) |

Historial de CLI-001 tras B-09 (A-17):
- 7 apuestas resueltas: total apostado 470.00, total en premios 740.00, resultado neto +270.00.
- Saldo inicial del seeder 1000 + 270 = 1270.

### 15.3. Ganancias esperadas de hoy (Administrador, Desde = Hasta = hoy)

| Mesa | Apuestas resueltas | Total apostado | Premios pagados | Consolidado casino | Neto casino | Cuándo es válido |
|---|---|---|---|---|---|---|
| R-01 | 3 | 110.00 | 140.00 | 40.00 | −30.00 | Desde B-05 |
| B-01 | 1 | 600.00 | 1800.00 | 0.00 | −1200.00 | Desde C-03 |
| P-01 | 4 | 2460.00 | 200.00 | 2360.00 | 2260.00 | Desde C-04 (incluye la pérdida sembrada de 1100) |
| Total | 8 | 3170.00 | 2140.00 | 2400.00 | 1030.00 | Al terminar todo |

`supervisor2`, con la fecha de hoy, ve dos filas:
- B-01, con la apuesta de C-03 (600 / 1800 / 0 / −1200);
- R-01, que supervisó ayer, en ceros.

Ve las mesas que supervisó alguna vez, pero solo cuenta las rondas de sus propias asignaciones.

---

## 16. Inventario de capturas y evidencias

Capturas reales tomadas el 2026-11-01 en la copia de auditoría (mismo código, datos del seeder), a 1280×800 salvo AUD-70 y AUD-71 (375 px). Carpeta: [docs/evidencias/auditoria/](evidencias/auditoria/). El paso previo es siempre «entrar con el rol indicado»; los demás pasos remiten al manual (§11). Las 11 capturas del 29/10 siguen en [docs/evidencias/](evidencias/README.md).

| Id | Funcionalidad | Rol | Pasos previos | Qué demuestra |
|---|---|---|---|---|
| AUD-01 | Login | — | Abrir `/login` | Pantalla de inicio de sesión |
| AUD-02 | Login | — | Contraseña equivocada | Rechazo de credenciales |
| AUD-03 | Inicio | Administrador | Login | Menú del administrador |
| AUD-04 | Sesiones | — | Cerrar sesión y abrir `/empleados` | Ruta protegida tras el cierre |
| AUD-05 | Empleados | Administrador | Menú Empleados | Lista con competencias y perfil de jugador |
| AUD-06 | Jugadores | Administrador | Menú Jugadores | Lista con saldo y estado |
| AUD-07 | Mesas | Administrador | Menú Mesas | Mesas con límites |
| AUD-08 | Turnos | Administrador | Menú Turnos | Lista de turnos (≤ 2 h) |
| AUD-09 | Asignaciones | Administrador | Turnos → Ver asignaciones | Turno en curso con 3 asignaciones |
| AUD-10 | Suspensiones | Administrador | Menú Suspensiones | Activas e historial |
| AUD-11 | Ganancias | Administrador | Ganancias, 5 días | Informe por mesa |
| AUD-12 | Notificaciones | Administrador | Notificaciones | Avisos de suspensión y fraude |
| AUD-13 | Historial de cliente | Administrador | Jugadores → Historial EMP-CLI-003 | Datos, totales, apuestas, suspensiones y abonos |
| AUD-14 | Abonos | Administrador | Jugadores → Abonos | Deuda, límite y abonos anteriores |
| AUD-15 | Roles | Crupier | Abrir `/empleados` | 403 del servidor |
| AUD-16 | Jugadores | Administrador | Alta con edad 17 | Rechazo de menor de edad (servidor) |
| AUD-17 | Jugadores | Administrador | Contraseña de 5 caracteres | Validación de longitud en el navegador |
| AUD-18 | Jugadores | Administrador | Código CLI-001 repetido | Código único |
| AUD-19 | Jugadores | Administrador | Alta válida | Jugador registrado |
| AUD-20 | Mi mesa | Crupier | Mi mesa | Mesa, límites y ronda abierta |
| AUD-21 | Apuestas | Crupier | Monto 5 | Rechazo bajo el mínimo |
| AUD-22 | Apuestas | Crupier | CLI-002 | Rechazo de jugador suspendido |
| AUD-23 | Apuestas | Crupier | Varias apuestas | Ronda abierta con apuestas pendientes |
| AUD-24 | Apuestas | Crupier | Modificar | Formulario de modificación |
| AUD-25 | Rondas | Crupier | Resultado y tipo ganador | Formulario de cierre |
| AUD-26 | Rondas | Crupier | Cerrar ronda | Ganadas, premios y perdidas |
| AUD-27 | Rondas | Crupier | Abrir ronda | Número correlativo (4) |
| AUD-28 | Póker | Crupier | Abrir ronda en P-01 | Sin formulario de apuesta con la ronda abierta |
| AUD-29 | Póker | Crupier | Ronda cerrada | Registro con resultado conocido |
| AUD-30 | Apuestas | Crupier | Monto mayor que el saldo | Saldo insuficiente |
| AUD-31 | Suspensiones | Crupier | EMP-CLI-003 | Investigación bloquea apuestas |
| AUD-32 | Suspensiones | Crupier | Pérdida > 1000 y otra apuesta | RN-14 en vivo |
| AUD-33 | Inicio | Supervisor | Login | Menú del supervisor |
| AUD-34 | Conceptos | Supervisor | Mis conceptos | Pendientes y lista de conceptos |
| AUD-35 | Conceptos | Supervisor | Registrar | Concepto registrado |
| AUD-36 | Ganancias | Supervisor | Ganancias, 5 días | Solo sus asignaciones |
| AUD-37 | Inicio | Jugador | Login | Menú del jugador |
| AUD-38 | Mi cuenta | Jugador | Mi cuenta | Saldo y estado |
| AUD-39 | Mi historial | Jugador | Mi historial | Historial propio |
| AUD-40 | Notificaciones | Jugador | Notificaciones | Apuestas resueltas |
| AUD-41 | Mi cuenta | Jugador | Tras RN-14 | Cuenta suspendida 24 h |
| AUD-42 | Notificaciones | Administrador | Tras RN-14 y concepto | Suspensión y sospecha de fraude |
| AUD-43 | Suspensiones | Administrador | Finalizar investigación | Investigación al historial |
| AUD-44 | Notificaciones | Empleado | Tras el fin de la investigación | Aviso de reactivación |
| AUD-45 | Suspensiones | Crupier | Empleado gana 1200 netos | RN-18 en vivo |
| AUD-46 | Mi cuenta | Jugador | Solicitar pausa | RN-15 |
| AUD-47 | Apuestas | Crupier | Apuesta de un jugador en pausa | Pausa bloquea apuestas |
| AUD-48 | Suspensiones | Administrador | Tras todas las pruebas | Cinco suspensiones activas |
| AUD-49 | Empleados | Administrador | Documento repetido | Unicidad |
| AUD-50 | Empleados | Administrador | Alta válida | Empleado registrado |
| AUD-51 | Empleados | Administrador | Editar | Contraseña opcional y competencias |
| AUD-52 | Jugadores | Administrador | Registrar como jugador | Perfil vinculado a empleado |
| AUD-53 | Empleados | Administrador | Borrar Crupier Uno | Borrado bloqueado por asignaciones |
| AUD-54 | Jugadores | Administrador | Borrar CLI-AUD-1 | Borrado bloqueado por apuestas |
| AUD-55 | Jugadores | Administrador | Editar | Sin campo de saldo |
| AUD-56 | Mesas | Administrador | Mínimo > máximo | Validación |
| AUD-57 | Mesas | Administrador | Alta R-02 | Mesa registrada |
| AUD-58 | Mesas | Administrador | Cambiar el juego de R-01 | Bloqueo con asignaciones |
| AUD-59 | Turnos | Administrador | 10:00–12:30 | Rechazo por más de 120 min (el «se solapa» de la misma pantalla era DEF-02, ya corregido) |
| AUD-60 | Asignaciones | Administrador | Crupier sin competencia | RN-08 |
| AUD-61 | Asignaciones | Administrador | Repetir mesa el mismo día | RN-05 |
| AUD-62 | Asignaciones | Administrador | Segunda mesa del crupier | RN-04 |
| AUD-63 | Asignaciones | Administrador | Dos asignaciones válidas | Turno con asignaciones |
| AUD-64 | Turnos | Administrador | Editar un turno con asignaciones | Bloqueo |
| AUD-65 | Turnos | Administrador | 10:00–12:00 tras 08:00–10:00 | **DEF-02** antes de corregirlo (histórico) |
| AUD-66 | Abonos | Administrador | Abono con deuda negativa | OBS-01 |
| AUD-67 | Suspensiones | Crupier | Empleado con deuda > 2 salarios | RN-17 en vivo |
| AUD-68 | Abonos | Administrador | Abono de 100 | Reactivación automática |
| AUD-69 | Notificaciones | Empleado | Tras RN-17 y el abono | Suspensión y reactivación |
| AUD-70 | Mi mesa | Crupier | 375 px | Vista de celular sin desborde |
| AUD-71 | Asignaciones | Administrador | 375 px | OBS-05: el menú desborda |

Evidencias en texto, en la misma carpeta:
- [resultados-servidor-2026-11-01.txt](evidencias/auditoria/resultados-servidor-2026-11-01.txt): permisos, sesiones y SHA-256 probados contra el servidor.
- [pruebas-php-artisan-test-2026-11-01.txt](evidencias/auditoria/pruebas-php-artisan-test-2026-11-01.txt): salida completa de las 122 pruebas en el momento de la auditoría. La salida tras las correcciones (125 pruebas) está en [docs/evidencias/pruebas-php-artisan-test.txt](evidencias/pruebas-php-artisan-test.txt).
- [verificacion-correcciones-2026-11-01.txt](evidencias/auditoria/verificacion-correcciones-2026-11-01.txt): verificación en vivo de DEF-01, DEF-02 y DEF-03 corregidos.
- [route-list-2026-11-01.txt](evidencias/auditoria/route-list-2026-11-01.txt): las 54 rutas.

**Capturas mínimas recomendadas para la entrega y la sustentación:**

| Captura | Qué demuestra |
|---|---|
| AUD-01, AUD-02, AUD-03 | Login |
| A-02 (que tomarán ustedes) | Carga útil sin contraseña |
| AUD-15 | 403 |
| AUD-20, AUD-33, AUD-37 | Menús por rol |
| AUD-19 | Alta |
| AUD-16 | Validación |
| AUD-26 | Resultado de la ronda |
| AUD-29 | Póker |
| AUD-32, AUD-46, AUD-45, AUD-67, AUD-68 | Suspensiones |
| AUD-42 | Notificaciones |
| AUD-11 | Ganancias |

Para tomarlas ustedes mismos: sigan la guía de §11 indicada en cada fila, maximicen la ventana del navegador y capturen con Win + Shift + S.

---

## 17. Pruebas automatizadas

**Ejecución en la VM tras las correcciones, 2026-11-01 a las 09:23 (hora de la VM):** `php84 artisan test` → **125 pruebas aprobadas (661 aserciones), 0 fallidas, 6,10 s**.
- Usan SQLite en memoria (`phpunit.xml:22-23`). Se comprobó que el archivo de la base real tenía la misma fecha de modificación antes y después.
- Salida completa en [docs/evidencias/pruebas-php-artisan-test.txt](evidencias/pruebas-php-artisan-test.txt).
- Durante la auditoría, antes de corregir (08:30), eran 122 pruebas (647 aserciones): [pruebas-php-artisan-test-2026-11-01.txt](evidencias/auditoria/pruebas-php-artisan-test-2026-11-01.txt).

| Archivo | Pruebas | Qué comprueba exactamente |
|---|---|---|
| `AutenticacionTest` | 10 | El formulario entrega un nonce y no envía la contraseña; login con la respuesta correcta; rechazo de nonce reutilizado, de contraseña en claro, de hash sin nonce y de sesión sin nonce; usuario inexistente; rutas protegidas; regeneración del id de sesión; logout; la base guarda exactamente SHA-256 en hexadecimal. |
| `RolesTest` | 4 | El middleware da 403 a otro rol; «Inicio» muestra el rol; el seeder no crea usuarios sin contraseña de demostración y sí crea uno por rol con ella. |
| `PaginasTest` | 3 | Todas las páginas de cada rol cargan y las de otros roles devuelven 403 (en español); las páginas 404 y 419 están en español (DEF-03). |
| `EmpleadosTest` | 11 | Juegos con su registro detallado; alta con cuenta y competencias; unicidad, salario positivo y rol válido; contraseña no SHA-256 rechazada; edición sin contraseña; perfil de jugador actualizado; borrados permitidos y bloqueados; solo el admin gestiona; menú del admin. |
| `JugadoresTest` | 8 | Alta con todos los datos; código y documento únicos; campos obligatorios; 17 años rechazado y 18 aceptado; editar no cambia el saldo; empleado con perfil de jugador; un perfil por usuario; documento de empleado ya usado. |
| `MesasTest` | 3 | CRUD; código único y mínimo ≤ máximo; no se borra ni cambia de juego una mesa con asignaciones. |
| `TurnosTest` | 6 | 2 h se acepta y 2 h 1 min no; medianoche; solape y contigüidad entre turnos del formulario y **con un turno guardado con segundos, como los del seeder** (DEF-02); editar el propio turno; turno con asignaciones. |
| `AsignacionesTest` | 8 | RN-04 (mesa y crupier por turno), RN-05 (mismo día sí, otro día no), RN-08, rol equivocado, borrado protegido y solo el admin. |
| `ConceptoTest` | 6 | Registro al terminar el turno; rechazo antes del fin; segundo concepto; valor no permitido; otro supervisor 403; solo supervisores. |
| `RondasTest` | 7 | Numeración correlativa; una sola ronda abierta; anulación; crupier sin asignación, de otra mesa o fuera de turno → 403; no se borra una asignación con rondas. |
| `ApuestasTest` | 8 | Guarda los cinco datos; límites exactos aceptados; bajo el mínimo o sobre el máximo; formulario conservado tras un rechazo; saldo insuficiente; registrar, modificar y anular ajustan el saldo; no se borra un jugador con apuestas; jugador y supervisor no operan. |
| `ResultadoTest` | 5 | El cierre procesa ganadas y perdidas con premio y saldo; una ronda cerrada no se vuelve a procesar; póker rechaza la apuesta con la ronda abierta y la acepta tras el resultado; póker se procesa al registrar; póker rechaza (403) una apuesta en una ronda de un turno anterior (DEF-01). |
| `SuspensionesTest` | 6 | RN-14: mayor que el umbral suspende 24 h, igual no suspende, bloquea en cualquier mesa y vuelve a apostar tras 24 h; la pérdida es neta y por día calendario; empleado con perfil apuesta como cliente. |
| `PausaTest` | 4 | Pausa de 24 h; no se pide con otra suspensión activa; solo sobre el propio perfil; empleado con perfil. |
| `AbonosTest` | 12 | RN-17 (suspensión y reactivación por abono, o no si sigue sobre el límite); abono mayor que la deuda o a un no empleado; RN-18 (investigación y bloqueo); fin de la investigación; la ganancia se cuenta de nuevo tras el fin; no aplica a no empleados; solo el admin. |
| `NotificacionesTest` | 10 | Cada evento de §9.1 notifica a quien corresponde; la pausa y otros conceptos no notifican; marcar una o todas; no marcar ajenas; contador en la cabecera. |
| `HistorialTest` | 5 | El admin ve el historial completo; el jugador solo el suyo; el jugador no accede a `/jugadores/{id}`; empleado con perfil; cuenta sin perfil → 403. |
| `GananciasTest` | 3 | Totales por mesa y filtro por fecha; el supervisor solo ve sus mesas y sus rondas. |
| `DemoSeederTest` | 4 | Sin contraseña no crea nada; crea cuentas, mesas y turno en curso; los datos cumplen las reglas; un crupier opera su mesa tras la carga. |

**Requisitos o casos sin prueba automática:**

| Sin prueba automática | Cómo se cubrió |
|---|---|
| `public/js/zk.js`: hash en el navegador, longitud mínima y coincidencia de contraseñas | No tiene pruebas automáticas de JavaScript; se verificó a mano (AUD-17, A-02). |
| Presentación a ancho de celular | OBS-05. |

DEF-01, DEF-02 y DEF-03 no tenían prueba en la auditoría; por eso pasaron inadvertidos. Ahora cada uno tiene la suya:
- `ResultadoTest::test_d14_poker_no_registra_apuestas_en_rondas_de_turnos_anteriores`;
- `TurnosTest::test_d10_turno_contiguo_a_uno_guardado_con_segundos_no_cuenta_como_solape`;
- `PaginasTest::test_fa_00_paginas_de_error_404_y_419_en_espanol`.

Se comprobó que las dos primeras **fallan con el código anterior** y pasan con el corregido.

Una prueba que pasa demuestra solo lo que comprueba la tabla anterior, no todos los casos posibles.

---

## 18. Problemas encontrados y requisitos pendientes

Los tres defectos se encontraron en la auditoría (commit `1102802`) y se corrigieron el mismo día. Cada sección conserva cómo se reproducía, para poder comprobar que ya no ocurre.

### DEF-01 — Póker: apuestas registrables en rondas de turnos anteriores — CORREGIDO

- **Gravedad.** Media-baja: solo con una solicitud fabricada; la interfaz no ofrece esa opción.
- **Regla afectada.**
  - D-14: el crupier opera solo las rondas y apuestas de la mesa que tiene asignada en el turno en curso.
  - Integridad del informe de ganancias por turno y supervisor (D-24).
- **Cómo se reproduce.**
  1. Carga los datos y entra como `crupier3` durante su turno en P-01.
  2. Abre «Mi mesa» y, en la consola del navegador (F12), envía:

     ```js
     fetch('/rondas/1/apuestas', {
       method: 'POST',
       headers: {'Content-Type': 'application/x-www-form-urlencoded'},
       body: new URLSearchParams({
         _token: document.querySelector('[name=_token]').value,
         codigo_cliente: 'CLI-001', tipo_apuesta: 'Mano ganadora',
         monto: '50', factor_pago: '2', estado: 'Ganada',
       }),
     })
     ```

     La ronda 1 es una ronda de P-01 de hace 5 días, del turno de crupier2.
  3. **Observado en la auditoría:** «Apuesta registrada correctamente.»; se creaba la apuesta en esa ronda antigua y el premio se acreditaba.
  4. **Esperado y observado tras la corrección:** 403, como cuando se intenta en otra mesa; no se crea ninguna apuesta.
- **Causa.** `ApuestaController::autorizarRonda` comprobaba que el crupier tuviera asignada **la mesa** en el turno en curso, no que la ronda perteneciera **a esa asignación**. En juegos con registro detallado no pasa nada, porque solo se apuesta en la ronda abierta; en póker se apuesta en rondas cerradas.
- **Corrección aplicada.** `ApuestaController.php:60-61`: una ronda cerrada solo admite apuestas si es de la asignación en curso del crupier. Una ronda abierta de la mesa sigue admitiéndolas, aunque venga del turno anterior (D-14). Es la misma regla con la que «Mi mesa» decide qué rondas mostrar.
- **Prueba nueva.** `ResultadoTest::test_d14_poker_no_registra_apuestas_en_rondas_de_turnos_anteriores`.
- **Verificación en vivo.**
  - El `POST /rondas/1/apuestas` de crupier3 recibe 403 y la ronda 1 sigue con una sola apuesta.
  - El póker del turno en curso funciona: abrir, cerrar y registrar dan «Apuesta registrada correctamente.».
  - La ruleta también sigue funcionando.

### DEF-02 — Falso solape al crear un turno contiguo a un turno del seeder — CORREGIDO

- **Gravedad.** Media: afectaba a la demostración, porque el turno sembrado es justamente el que hay que continuar.
- **Regla afectada.** D-10: los turnos del mismo día no se solapan, pero pueden ser contiguos. No contradice C directamente, pero impide una operación válida.
- **Cómo se reproduce.**
  1. Carga los datos: el turno en curso es, por ejemplo, 08:00–10:00.
  2. Como `admin`, «Turnos» → «Registrar turno»: hoy, 10:00–12:00.
  3. **Observado en la auditoría:** «El turno se solapa con otro turno de la misma fecha.» (AUD-65).
  4. **Esperado y observado tras la corrección:** «Turno registrado correctamente.».
- **Causa.** El seeder guarda las horas con segundos (`10:00:00`) y el formulario sin segundos (`10:00`). La consulta de solape comparaba texto en SQLite, y `'10:00:00' > '10:00'` es verdadero. Con turnos creados solo desde el formulario no ocurría; por eso `TurnosTest` pasaba.
- **Corrección aplicada.** `TurnoRequest.php:44-51`: el solape se calcula en PHP con las horas reales de los turnos del día (`Turno::inicio()` y `Turno::fin()`), sin importar cómo estén guardadas. Así no hace falta migrar datos y funciona igual en SQLite y en PostgreSQL.
- **Prueba nueva.** `TurnosTest::test_d10_turno_contiguo_a_uno_guardado_con_segundos_no_cuenta_como_solape`: acepta los turnos contiguos antes y después del sembrado y rechaza uno que se solapa.
- **Verificación en vivo.** Con el turno sembrado de 09:00–11:00, el turno 11:00–13:00 se registró y uno de 10:59–11:30 se rechazó.

### DEF-03 — Páginas de error 404 y 419 en inglés — CORREGIDO

- **Gravedad.** Baja (cosmético).
- **Cómo se reproducía.**
  - Abrir `/mesas/99/edit` como `admin` → «404 Not Found».
  - Enviar un formulario con la sesión vencida → «419 Page Expired».
- **Esperado.** Español, como la 403 («No tienes permiso para ver esta página.»).
- **Corrección aplicada.** Vistas nuevas `resources/views/errors/404.blade.php` («La página que buscas no existe.») y `419.blade.php` («La sesión expiró. Vuelve a cargar la página e inténtalo de nuevo.»), con la misma plantilla que la 403.
- **Prueba nueva.** `PaginasTest::test_fa_00_paginas_de_error_404_y_419_en_espanol`.
- **Verificación en vivo.** Así salen `/mesas/99/edit` (404) y `POST /logout` sin token (419).

### Observaciones (no incumplen P ni C)

| Id | Observación | Recomendación |
|---|---|---|
| OBS-01 | En «Abonos», un empleado con ganancias netas muestra «Deuda actual: -2900.00 fichas» y el campo de monto queda con un máximo negativo: el navegador bloquea el envío sin un mensaje claro (AUD-66). El servidor rechaza bien. | Mostrar «Sin deuda» y ocultar el formulario cuando la deuda sea ≤ 0. |
| OBS-02 | `APP_DEBUG=true` (Debug Mode ENABLED) en la VM: un error mostraría la traza. Hoy solo se accede por túnel. | Poner `APP_DEBUG=false` en el `.env` de la VM para la entrega. |
| OBS-03 | SHA-256 sin sal: contraseñas iguales dan el mismo valor guardado. El valor guardado basta para responder el desafío; SHA-256 es rápido ante ataques de diccionario. Es la consecuencia del requisito SHA-256 + Zero Knowledge, no un error. | Explicarlo en el análisis crítico (§19, etapa 3). |
| OBS-04 | El mínimo de 8 caracteres solo lo comprueba el navegador (D-23). | Explicarlo; es inherente a Zero Knowledge. |
| OBS-05 | A 375 px, el menú del administrador (7 opciones) no se ajusta y la página mide 701 px; en las vistas del crupier no pasa (AUD-70, AUD-71). | Permitir que el menú pase a varias líneas en pantallas angostas. |
| OBS-06 | En «Mi mesa», cada apuesta es un `<p>` que contiene un `<form>` («Anular»): HTML inválido que el navegador corrige solo. Sin efecto funcional. | Usar `<div>` o `<li>`. |
| OBS-07 | Borrar mesas, turnos y asignaciones, y anular rondas y apuestas, no pide confirmación; borrar empleados o jugadores sí. | Unificar con `confirm()`. |
| OBS-08 | La notificación «Tu apuesta de la ronda 3 fue resuelta» no dice la mesa, y cada mesa tiene su propia numeración. | Incluir el código de la mesa. |
| OBS-09 | El login no limita los intentos fallidos. P no lo pide. | Opcional: `throttle` de Laravel en `POST /login`. |
| OBS-10 | Interpretaciones que conviene defender en la sustentación: D-04 (el crupier marca los tipos ganadores), D-06 (la pérdida diaria es neta), D-24 (ganancias como informe), suspensiones sin alta ni borrado manual, y D-12 (sin consignaciones). | Tenerlas a mano (§19). |

### Requisitos pendientes del proyecto (no de la aplicación)

| Pendiente | Fuente |
|---|---|
| Análisis crítico de Laravel: calidad, facilidad de uso, curva de aprendizaje, ventajas, desventajas y limitaciones | P §10 |
| Diapositivas de la exposición de 45 minutos | P §10 |
| Decidir si se atienden OBS-02 (`APP_DEBUG=false`) y las demás observaciones antes de la entrega (2026-11-03) | Esta auditoría |

---

## 19. Guía para preparar la demostración de la sustentación

Solo usa funcionalidades verificadas en esta auditoría. Duración: unos 25 minutos de los 45 de la exposición. Roles sugeridos:
- **Integrante 1** opera el navegador del administrador y del supervisor;
- **Integrante 2** opera el navegador del crupier y del jugador;
- **Integrante 3** explica el código y la terminal.

### 19.1. Preparación (el día de la exposición)

1. **Cargar los datos.** Entre 15 y 45 minutos antes, cuidando que toda la demostración caiga dentro de las 2 horas del turno en curso. Ejemplo: exposición a las 10:20 → cargar entre las 10:00 y las 10:15 (turno de 10:00 a 12:00).
   - Revisa la hora de la VM (`ssh estudiante@10.43.98.50 date`): ha estado unos minutos atrasada respecto al PC.
2. Con `admin`, crear antes de la exposición:
   - el jugador `CLI-DEMO`: documento `1030000001`, «Demo Sustentación», 30 años, saldo 1500, usuario `demo_jugador`;
   - un usuario de prueba para la demostración de SHA-256 (por ejemplo, ese mismo `demo_jugador`, con una contraseña que solo conozca quien la escribe).
3. Abrir dos navegadores distintos y dejar las sesiones iniciadas:
   - navegador 1: `admin` en una pestaña y `supervisor1` en una ventana de incógnito;
   - navegador 2: `crupier1` en una pestaña, `crupier3` en una ventana de incógnito y `jugador1` en otro perfil.

   Cada usuario debe tener su propio perfil o ventana de incógnito, porque el mismo navegador comparte la sesión entre pestañas.
4. Tener abiertos:
   - una terminal con el túnel;
   - otra terminal en la VM (`cd ~/casino`);
   - el editor en `AutenticacionZeroKnowledge.php` y `public/js/zk.js`.
5. Plan B: las capturas AUD-xx de §16 en una carpeta, por si falla la red.
6. **No** volver a cargar los datos durante la exposición y **no** pedir la pausa de `jugador1` hasta el final.

### 19.2. Recorrido

| Etapa | Quién | Rol | Cómo entra | Operación y datos | Qué debe mostrar | Requisito | Evidencia de respaldo |
|---|---|---|---|---|---|---|---|
| 1 | Integrante 3 | — | Terminal en la VM | `php84 artisan --version`; `php84 artisan route:list --except-vendor` | `Laravel Framework 13.39.0`; 54 rutas con middleware | P §2, §7 | §3.1, route-list |
| 2 | Integrante 1 | — | `/login` | Contraseña equivocada; luego la correcta con `admin`; F12 → Red → `POST login` | Error; «Inicio»; la carga útil sin contraseña | P §3, §4 (login, ZK) | AUD-01 a AUD-03 |
| 3 | Integrante 3 | — | Editor y terminal | Mostrar `zk.js:66-67` y `AutenticacionZeroKnowledge.php:12-21`; ejecutar la comprobación de §5.2 con `demo_jugador` | «coincide»; explicar la sal y el pass-the-hash (OBS-03) | P §4 (SHA-256) | §5.2 |
| 4 | Integrantes 1 y 2 | Los 4 | Ventanas ya abiertas | Mostrar los 4 menús; en `crupier1`, escribir `/empleados` | 4 menús distintos; «403 No tienes permiso…» | P §4 (roles) | AUD-03, 15, 20, 33, 37 |
| 5 | Integrante 1 | Administrador | «Turnos» → «Ver asignaciones» del turno en curso | Mostrar las 3 asignaciones; intentar R-01 + Crupier Dos | «El crupier no tiene competencia en el juego de esta mesa.» | C: competencias; P §5.1 | AUD-09, AUD-60 |
| 6 | Integrante 2 | Crupier | «Mi mesa» (R-01) | `CLI-001`, `Rojo`, `20`, `2` → registrar; monto `5`; `CLI-002`; luego «Resultado» `Rojo 12`, marcar Rojo → «Cerrar ronda» | Apuesta registrada; los dos rechazos; las dos apuestas a Rojo Ganada, con premios 100 y 40 | P §5.2-5.3; C: apuestas y resultados | AUD-21, 22, 26 |
| 7 | Integrante 2 | Crupier | Ventana de `crupier3` (P-01) | «Abrir ronda» (no hay formulario) → «Cerrar ronda» con `Escalera de color` → `CLI-001`, `Mano ganadora`, `100`, `2`, Ganada | Registro tras el resultado | C: póker | AUD-28, AUD-29 |
| 8 | Integrante 2 → 1 | Crupier → Administrador | Ventana de `crupier3` | `CLI-DEMO`, `Mano perdida`, `1010`, `1`, Perdida; otra de 50 | Rechazo «Pérdida diaria hasta …»; el admin ve la notificación y la fila en «Suspensiones» | C: autoexclusión (RN-14); P §6.1, §6.4 | AUD-32, AUD-42 |
| 9 | Integrante 1 | Administrador | «Suspensiones» | Mostrar la investigación de EMP-CLI-003 y el historial con la suspensión por deuda levantada por un abono → «Finalizar investigación» | «Investigación finalizada.» | C: empleados (RN-17, RN-18) | AUD-10, AUD-43, AUD-68 |
| 10 | Integrante 1 | Supervisor | «Mis conceptos» | Turno más antiguo de P-01 → «Sospecha de fraude» | «Concepto registrado correctamente.»; explicar que no se calcula (C) | C: concepto | AUD-34, AUD-35 |
| 11 | Integrante 1 | Administrador | «Ganancias» | Desde = Hasta = hoy | Tabla por mesa y totales | P §5.4 | AUD-11 |
| 12 | Integrante 2 | Jugador | Perfil de `jugador1` | «Mi cuenta», «Mi historial», «Notificaciones»; al final, «Solicitar pausa de 24 horas» | Saldo, historial y avisos; pausa | P §6.1, §6.3; C: pausa | AUD-38 a AUD-40, AUD-46 |
| 13 | Integrante 3 | — | Terminal | `php84 artisan test` | `125 passed (661 assertions)` | Calidad | §17 |

**Recomendaciones.**
- Ensayen el recorrido completo una vez el día anterior, con los datos recién cargados.
- DEF-01, DEF-02 y DEF-03 ya están corregidos. Si cambian algo más en el código antes de la entrega, repitan `php84 artisan test` y las pruebas A-12 y B-09.
- Las preguntas frecuentes del jurado sobre SHA-256 sin sal y sobre las interpretaciones D-04, D-06 y D-24 están respondidas en §5.2 y en OBS-10.

---

## 20. Glosario

| Término | Significado en esta aplicación |
|---|---|
| Abono | Pago que el Administrador registra para reducir la deuda (pérdidas netas) de un empleado-jugador. No cambia el saldo en fichas. |
| Asignación | Mesa + crupier + supervisor dentro de un turno. |
| Blade | Motor de plantillas de Laravel para las vistas HTML. |
| Concepto | Opinión subjetiva del supervisor sobre el crupier al final del turno (4 valores). |
| Consolidado casino | Suma de los montos de las apuestas perdidas: lo que gana el casino. |
| CSRF / 419 | Token que Laravel exige en cada formulario; sin él responde 419 «Page Expired». |
| Deuda de empleado | Pérdidas netas acumuladas de un empleado-jugador menos sus abonos. |
| Eloquent | ORM de Laravel: cada tabla es un modelo PHP. |
| Factor de pago | Multiplicador de la apuesta. Premio = monto × factor (C). |
| Form Request | Clase de Laravel que valida los datos de un formulario antes del controlador. |
| Hash / SHA-256 | Función que transforma la contraseña en 64 caracteres hexadecimales; no se puede invertir. |
| Investigación | Estado de un empleado-jugador con ganancias netas mayores que su salario mensual; bloquea apuestas hasta que el Administrador lo finaliza. |
| Middleware | Filtro de Laravel que corre antes del controlador (`auth`, `guest`, `rol`). |
| Migración | Archivo PHP que crea o modifica tablas de forma reproducible. |
| Neto casino | Total apostado − premios pagados. |
| Nonce | Número aleatorio de un solo uso que el servidor entrega con el login; impide reutilizar una respuesta capturada. |
| Pausa voluntaria | Autoexclusión de 24 horas pedida por el jugador. |
| Registro detallado | Atributo del juego: Ruleta y Blackjack sí (se apuesta antes del resultado); Póker no (se registra después). |
| Ronda | Partida de una mesa con número correlativo, hora de inicio y cierre, y resultado. |
| Seeder | Programa que carga los datos de demostración (`php artisan migrate:fresh --seed`). |
| Suspensión | Bloqueo de apuestas por pérdida diaria (24 h), pausa (24 h), deuda de empleado (hasta el abono) o investigación (hasta que se finalice). |
| Túnel SSH | Conexión cifrada que lleva `localhost:8010` del PC al puerto 8000 de la VM sin abrir puertos públicos. |
| Turno | Franja de un día, de máximo 2 horas, sin cruzar la medianoche. |
| Zero Knowledge | El servidor comprueba que el usuario conoce la contraseña sin recibirla: recibe SHA-256(SHA-256(contraseña) + nonce). |

---

## 21. Checklist final de cumplimiento

| Requisito | Fuente | Estado | Dónde se demuestra |
|---|---|---|---|
| Framework asignado: Laravel | P §2, §7 | APROBADO | §3 |
| Base relacional y persistencia | P §7 | APROBADO | §3.2, §4 |
| Arquitectura coherente con el framework | P §7 | APROBADO | §3.3 |
| Lógica de negocio correcta | P §7 | APROBADO (DEF-01 y DEF-02 corregidos) | §8, §18 |
| Login | P §4 | APROBADO | §5.1 |
| Sesiones | P §4 | APROBADO | §5.3 |
| Contraseñas con SHA-256 | P §4 | APROBADO | §5.2 |
| Zero Knowledge | P §3 | APROBADO | §5 |
| Control de acceso por roles (interfaz y servidor) | P §4 | APROBADO | §6 |
| Caso de uso: mesas | P §5 | APROBADO | §7.1 |
| Caso de uso: rondas | P §5 | APROBADO | §7.2 |
| Caso de uso: apuestas | P §5 | APROBADO (DEF-01 corregido) | §7.3 |
| Caso de uso: ganancias | P §5 | APROBADO | §7.4 |
| Mínimo de 3 casos de uso | P §5 | APROBADO (4) | §7 |
| Interfaz de usuario | P §6 | APROBADO (OBS-05) | §11, capturas |
| Notificaciones | P §6.1 | APROBADO | §9.1 |
| Gestión de empleados | P §6.2 | APROBADO | §9.2 |
| Historial de clientes | P §6.3 | APROBADO | §9.3 |
| Validación de autorizaciones/suspensiones | P §6.4 | APROBADO | §9.4 |
| Mínimo de 3 funcionalidades adicionales | P §6 | APROBADO (4) | §9 |
| Jugadores: código, documento, nombre, edad y saldo; sin menores | C | APROBADO | §8.1 |
| Mesas: identificador, juego, mínimo y máximo | C | APROBADO | §8.2 |
| Competencias de crupieres y supervisores | C | APROBADO | §8.2 |
| Un crupier por mesa y turno; rotación en la jornada | C | APROBADO | §8.2 |
| Turnos de máximo 2 horas | C | APROBADO | §8.2 |
| Concepto del supervisor, subjetivo, de 4 valores | C | APROBADO | §8.2 |
| Rondas: mesa, número correlativo, inicio y cierre | C | APROBADO | §8.3 |
| Apuestas: datos, mínimo, máximo y saldo | C | APROBADO | §8.4 |
| Póker: registro tras el resultado | C | APROBADO | §8.4 |
| Resultado: Ganada o Perdida, premio = monto × factor, acreditación, consolidación, sin duplicados | C | APROBADO | §8.5 |
| Suspensión por pérdida diaria y por pausa, 24 h | C | APROBADO | §8.6 |
| Empleado: deuda > 2 salarios, reactivación automática | C | APROBADO | §8.6 |
| Empleado: ganancias > 1 salario → investigación | C | APROBADO | §8.6 |
| Código fuente e instrucciones | P §8 | APROBADO | `README.md` |
| Documento técnico | P §8 | APROBADO | `docs/documento-tecnico.md` |
| Evidencia: datos de prueba y capturas | P §8 | APROBADO | §15, §16 |
| Análisis crítico y diapositivas | P §10 | NO VERIFICADO (pendiente) | §18 |
| Pruebas automáticas | — | APROBADO (125/125) | §17 |
| Sin funciones fuera de alcance | P, C | APROBADO | §10 |
