# Matriz de cumplimiento — Proyecto Casino (Laravel)

Fuentes de verdad:

- **P** = `2026.3-ProyectoCasinoxFrameworks (1).pdf` (enunciado del proyecto).
- **C** = `2026.09.15-Parcial_1-contexto (1).pdf` (contexto y reglas del casino).
- **U** = instrucciones del usuario (entorno Rocky Linux, cuatro casos de uso, cuatro funcionalidades adicionales).

Estados: Pendiente · En implementación · Implementado, pendiente de verificación · Verificado · Bloqueado.

## 1. Requisitos técnicos y de proyecto

| ID | Requisito (fiel al documento) | Origen | Implementación prevista | Dependencias | Pruebas previstas | Estado | Evidencia |
|---|---|---|---|---|---|---|---|
| RT-01 | Desarrollar todo el proyecto exclusivamente con el framework asignado (Laravel). | P §2, §7 | Proyecto Laravel 13 sobre PHP 8.4 instalado en el espacio del usuario (D-18), Blade, Eloquent, migraciones | PHP 8.4, Composer | `php artisan test` pasa | Verificado | Laravel Framework 13.39.0 sobre PHP 8.4.1 en la VM; 121 pruebas pasan (docs/evidencias/pruebas-php-artisan-test.txt). |
| RT-02 | Usar una base de datos relacional o NoSQL e implementar persistencia de datos. | P §7 | Base relacional SQLite en archivo (`database/database.sqlite`), con claves foráneas y migraciones (D-17) | RT-01 | Pruebas de persistencia (`assertDatabaseHas`) | Verificado | 13 tablas creadas por migraciones en SQLite, con claves foráneas, índices únicos y restricciones CHECK; los datos persisten entre sesiones (seeder y operaciones en el navegador). |
| RT-03 | Manejar correctamente la lógica de negocio planteada. | P §7 | Servicios de dominio (RN-01…RN-18) | Todas las RN | Pruebas por regla | Verificado | RN-01…RN-18 verificadas, cada una con sus pruebas (sección 5). |
| RT-04 | Seguir una arquitectura coherente con el framework. | P §7 | MVC de Laravel: rutas, controladores, Form Requests, modelos, servicios, Blade | RT-01 | Revisión de estructura | Verificado | Rutas + middleware, Form Requests, controladores, 5 servicios de dominio, modelos Eloquent, enums, notificaciones y Blade; código formateado con Laravel Pint. |
| RT-05 | La aplicación se ejecuta en la máquina virtual Rocky Linux. | U | Despliegue con `php artisan serve` en 127.0.0.1, acceso por túnel SSH | Acceso SSH a la VM | Prueba de humo en la VM | Verificado | `php84 artisan serve` en 127.0.0.1:8000 de la VM 10.43.98.50 (`~/casino`), usada desde el navegador por túnel SSH; capturas en docs/evidencias. |

## 2. Autenticación y roles

