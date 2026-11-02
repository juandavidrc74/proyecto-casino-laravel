# Casino — Proyecto de Bases de Datos con Laravel

Aplicación web para gestionar un casino: mesas, turnos con crupier y supervisor, rondas, apuestas, procesamiento de resultados, ganancias, suspensiones de jugadores, notificaciones e historial de clientes. Proyecto de la asignatura Bases de Datos, Administración y Aplicaciones (Pontificia Universidad Javeriana).

- Framework: Laravel 13 (PHP 8.4), vistas Blade, Eloquent y migraciones.
- Base de datos: SQLite (`database/database.sqlite`).
- Autenticación «Zero Knowledge» con SHA-256: la contraseña nunca sale del navegador (ver [documento técnico](docs/documento-tecnico.md)).
- Roles: Administrador, Crupier, Supervisor y Jugador, con autorización en el servidor.

## Integrantes

- Juan David Ramírez
- Alejandro Doncel
- Dana Katalina Díaz

## Documentación

- [docs/documento-tecnico.md](docs/documento-tecnico.md): framework, arquitectura, modelo de datos, funcionalidades y dificultades.
- [docs/matriz-cumplimiento.md](docs/matriz-cumplimiento.md): cada requisito del enunciado y del contexto, con su estado, sus pruebas y las decisiones de interpretación.
- [docs/evidencias/](docs/evidencias/): capturas de la aplicación en funcionamiento.
- [docs/GUIA_FUNCIONAL_SUSTENTACION.md](docs/GUIA_FUNCIONAL_SUSTENTACION.md): cómo se usa cada funcionalidad, paso a paso y por rol.
- [docs/GUIA_AUDITORIA_USO_Y_PRUEBAS_CASINO.md](docs/GUIA_AUDITORIA_USO_Y_PRUEBAS_CASINO.md): auditoría completa contra el enunciado, manual de uso y plan de pruebas funcionales.

## Requisitos

- **PHP 8.3 o superior** (probado con PHP 8.4) con las extensiones `pdo_sqlite`, `sqlite3`, `bcmath`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype` y `fileinfo`. Con `php -v` ves la versión y con `php -m` las extensiones activas. En Windows (por ejemplo, con XAMPP), las que falten se activan quitando el `;` de su línea `extension=` en `php.ini`.
- **Composer 2** (<https://getcomposer.org>).
- Un navegador moderno que abra la aplicación por `http://localhost` o por `https`. El cálculo de SHA-256 en el navegador (Web Crypto) solo funciona en esos contextos seguros.

No hace falta Node.js, npm ni un servidor de base de datos: la base es un archivo SQLite.

## Instalación

**1. Obtén el código** y entra a su carpeta:

```bash
git clone https://github.com/juandavidrc74/proyecto-casino-laravel.git
cd proyecto-casino-laravel
```

Si lo recibiste en un ZIP, descomprímelo y abre una terminal dentro de la carpeta `proyecto-casino-laravel`.

**2. Instala las dependencias de PHP:**

```bash
composer install
```

**3. Crea el archivo de configuración y la base de datos vacía:**

| Paso | Linux y macOS | Windows (PowerShell) |
|---|---|---|
| Copiar la configuración | `cp .env.example .env` | `Copy-Item .env.example .env` |
| Crear la base SQLite | `touch database/database.sqlite` | `New-Item database\database.sqlite -ItemType File` |

Después, en cualquier sistema:

```bash
php artisan key:generate
```

**4. Edita `.env`:**

- `CASINO_DEMO_PASSWORD`: contraseña de las cuentas de demostración. Elígela tú; nunca se guarda en el repositorio. **Si la dejas vacía, el seeder no crea datos.**
- `CASINO_UMBRAL_PERDIDA_DIARIA`: pérdida neta diaria a partir de la cual un jugador queda suspendido 24 horas. El valor de ejemplo es `1000.00` fichas.

**5. Crea las tablas y los datos de demostración, y arranca el servidor:**

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

**6. Abre <http://localhost:8000>** y entra con `admin` y la contraseña que pusiste en `CASINO_DEMO_PASSWORD`.

Los datos de demostración crean un **turno en curso de 2 horas** desde la hora en punto en que se cargan. Pasado ese tiempo, los crupieres ya no tienen mesa. Para seguir probando, vuelve a ejecutar `php artisan migrate:fresh --seed`, que borra todo y recarga los datos, o crea un turno nuevo con sus asignaciones desde el menú «Turnos».

Para comprobar la instalación, ejecuta las pruebas automáticas (sección [Pruebas](#pruebas)). Deben pasar las 125.

### Acceso desde otra máquina

El servidor escucha solo en `127.0.0.1`. Para usarlo desde tu equipo, abre un túnel SSH y entra por `localhost`, que el navegador considera un contexto seguro:

```bash
ssh -N -L 8000:127.0.0.1:8000 usuario@servidor
```

## Cuentas de demostración

Todas usan la contraseña definida en `CASINO_DEMO_PASSWORD`.

| Usuario | Rol | Para qué sirve |
|---|---|---|
| `admin` | Administrador | Empleados, jugadores, mesas, turnos y asignaciones, ganancias, suspensiones y abonos. |
| `crupier1`, `crupier2`, `crupier3` | Crupier | «Mi mesa»: abrir y cerrar rondas y registrar apuestas en la mesa del turno en curso. `crupier3` también tiene perfil de jugador. |
| `supervisor1`, `supervisor2` | Supervisor | «Mis conceptos» al terminar cada turno y ganancias de sus mesas. |
| `jugador1`, `jugador2`, `jugador3` | Jugador | «Mi cuenta» (saldo, estado y pausa voluntaria), «Mi historial» y notificaciones. |

El seeder deja:

- un turno en curso con las tres mesas asignadas y una ronda abierta en `R-01`;
- el historial de días anteriores;
- `jugador3` suspendido por pérdida diaria y `jugador2` en pausa voluntaria;
- el perfil de jugador de `crupier3` en investigación. El detalle está en [docs/documento-tecnico.md](docs/documento-tecnico.md).

## Pruebas

```bash
php artisan test
```

Son pruebas de característica de PHPUnit sobre SQLite en memoria. El nombre de cada prueba incluye el requisito que demuestra (por ejemplo `test_rn_11_saldo_insuficiente_se_rechaza`).

## Despliegue en la VM Rocky Linux de la asignatura

La VM tiene PHP 8.0, que no admite versiones de Laravel con soporte, y no hay permisos de administrador. Por eso se instaló, solo para el usuario `estudiante` y sin `sudo`, el PHP 8.4 oficial de Laravel en `~/.local/php84`, con el comando `~/.local/bin/php84`. Composer está en `~/.local/bin/composer`.

```bash
cd ~/casino
php84 ~/.local/bin/composer install
php84 artisan migrate:fresh --seed
php84 artisan serve --host=127.0.0.1 --port=8000
```

No se modificó Apache, php-fpm ni el PHP del sistema, y no se abrió ningún puerto. El acceso es por el túnel SSH descrito arriba.
