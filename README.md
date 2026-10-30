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

## Requisitos

- PHP 8.3 o superior con las extensiones `pdo_sqlite`, `sqlite3`, `bcmath`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype` y `fileinfo`.
- Composer 2.
- Un navegador moderno que abra la aplicación por `http://localhost` o por `https`. El cálculo de SHA-256 en el navegador (Web Crypto) solo funciona en esos contextos seguros.

No hace falta Node.js ni npm.

## Instalación

```bash
cd Proyecto-Casino
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Edita `.env`:

- `CASINO_DEMO_PASSWORD`: contraseña de las cuentas de demostración. Elígela tú; nunca se guarda en el repositorio. Si la dejas vacía, el seeder no crea datos.
- `CASINO_UMBRAL_PERDIDA_DIARIA`: pérdida neta diaria a partir de la cual un jugador queda suspendido 24 horas. El valor de ejemplo es `1000.00` fichas.

Crea las tablas y los datos de demostración, y arranca el servidor:

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Abre <http://localhost:8000>.

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