| ID | Requisito | Origen | Implementación prevista | Dependencias | Pruebas previstas | Estado | Evidencia |
|---|---|---|---|---|---|---|---|
| RF-AUT-01 | Sistema de login. | P §4 | `LoginController`, vista de login | RT-01 | Login correcto e incorrecto | Verificado | 14 pruebas PHPUnit pasan en la VM (2026-10-29). Navegador real por túnel: login de admin y jugador1 correcto; el formulario envía solo `_token`, `nonce`, `respuesta` y `username`, nunca la contraseña. |
| RF-AUT-02 | Manejo de sesiones. | P §4 | Guard `web` de Laravel (sesión), regeneración de sesión al entrar, logout | RF-AUT-01 | Rutas protegidas exigen sesión; logout invalida sesión | Verificado | 14 pruebas PHPUnit pasan en la VM (2026-10-29). Navegador real por túnel: login de admin y jugador1 correcto; el formulario envía solo `_token`, `nonce`, `respuesta` y `username`, nunca la contraseña. |
| RF-AUT-03 | Almacenamiento de contraseñas usando SHA-256. | P §4 | Columna `users.password` = SHA-256 hex (64 caracteres); sin `bcrypt` | RF-AUT-04 | La BD guarda exactamente SHA-256(contraseña) | Verificado | 14 pruebas PHPUnit pasan en la VM (2026-10-29). Navegador real por túnel: login de admin y jugador1 correcto; el formulario envía solo `_token`, `nonce`, `respuesta` y `username`, nunca la contraseña. |
| RF-AUT-04 | Autenticación de usuarios «Zero Knowledge». | P §3 | El navegador calcula SHA-256 (Web Crypto); el servidor nunca recibe la contraseña; login por desafío con nonce de un solo uso (ver D-01) | RF-AUT-03 | Login con respuesta correcta, con nonce reutilizado, con texto plano | Verificado | 14 pruebas PHPUnit pasan en la VM (2026-10-29). Navegador real por túnel: login de admin y jugador1 correcto; el formulario envía solo `_token`, `nonce`, `respuesta` y `username`, nunca la contraseña. |
| RF-ROL-01 | Según el rol, mostrar únicamente las funcionalidades permitidas: Administrador, crupier, supervisor, jugador. | P §4, C | Columna `users.rol`; menú por rol | RF-AUT-01 | Cada rol ve solo su menú | Verificado | Menú por rol y middleware `rol` en todas las rutas; PaginasTest carga cada página de cada rol (200) y las ajenas (403); 115 pruebas en la VM (2026-10-29). |
| RF-ROL-02 | El control de acceso se aplica también en el servidor. | P §4 (indispensable) | Middleware de rol en rutas + comprobaciones de pertenencia (crupier/supervisor asignado) | RF-ROL-01 | Accesos denegados (403) por rol | Verificado | Menú por rol y middleware `rol` en todas las rutas; PaginasTest carga cada página de cada rol (200) y las ajenas (403); 115 pruebas en la VM (2026-10-29). |

## 3. Casos de uso (P §5) — se implementan cuatro

| ID | Requisito | Origen | Implementación prevista | Dependencias | Pruebas previstas | Estado | Evidencia |
|---|---|---|---|---|---|---|---|
| CU-01 | Gestión de mesas (CRUD con las restricciones del contexto), incluida su asignación de crupier y supervisor por turno. | P §5; C | CRUD de mesas (Admin), turnos del día y asignaciones; registro del concepto (Supervisor) | FA-02, RN-03…RN-08 | CRUD, validaciones, borrado bloqueado con historial | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| CU-02 | Gestión de rondas. | P §5; C | Abrir ronda (número correlativo, hora de inicio), cerrar con resultado (hora de cierre), consultar, anular ronda abierta sin apuestas | CU-01, RN-09 | Correlativo, una sola abierta, solo el crupier asignado | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| CU-03 | Gestión de apuestas. | P §5; C | Registrar, consultar, modificar y anular apuestas pendientes (Crupier) | CU-02, FA-04, RN-10, RN-11, RN-13 | Mínimo, máximo, saldo, autorización, juego sin registro detallado | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| CU-04 | Gestión de ganancias. | P §5; C | Procesamiento automático del resultado (Ganada/Perdida, premio, consolidado del casino) y consulta de ganancias | CU-03, RN-12 | Premio = monto × factor, saldo, sin doble proceso | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |

## 4. Funcionalidades adicionales (P §6)

