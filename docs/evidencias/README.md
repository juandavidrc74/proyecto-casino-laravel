# Evidencias de funcionamiento

Capturas tomadas el 29/10/2026 en la aplicación desplegada en la VM Rocky Linux (`~/casino`, `php84 artisan serve` en 127.0.0.1:8000), abierta desde el navegador del equipo por un túnel SSH a `http://localhost`. Los datos son los del seeder de demostración (`php artisan migrate:fresh --seed`), más las acciones descritas en cada captura.

| Captura | Qué muestra | Requisitos |
|---|---|---|
| [01-login-zero-knowledge.jpg](01-login-zero-knowledge.jpg) | Formulario de inicio de sesión. El campo de contraseña no se envía: el navegador manda solo la respuesta SHA-256 al desafío. | RF-AUT-01…04 |
| [02-turno-en-curso-asignaciones.jpg](02-turno-en-curso-asignaciones.jpg) | Administrador: turno de hoy (15:00–17:00, 2 horas) con las tres mesas asignadas, cada una con un crupier distinto y un supervisor competente en su juego. | CU-01, RN-04, RN-06, RN-08 |
| [03-ganancias-por-mesa.jpg](03-ganancias-por-mesa.jpg) | Ganancias del 24 al 29/10 por mesa: apuestas resueltas, total apostado, premios, consolidado a favor del casino y neto. | CU-04, RN-12 |
| [04-suspensiones.jpg](04-suspensiones.jpg) | Suspensiones activas: pérdida diaria (24 h), pausa voluntaria (24 h) e investigación de un empleado. En el historial, la suspensión por deuda de empleado que un abono levantó. | FA-04, RN-14, RN-15, RN-17, RN-18 |
| [05-notificaciones-administrador.jpg](05-notificaciones-administrador.jpg) | Notificaciones del Administrador: suspensiones automáticas y concepto «Sospecha de fraude». | FA-01, RN-07 |
| [06-apuesta-rechazada-jugador-suspendido.jpg](06-apuesta-rechazada-jugador-suspendido.jpg) | Crupier: la apuesta de un jugador suspendido por pérdida diaria se rechaza con el motivo y la fecha de fin. | FA-04, RN-14 |
| [07-ronda-cerrada-apuesta-procesada.jpg](07-ronda-cerrada-apuesta-procesada.jpg) | Crupier: cierre de la ronda 3 de R-01 con el resultado «Rojo 32». La apuesta a «Rojo» queda Ganada, con premio = 50 × 2 = 100 acreditado al momento. | CU-02, CU-03, RN-09, RN-12 |
| [08-notificaciones-jugador.jpg](08-notificaciones-jugador.jpg) | Jugador: un aviso por cada apuesta resuelta, incluida la recién cerrada. | FA-01 |
| [09-historial-del-jugador.jpg](09-historial-del-jugador.jpg) | Jugador: su historial con datos, saldo (1000 + 200 − 100 + 50 = 1150), estado y totales. | FA-03, RN-01 |
| [10-conceptos-del-supervisor.jpg](10-conceptos-del-supervisor.jpg) | Supervisor: conceptos emitidos por turno; los del turno en curso esperan a que termine y los de turnos terminados se pueden registrar. | RN-07 |
| [11-ronda-abierta-con-apuestas.jpg](11-ronda-abierta-con-apuestas.jpg) | Crupier: ronda 4 de R-01 abierta, con los límites de la mesa (mínimo 10, máximo 500). La apuesta recién registrada (CLI-001, «Negro», 20 × 2) queda Pendiente y se puede modificar mientras la ronda siga abierta. | CU-02, CU-03, RN-09, RN-10, RN-11 |
| [pruebas-php-artisan-test.txt](pruebas-php-artisan-test.txt) | Salida completa de `php artisan test` en la VM: 125 pruebas y 661 aserciones correctas. | Todos |
| [auditoria/](auditoria/) | Auditoría del 01/11/2026: 71 capturas (AUD-01 a AUD-71), resultados de las pruebas contra el servidor, salida de las pruebas, verificación de las correcciones y, del 02/11/2026, la ejecución del recorrido funcional. El índice de capturas está en la sección 16 de [GUIA_AUDITORIA_USO_Y_PRUEBAS_CASINO.md](../GUIA_AUDITORIA_USO_Y_PRUEBAS_CASINO.md). | Todos |