| ID | Requisito | Origen | Implementación prevista | Dependencias | Pruebas previstas | Estado | Evidencia |
|---|---|---|---|---|---|---|---|
| FA-00 | Interfaz de usuario (obligatoria). | P §6 | Vistas Blade con un CSS propio; formularios, listados, mensajes de validación, estados | Todos | Operaciones vía HTTP de formularios | Verificado | Menú por rol y middleware `rol` en todas las rutas; PaginasTest carga cada página de cada rol (200) y las ajenas (403); 115 pruebas en la VM (2026-10-29). |
| FA-01 | Notificaciones. | P §6 | Notificaciones de Laravel en base de datos ante eventos del contexto: resultado de apuestas, suspensión, investigación, reactivación, concepto «Sospecha de fraude» | CU-04, FA-04, RN-07 | Se crean y se marcan como leídas | Verificado | 115 pruebas pasan en la VM (NotificacionesTest, HistorialTest, PaginasTest; 2026-10-29). Navegador: «Mi historial» de jugador1 con su apuesta, totales y pausa; 403 en páginas de administración. |
| FA-02 | Gestión de empleados. | P §6 | CRUD de empleados (Admin): documento, nombre, rol, salario mensual, competencias por juego, cuenta de acceso | RF-ROL-01 | CRUD, competencias, borrado bloqueado con historial | Verificado | CRUD con cuenta SHA-256 y competencias verificado con pruebas y en el navegador (alta de crupier, login, 403 en administración); borrado bloqueado con perfil de jugador o asignaciones. |
| FA-03 | Historial de clientes. | P §6 | Ficha del jugador: datos, saldo, estado, apuestas, suspensiones, abonos (Admin y el propio jugador) | CU-03, CU-04, FA-04 | Un jugador solo ve su propio historial | Verificado | 115 pruebas pasan en la VM (NotificacionesTest, HistorialTest, PaginasTest; 2026-10-29). Navegador: «Mi historial» de jugador1 con su apuesta, totales y pausa; 403 en páginas de administración. |
| FA-04 | Validación de autorizaciones/suspensiones. | P §6 | Servicio único `puedeApostar`; aplicación automática de RN-14…RN-18; pausa voluntaria; fin de investigación; abonos | RN-14…RN-18 | Apuesta rechazada en cada estado; reactivación | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |

## 5. Reglas de negocio del contexto (C)

| ID | Regla (fiel al documento) | Implementación prevista | Pruebas previstas | Estado | Evidencia |
|---|---|---|---|---|---|
| RN-01 | Registro obligatorio del jugador antes de participar: código único de cliente, documento de identidad, nombre completo, edad, saldo actual en fichas. | Tabla `jugadores` con unicidad de código y documento; solo jugadores registrados pueden apostar | Unicidad, campos obligatorios | Verificado | Pruebas de JugadoresTest en la VM (31 pruebas en total, 2026-10-29). |
| RN-02 | La edad se usa para validar que no son menores. | Validación edad ≥ 18 (D-02) | Rechazo de 17, aceptación de 18 | Verificado | Pruebas de JugadoresTest en la VM (31 pruebas en total, 2026-10-29). |
| RN-03 | Mesa: identificador único, tipo de juego, monto mínimo y máximo de apuesta por jugada. | Tabla `mesas`; mínimo ≤ máximo | Validaciones del CRUD | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-04 | La mesa está asignada a un crupier durante un turno; un crupier solo está a cargo de una mesa a la vez por turno. | Tabla `asignaciones` con unicidad (turno, mesa) y (turno, crupier) | Doble asignación rechazada | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-05 | Cada turno se rotan los crupiers y no deben repetir mesa durante una misma jornada. | Validación: el crupier no tiene otra asignación a esa mesa en la misma fecha | Repetición rechazada | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-06 | La duración de los turnos se define cada día y no puede superar las 2 horas. | Tabla `turnos` (fecha, hora inicio, hora fin); duración ≤ 120 min; sin solaparse en el día (D-10) | 2 h aceptado, 2 h 1 min rechazado, solape rechazado | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-07 | Cada mesa tiene un supervisor que, al final de cada turno, emite un concepto: «Genera ganancias», «Genera pérdidas», «Sospecha de fraude» o «Comportamiento neutro»; es subjetivo, no se calcula. | Campo `concepto` en la asignación, elegido por el supervisor asignado tras el fin del turno (D-11) | Solo el supervisor asignado, solo tras el turno, solo valores permitidos | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-08 | A una mesa solo se asignan crupiers y supervisores que conozcan la mecánica de su juego. | Tabla `competencias` (empleado × juego); validación en la asignación | Asignación sin competencia rechazada | Verificado | Pruebas MesasTest, TurnosTest, AsignacionesTest y ConceptoTest pasan en la VM (55 en total, 2026-10-29); páginas de administración cargadas en el navegador sin errores. |
| RN-09 | Ronda: pertenece a una mesa, número correlativo, hora de inicio y de cierre; durante ella los jugadores hacen una o más apuestas. | Tabla `rondas`; correlativo por mesa; una ronda abierta por mesa (D-15) | Numeración 1, 2, 3; segunda ronda abierta rechazada | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| RN-10 | En juegos con registro detallado, la apuesta registra jugador, ronda, tipo de apuesta, monto y factor de pago. | Tabla `apuestas` | Campos obligatorios | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| RN-11 | Verificar estrictamente que el monto no sea inferior al mínimo ni superior al máximo de la mesa, y que el jugador tenga saldo suficiente. | Validación en el servicio de apuestas, con bloqueo de fila del jugador | Bordes mínimo/máximo, saldo insuficiente | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| RN-12 | Al finalizar la ronda el crupier ingresa el resultado; el sistema procesa cada apuesta: coincide → Ganada, premio = monto × factor acreditado inmediatamente; no coincide → Perdida, monto consolidado a favor del casino. | Cierre transaccional de la ronda (D-03, D-04) | Saldos, estados, cierre repetido no duplica premios | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| RN-13 | En juegos sin registro detallado (como el póker) las apuestas solo se registran una vez se evidencia el resultado. | Atributo `registro_detallado` del juego; apuestas posteriores al cierre (D-05) | Póker: apuesta antes del resultado rechazada, después aceptada | Verificado | 77 pruebas pasan en la VM (RondasTest, ApuestasTest, ResultadoTest, GananciasTest, PaginasTest; 2026-10-29). Navegador: ronda 1 abierta, apuesta de 5 rechazada por mínimo, apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. |
| RN-14 | Si un jugador acumula pérdidas superiores a un umbral configurado dentro de un mismo día, su cuenta pasa a «Suspendido» y no puede apostar en ninguna mesa durante 24 horas. | Evaluación tras cada apuesta resuelta (D-06, D-09) | Suspensión al superar el umbral; apuesta rechazada; reapertura a las 24 h | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |
| RN-15 | Si el jugador solicita voluntariamente una pausa, su cuenta pasa a «Suspendido» 24 horas. | Acción «Solicitar pausa» del jugador | Suspensión de 24 h | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |
| RN-16 | Los empleados pueden apostar como cualquier cliente. | Perfil de jugador vinculado a la cuenta del empleado (D-13) | Empleado registrado como jugador apuesta | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |
| RN-17 | Empleado con pérdidas acumuladas superiores a 2 meses de salario → «Suspendido»; al subsanar la deuda y bajar de ese límite, vuelve a jugar automáticamente hasta que lo supere de nuevo. | Evaluación tras cada apuesta resuelta y tras cada abono (D-06, D-07, D-16) | Suspensión; abono que reactiva | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |
| RN-18 | Empleado con ganancias superiores a 1 mes de salario → «Investigación»; no puede apostar hasta que finalice la investigación. | Suspensión de tipo investigación; el Administrador la finaliza (D-08) | Bloqueo y desbloqueo | Verificado | 99 pruebas pasan en la VM (SuspensionesTest, AbonosTest, PausaTest; 2026-10-29). Navegador: jugador1 pide la pausa y queda «Suspendido» 24 h. |

## 6. Entregables (P §8)

| ID | Entregable | Implementación prevista | Estado | Evidencia |
|---|---|---|---|---|
| EN-01 | Código fuente: proyecto completo funcional e instrucciones de instalación. | Repositorio + `README.md` | Verificado | Repositorio del proyecto y README.md (requisitos, instalación, cuentas de demostración, pruebas y despliegue en la VM). |
| EN-02 | Documento técnico: descripción del framework, arquitectura, modelo de datos, funcionalidades implementadas, dificultades encontradas. | `docs/documento-tecnico.md` | Verificado | docs/documento-tecnico.md. |
| EN-03 | Evidencia de funcionamiento: datos de prueba y capturas o demostración funcional. | Seeder de demostración + `docs/evidencias/` | Verificado | Seeder de demostración (DemoSeederTest), 11 capturas y la salida de las pruebas en docs/evidencias. |

## 7. Selección de casos de uso

Se eligen **CU-01 Mesas, CU-02 Rondas, CU-03 Apuestas y CU-04 Ganancias**:

- Forman la cadena completa del juego que describe el contexto (mesa → ronda → apuesta → resultado y premio), y casi todas las reglas del contexto (RN-03…RN-13) recaen sobre ellos.
- Las suspensiones (RN-14…RN-18) deben funcionar igualmente porque P §7 exige manejar toda la lógica de negocio; quedan cubiertas por FA-04 y no hace falta elegirlas como caso de uso.
- Consignaciones/retiros se descarta: el contexto no define ninguna regla sobre ellas; el saldo en fichas es un dato del registro del jugador (D-12).

Dependencias: CU-01 necesita FA-02 (empleados y competencias). CU-02 necesita CU-01 (la mesa y el crupier asignado al turno). CU-03 necesita CU-02, el registro de jugadores (RN-01) y FA-04. CU-04 necesita CU-03. FA-03 y FA-01 consumen lo que producen CU-04 y FA-04.

## 8. Decisiones de interpretación

| ID | Decisión | Motivo |
|---|---|---|
| D-01 | Zero Knowledge: la contraseña nunca sale del navegador. Al crear o cambiar una contraseña, el navegador envía SHA-256(contraseña) y el servidor lo guarda. Al entrar, el servidor entrega un nonce de un solo uso guardado en la sesión y el navegador envía SHA-256(SHA-256(contraseña) + nonce); el servidor lo compara con SHA-256(hash guardado + nonce). Solo se usa SHA-256 (Web Crypto del navegador y `hash('sha256')` de PHP). | P §3 exige «Zero Knowledge» y P §4 exige SHA-256. Web Crypto solo existe en contextos seguros, por eso el acceso es por `localhost` (túnel SSH), sin abrir puertos. |
| D-02 | Edad mínima 18 años. | C pide validar «que no son menores»; la mayoría de edad en Colombia es 18. |
| D-03 | Al registrar una apuesta se descuenta el monto del saldo. Si gana se acredita el premio = monto × factor; si pierde, el monto ya descontado queda consolidado a favor del casino. | C: «se consolida a favor del casino» y «saldo suficiente»; evita gastar dos veces el mismo saldo con varias apuestas. |
| D-04 | Al cerrar la ronda el crupier escribe el resultado ganador y marca, entre los tipos de apuesta registrados en la ronda, los que coinciden con él; el sistema procesa automáticamente cada apuesta. | C no define la mecánica de cada juego; así la coincidencia es exacta y no se inventan reglas de juego. |
| D-05 | «Registro detallado» es un atributo del juego (Ruleta y Blackjack sí; Póker no). En mesas sin registro detallado, la ronda se cierra con su resultado y después el crupier registra cada apuesta indicando si fue Ganada o Perdida; se procesa al registrarla con las mismas validaciones. | C: el póker solo registra apuestas una vez se evidencia el resultado. |
| D-06 | Pérdidas y ganancias = resultado neto de las apuestas resueltas (premios − montos apostados). Diario: el día calendario. Empleado: acumulado histórico menos abonos para pérdidas; ganancias contadas desde la última investigación finalizada. | C habla de «deuda» que «disminuye»; sin el corte, un empleado quedaría en investigación para siempre tras la primera. |
| D-07 | Subsanar la deuda: el Administrador registra abonos del empleado; tras cada abono se reevalúa y la suspensión se levanta sola si la pérdida neta ya no supera 2 salarios. | C dice que vuelve a jugar «automáticamente» al subsanar la deuda, pero no indica cómo se registra el pago. |
| D-08 | La investigación la finaliza el Administrador, sin más pasos. | C no define el procedimiento; no se inventa uno. |
| D-09 | El umbral diario es la variable de configuración `CASINO_UMBRAL_PERDIDA_DIARIA` (`config/casino.php`). Los datos de demostración usan un valor de ejemplo documentado. | C dice «umbral configurado» sin dar un número. |
| D-10 | Los turnos de un mismo día no pueden solaparse ni cruzar la medianoche. | Necesario para que «una mesa a la vez por turno» tenga sentido. |
| D-11 | El concepto lo registra solo el supervisor asignado, una vez terminado el turno y una sola vez. | C: «al final de cada turno el supervisor emitirá un concepto». |
| D-12 | El Administrador registra a los jugadores (con su saldo inicial en fichas y su cuenta de acceso). Después, el saldo solo cambia por apuestas y premios. | Consignaciones/retiros no forma parte de los casos elegidos. |
| D-13 | Un empleado puede tener un perfil de jugador vinculado a su propia cuenta. | C: los empleados apuestan como cualquier cliente. |
| D-14 | El crupier solo opera rondas y apuestas de las mesas que tiene asignadas en el turno en curso. Si una ronda quedó abierta al terminar un turno, la cierra el crupier asignado a esa mesa en el turno siguiente. | C: la mesa está asignada a un crupier durante un turno. Sin esa salida, una ronda abierta bloquearía la mesa para siempre (D-15). |
| D-15 | Una sola ronda abierta por mesa; el número es correlativo por mesa. | C: número correlativo de la ronda dentro de la mesa. |
| D-16 | «Salario» es el salario mensual del empleado. | C: «salario de 2 meses» y «1 mes de salario». |
| D-17 | Motor SQLite. PostgreSQL 13 existe en la VM, pero el usuario `estudiante` no puede crear bases y la única base propia pertenece a otro proyecto (no se reutiliza). Las migraciones no dependen del motor: pasar a PostgreSQL es cambiar `DB_CONNECTION` cuando exista una base propia. | Sin permisos de administrador; no reutilizar bases anteriores. |
| D-18 | Laravel 13 sobre el PHP 8.4 oficial de Laravel (php.new / Herd Lite), instalado sin `sudo` en `~/.local/php84` del usuario `estudiante` e invocado como `php84`. Se descartó Laravel 9, lo máximo que admite el PHP 8.0.30 del sistema: ya no tiene soporte y Composer bloquea todas sus versiones por avisos de seguridad sin parche. | Framework con soporte de seguridad, sin permisos de administrador y sin tocar el PHP del sistema que usan Apache y php-fpm. |
| D-19 | Ejecución con `php artisan serve` en 127.0.0.1 de la VM y acceso por túnel SSH (`ssh -L 8000:127.0.0.1:8000`). No se toca Apache ni se abren puertos. | Acceso autorizado por SSH; contexto seguro (localhost) para Web Crypto (D-01). |
| D-20 | Zona horaria de la aplicación `America/Bogota`, la misma de la VM. Las horas de los turnos y el «mismo día» de RN-14 se miden en ella. | Laravel usa UTC por defecto, lo que desplazaría 5 horas los turnos y el día calendario. |
| D-21 | Las cuentas de demostración se crean con el seeder solo si se define `CASINO_DEMO_PASSWORD` en `.env` al instalar; esa contraseña nunca va al repositorio. | No guardar contraseñas en el repositorio y, aun así, entregar datos de prueba reproducibles. |
| D-22 | Saldos, montos, premios, abonos y salarios en fichas con dos decimales (`decimal(14,2)`); factor de pago `decimal(8,2)` mayor que 0. Los cálculos de dinero usan BCMath (incluido en el PHP 8.4 instalado), no números de coma flotante; el premio se trunca a centésimas (por ejemplo, 10,05 × 1,5 = 15,07). | Premio = monto × factor puede tener decimales (por ejemplo, factor 1,5); la coma flotante acumula errores de redondeo en los saldos. |
| D-23 | El navegador exige al menos 8 caracteres al crear o cambiar una contraseña. El servidor no puede comprobarlo porque solo recibe el SHA-256 (D-01); valida que sea un SHA-256 hexadecimal. | Consecuencia directa de Zero Knowledge. |
| D-24 | Ganancias: el Administrador ve todas las mesas; el supervisor, solo las rondas de las asignaciones que él supervisó. Resultado neto del casino = total apostado − total pagado en premios; «consolidado a favor del casino» = suma de los montos de las apuestas perdidas. | C asigna un supervisor por mesa y turno; RN-12 define el consolidado como el monto de las apuestas perdidas. |

## 9. Fuera de alcance

Consignaciones y retiros, recuperación de contraseña, registro público, canales de notificación externos (correo, SMS), reportes o gráficas, colas, cachés externos, contenedores y servicios en la nube.

## 10. Plan técnico

### Entorno verificado (VM 10.43.98.50, usuario `estudiante`)

Rocky Linux 9.8; PHP 8.0.30 del sistema (sin `zip` ni `unzip`, no se usa); git 2.52; PostgreSQL 13 (sin permiso CREATEDB); SELinux en modo Enforcing; Apache y php-fpm ya en uso por otros proyectos (no se tocan); sin `sudo`.

Instalado para el proyecto, sin `sudo`: PHP 8.4.1 en `~/.local/php84` (comando `~/.local/bin/php84`, con `pdo_sqlite`, `sqlite3`, `zip`, `mbstring`, `openssl`, `intl`) y Composer 2.11.0 en `~/.local/bin/composer`. Esqueleto creado: Laravel Framework 13.39.0 en `~/casino`.

### Modelo de datos

| Tabla | Columnas | Restricciones | Requisitos |
|---|---|---|---|
| `users` | id, username, password (SHA-256 hex), rol | username único; rol ∈ {administrador, crupier, supervisor, jugador} | RF-AUT-*, RF-ROL-* |
| `empleados` | id, user_id, documento, nombre_completo, salario_mensual | user_id y documento únicos | FA-02, RN-17, RN-18 |
| `juegos` | id, nombre, registro_detallado | nombre único; datos iniciales Ruleta, Blackjack (detallado) y Póker (no detallado) | RN-03, RN-13 |
| `competencias` | empleado_id, juego_id | clave primaria compuesta | RN-08 |
| `jugadores` | id, user_id, codigo_cliente, documento, nombre_completo, edad, saldo | user_id, código y documento únicos; edad ≥ 18 | RN-01, RN-02, RN-16 |
| `mesas` | id, codigo, juego_id, apuesta_minima, apuesta_maxima | código único; mínimo ≤ máximo | RN-03 |
| `turnos` | id, fecha, hora_inicio, hora_fin | duración ≤ 2 h; sin solape en la fecha | RN-06 |
| `asignaciones` | id, turno_id, mesa_id, crupier_id, supervisor_id, concepto, concepto_registrado_en | únicos (turno, mesa) y (turno, crupier); competencias; sin repetir mesa en la jornada | RN-04, RN-05, RN-07, RN-08 |
| `rondas` | id, mesa_id, asignacion_id, numero, hora_inicio, hora_cierre, resultado | único (mesa, número); una abierta por mesa | RN-09, RN-12 |
| `apuestas` | id, ronda_id, jugador_id, tipo_apuesta, monto, factor_pago, estado, premio, resuelta_en | estado ∈ {Pendiente, Ganada, Perdida} | RN-10…RN-13 |
| `suspensiones` | id, jugador_id, motivo, inicio, fin | motivo ∈ {perdida_diaria, pausa_voluntaria, perdida_empleado, investigacion}; activa si fin es nulo o futuro | RN-14…RN-18 |
| `abonos` | id, jugador_id, monto | solo jugadores que son empleados; monto ≤ deuda | RN-17 (D-07) |
| `notifications` | tabla estándar de notificaciones de Laravel | | FA-01 |

### Arquitectura mínima

MVC de Laravel en un único proyecto: rutas `web` agrupadas por el middleware `auth` y un middleware de rol; controladores por recurso; Form Requests para la validación; servicios de dominio en `app/Services` para las reglas que cruzan entidades (apuestas, cierre de ronda, suspensiones, asignaciones); modelos Eloquent; notificaciones de Laravel por el canal `database`; vistas Blade con un único CSS propio en `public/css`. Sin Vite, sin npm, sin paquetes de frontend.

### Dependencias

| Dependencia | Motivo |
|---|---|
| PHP 8.4 oficial de Laravel en `~/.local/php84` | Laravel 13 exige PHP ≥ 8.3 (D-18); sin `sudo`. |
| Composer 2 en `~/.local/bin` (instalador oficial con verificación de firma) | Necesario para instalar Laravel; sin `sudo`. |
| Esqueleto `laravel/laravel` 13.x con sus dependencias por defecto | Framework asignado. Se retiran Vite, npm y los archivos de Laravel Boost (`AGENTS.md`/`CLAUDE.md` del esqueleto), que no se usan. |
| PHPUnit 12 (incluido en el esqueleto) | Pruebas automáticas. |

### Fases

1. F1 — Esqueleto, autenticación Zero Knowledge con SHA-256, roles, diseño base (RT-01…RT-04, RF-AUT-*, RF-ROL-*, FA-00).
2. F2 — Empleados y competencias; registro de jugadores (FA-02, RN-01, RN-02, RN-16).
3. F3 — Mesas, turnos, asignaciones y concepto del supervisor (CU-01, RN-03…RN-08).
4. F4 — Rondas y apuestas con validaciones (CU-02, CU-03, RN-09…RN-11, RN-13).
5. F5 — Procesamiento de resultados y ganancias (CU-04, RN-12).
6. F6 — Suspensiones, investigación, abonos y validación (FA-04, RN-14…RN-18).
7. F7 — Notificaciones e historial de clientes (FA-01, FA-03).
8. F8 — Datos de prueba, despliegue en la VM, README, documento técnico y evidencias (RT-05, EN-01…EN-03).

### Estrategia de pruebas

Pruebas de característica de Laravel (PHPUnit) sobre SQLite en memoria, el mismo motor que en producción. Cada prueba lleva en su nombre el ID del requisito. Las reglas que dependen del tiempo (turnos, 24 horas) usan `Carbon::setTestNow`. Codex escribe código y pruebas; Claude sincroniza con la VM, ejecuta `php artisan test` allí, revisa cada diff y valida en el navegador a través del túnel.

## 11. Registro de verificación

Todas las fechas son del 29/10/2026. Cada fase pasó por implementación de Codex, revisión del código por Claude, ejecución de `php84 artisan test` en la VM, formateo con Pint y comprobación en el navegador por el túnel SSH.

| Fase | Pruebas en la VM | Comprobación en el navegador | Defectos encontrados en la revisión y corregidos |
|---|---|---|---|
| F1 | 14/14 | Login de `admin` y `jugador1`; el formulario envía solo `_token`, `nonce`, `respuesta` y `username`. | — |
| F2 | 31/31 | Alta de un crupier desde el formulario: la base guarda exactamente SHA-256 de la contraseña; ese crupier entra y recibe 403 en administración. | Tabla deducida en inglés (`jugadors`); parámetro de ruta `{jugadore}`; error 500 con documento repetido; perfil de jugador no sincronizado al editar el empleado. |
| F3 | 55/55 | Las 14 páginas de administración responden 200; turno con su asignación. | Método `check()` inexistente en la migración; código comprimido en una línea (formateado con Pint). |
| F4–F5 | 77/77 | Ronda 1 abierta; apuesta de 5 rechazada por el mínimo; apuesta de 100 × 2 ganada al cerrar con «Rojo 7»; saldo 1000 → 1100. | Directiva Blade `@endif` no compilada; ayudante de pruebas que no descontaba el saldo; regla D-14 duplicada en tres controladores; el supervisor veía ganancias de turnos ajenos; texto con doble codificación UTF-8. |
| F6 | 99/99 | `jugador1` pide la pausa y queda «Suspendido» 24 h. | «Mi cuenta» sin código de cliente. |
| F7 | 115/115 | «Mi historial» de `jugador1` con apuesta, totales y pausa; 403 en administración. | Vista de paginación inexistente en Laravel 13 (sustituida por una propia); expectativa de saldo errónea en una prueba. |
| F8 | 121/121 | Recorrido completo con los datos del seeder (capturas 01–11 de `docs/evidencias`). | Escenario de demostración incoherente con RN-18 y RN-14; código y documento del cliente intercambiados; prueba que exigía 3 asignaciones por turno; casillas mal dibujadas. |
