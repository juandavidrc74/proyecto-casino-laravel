# Guía funcional para la sustentación — Proyecto Casino

Guía práctica para que cada integrante domine una parte de la aplicación **como usuario**, no a nivel técnico, y pueda mostrarla y responder «¿cómo se usaría esto?».

| Parte | Tema | Integrante |
|---|---|---|
| **Persona 1** | Administración: empleados, jugadores, mesas, turnos y asignaciones | |
| **Persona 2** | Operación de la mesa: rondas, apuestas, resultados y póker | |
| **Persona 3** | Control: jugadores, suspensiones, abonos, conceptos, ganancias y notificaciones | |

Los pasos, mensajes y cifras de esta guía se comprobaron el 02/11/2026 con los mismos datos. La comprobación está en [recorrido-funcional-2026-11-02.txt](evidencias/auditoria/recorrido-funcional-2026-11-02.txt); el detalle completo está en los pasos F-01 a F-40 de la [guía de auditoría](GUIA_AUDITORIA_USO_Y_PRUEBAS_CASINO.md) (§13).

---

## Antes de empezar (todos)

1. **Carguen los datos** al comienzo de una hora (entre las :00 y las :15), porque la carga crea un **turno en curso de 2 horas** desde la hora en punto. El comando borra todo y deja los datos de demostración; está en la guía de auditoría, §2.5. Anoten la hora de fin del turno.
2. **Abran la aplicación.** Con el túnel abierto, entren a `http://localhost:8010` (guía de auditoría, §2.2).
3. **Usen dos ventanas:** una normal para `admin` y una de incógnito para los demás usuarios. Antes de cambiar de usuario en una ventana, pulsen «Cerrar sesión».
4. **Contraseñas:**
   - `admin`, `crupier3` y `supervisor1` vienen de la carga y usan la contraseña de demostración (`CASINO_DEMO_PASSWORD`).
   - Los usuarios que crea la Persona 1 (`pedro`, `sofia`, `laura` y `mateo`) usan una contraseña que ustedes elijan, de al menos 8 caracteres.
5. **Orden:** la primera vez, háganlo en orden: Persona 1 → Persona 2 → Persona 3. Cada parte usa lo que dejó la anterior. En total toma unos 45 minutos.

**Qué ve cada rol en el menú.** Las «Notificaciones» siempre están arriba a la derecha.

| Rol | Menú |
|---|---|
| Administrador | Inicio · Empleados · Jugadores · Mesas · Turnos · Ganancias · Suspensiones |
| Crupier | Inicio · Mi mesa |
| Supervisor | Inicio · Mis conceptos · Ganancias |
| Jugador | Inicio · Mi cuenta · Mi historial |

Si un usuario escribe la dirección de una página que no es de su rol, la aplicación responde «403 No tienes permiso para ver esta página.».

---

## Persona 1 — Administración: preparar el casino

**Tu tema:** el administrador da de alta a las personas y las mesas, y organiza quién trabaja en qué mesa y cuándo.

### P1-1. Entrar como administrador
1. Abre `http://localhost:8010`, escribe el usuario `admin` y su contraseña, y pulsa **Entrar**.
2. **Debe verse:** «Sesión iniciada como admin (Administrador)» y el menú de 7 opciones.

### P1-2. Crear una mesa
1. Menú **Mesas** → **Registrar mesa**.
2. Llena: Código `R-02`, Juego **Ruleta**, Apuesta mínima `10`, Apuesta máxima `300`.
3. Pulsa **Guardar mesa**.
4. **Debe verse:** «Mesa registrada correctamente.» y `R-02 · Ruleta · 10.00 · 300.00` en la lista.

### P1-3. Contratar un crupier y una supervisora
1. Menú **Empleados** → **Registrar empleado**.
2. Llena: Documento `1030000001`, Nombre `Pedro Gómez`, Rol **Crupier**, Salario `900`, Competencias **Ruleta**, usuario `pedro`, y la contraseña dos veces.
3. Pulsa **Guardar empleado**.
4. Repite con: `1030000002`, `Sofía Herrera`, Rol **Supervisor**, Salario `400`, **Ruleta**, usuario `sofia`.
5. **Debe verse:** «Empleado registrado correctamente.» dos veces. Cada fila muestra el rol, el salario y las competencias.

### P1-4. Registrar jugadores
1. Menú **Jugadores** → **Registrar jugador**.
2. Llena: Documento `1040000001`, Nombre `Laura Martínez`, Código de cliente `CLI-100`, Edad `25`, Saldo inicial `1000`, usuario `laura` y la contraseña.
3. Pulsa **Guardar jugador**.
4. Repite con: `1040000002`, `Mateo Ruiz`, `CLI-101`, `40`, `2000`, usuario `mateo`.
5. **Debe verse:** «Jugador registrado correctamente.» y los dos jugadores con Estado **Activo**.

### P1-5. Asignar la mesa al crupier en el turno actual (lo más importante)
1. Menú **Turnos**. En la fila de hoy del turno en curso, pulsa **Ver asignaciones**: ya están R-01, B-01 y P-01, que vienen de la carga.
2. En **Añadir asignación** elige: Mesa `R-02`, Crupier `Pedro Gómez`, Supervisor `Sofía Herrera`.
3. Pulsa **Asignar**.
4. **Debe verse:** «Asignación registrada correctamente.» y la fila `R-02 · Pedro Gómez · Sofía Herrera`. Desde ahora Pedro puede atender R-02.

### P1-6. Crear el turno siguiente (rotación)
1. Menú **Turnos** → **Registrar turno**.
2. Llena: Fecha de hoy, Hora de inicio = la hora de fin del turno actual, Hora de fin = 2 horas después. Si pasa de medianoche, usa mañana de 08:00 a 10:00.
3. Pulsa **Guardar turno**.
4. En **Ver asignaciones** del turno nuevo, elige R-01, Pedro Gómez y Sofía Herrera, y pulsa **Asignar**.
5. **Debe verse:** «Turno registrado correctamente.» y «Asignación registrada correctamente.». Pedro cambia de mesa en el turno siguiente.

### P1-7. Registrar a un empleado como jugador
1. Menú **Empleados** → en la fila de Pedro, **Registrar como jugador**.
2. Llena: Código `EMP-CLI-100`, Edad `30`, Saldo `2000`, y pulsa **Guardar jugador**.
3. Repite con Sofía: `EMP-CLI-101`, `35`, `1500`.
4. **Debe verse:** en **Jugadores**, los dos con «Es empleado: Sí» y el enlace **Abonos**. La Persona 3 los usa después.

### P1-8. Editar y borrar
1. **Mesas** → **Editar** en R-02 → Apuesta máxima `500` → **Guardar mesa**. Debe verse «Mesa actualizada correctamente.».
2. **Empleados** → **Editar** en Pedro → marca también **Blackjack** y deja la contraseña vacía → **Guardar empleado**. Debe verse «Empleado actualizado correctamente.»; la contraseña de Pedro no cambia.
3. **Jugadores** → **Editar** en Mateo → Edad `41` → **Guardar jugador**. Debe verse «Jugador actualizado correctamente.»; el formulario no tiene saldo.
4. **Turnos** → **Registrar turno** para mañana, de 08:00 a 10:00. Luego **Editar** → fin `09:30` → **Guardar turno**. Luego **Borrar**. Deben verse los mensajes de registrado, actualizado y borrado.

### Preguntas que te pueden hacer

| Pregunta | Respuesta |
|---|---|
| ¿Quién crea los usuarios? | Solo el administrador. Cada empleado o jugador nace con su usuario y contraseña. |
| ¿Qué es una competencia? | Los juegos que sabe atender o supervisar un empleado. Sin competencia en el juego de la mesa, no se le puede asignar. |
| ¿Cuánto dura un turno? | Máximo 2 horas, dentro del mismo día y sin cruzarse con otro turno. |
| ¿Cómo se asigna una mesa? | En Turnos → Ver asignaciones: se eligen la mesa, el crupier y el supervisor de ese turno. |
| ¿Un crupier puede tener dos mesas? | No en el mismo turno. Tampoco puede repetir la misma mesa en el mismo día: debe rotar. |
| ¿Puede haber menores de edad? | No. La edad mínima es 18 y el código de cliente y el documento no se repiten. |
| ¿Cómo se agregan fichas a un jugador? | Solo con el saldo inicial al registrarlo. Después cambia únicamente por apuestas y premios; no hay consignaciones ni retiros. |
| ¿Por qué no deja borrar algo? | Para no perder historia. No se borra una mesa, turno o empleado con asignaciones, ni un jugador con apuestas. |
| ¿Un empleado puede jugar? | Sí: con **Registrar como jugador** tiene un perfil de cliente con sus propias reglas (Persona 3). |

---

## Persona 2 — Operación de la mesa: rondas y apuestas

**Tu tema:** el crupier abre partidas (rondas), registra las apuestas de los jugadores y, al terminar, registra el resultado; el sistema paga solo.

### P2-1. Entrar como crupier y ver la mesa
1. En la otra ventana, entra con `pedro` → menú **Mi mesa**.
2. **Debe verse:** «R-02 · Ruleta», con la apuesta mínima y la máxima, y el botón **Abrir ronda**.

### P2-2. Abrir una ronda
1. Pulsa **Abrir ronda**.
2. **Debe verse:** «Ronda abierta correctamente.» y «Ronda 1 · Abierta».

### P2-3. Registrar apuestas
1. En **Registrar apuesta**, llena Código de cliente, Tipo de apuesta, Monto y Factor de pago, y pulsa **Registrar apuesta**. Registra estas cinco, una por una:

   | Código | Tipo | Monto | Factor |
   |---|---|---|---|
   | `CLI-100` | `Rojo` | `100` | `2` |
   | `CLI-100` | `Impar` | `50` | `2` |
   | `CLI-101` | `Negro` | `200` | `2` |
   | `CLI-101` | `Pleno 17` | `20` | `36` |
   | `CLI-100` | `Pleno 5` | `10` | `36` (equivocada a propósito) |

2. **Debe verse:** «Apuesta registrada correctamente.» cada vez. Cada apuesta queda **Pendiente**, con los botones **Modificar** y **Anular**. El monto se descuenta del saldo del jugador en ese momento.

### P2-4. Corregir apuestas
1. **Anular** en `Pleno 5`. Debe verse «Apuesta anulada correctamente.»; se devuelven los 10.
2. **Modificar** en `CLI-101 · Negro` → Monto `150` → **Guardar**. Debe verse «Apuesta modificada correctamente.».

### P2-5. Cerrar la ronda con el resultado
1. En **Resultado** escribe `17 Negro`.
2. En **Tipos ganadores** marca `Negro`, `Impar` y `Pleno 17`. No marques `Rojo`.
3. Pulsa **Cerrar ronda**.
4. **Debe verse:** «Ronda cerrada correctamente.» y:
   - Rojo 100 → **Perdida**, premio 0.00;
   - Impar 50 × 2 → **Ganada**, 100.00;
   - Negro 150 × 2 → **Ganada**, 300.00;
   - Pleno 17 20 × 36 → **Ganada**, 720.00.

   El premio es monto × factor y se suma al saldo al instante: Laura queda con 950 y Mateo con 2850.

### P2-6. Rondas siguientes y jugador bloqueado
1. Pulsa **Abrir ronda**. Debe verse «Ronda 2 · Abierta»: el número sigue la secuencia.
2. Intenta registrar `CLI-002`, `Rojo`, `20`, `2`. CLI-002 viene de la carga y está en pausa.
3. **Debe verse:** «El jugador está suspendido: Pausa voluntaria hasta …» y no se registra nada.
4. Pulsa **Anular ronda**. Debe verse «Ronda anulada correctamente.»; solo se anula una ronda sin apuestas.

### P2-7. Póker: la apuesta se registra cuando se conoce el resultado
1. Cierra la sesión y entra con `crupier3` → **Mi mesa** (P-01 · Póker) → **Abrir ronda**.
2. **Debe verse:** la ronda abierta **sin** formulario de apuesta. En póker no se registra nada mientras se juega la mano.
3. En **Resultado** escribe `Escalera de color` y pulsa **Cerrar ronda**.
4. En **Registrar apuesta con resultado conocido** llena: `CLI-100`, `Mano ganadora`, `100`, `2`, Estado **Ganada**. Pulsa **Registrar apuesta**.
5. **Debe verse:** «Ganada · Premio 200.00». El saldo de Laura pasa a 1050.

### Preguntas que te pueden hacer

| Pregunta | Respuesta |
|---|---|
| ¿Quién registra las apuestas? | El crupier de la mesa. El jugador no apuesta desde su cuenta: la apuesta se hace en la mesa física. |
| ¿Qué crupier puede operar? | Solo el que tiene la mesa asignada en el turno en curso, y solo esa mesa. Fuera de su turno ve «No tienes una mesa asignada en el turno en curso.». |
| ¿Qué se revisa al registrar una apuesta? | Que el jugador exista y no esté suspendido, que tenga saldo suficiente y que el monto esté entre el mínimo y el máximo de la mesa. Si falla, aparece el motivo. |
| ¿Cuándo se puede modificar o anular? | Solo mientras la apuesta está pendiente y la ronda abierta. Anular devuelve el monto. |
| ¿Cómo se decide quién gana? | El crupier escribe el resultado y marca los tipos de apuesta ganadores. Las marcadas quedan Ganadas; el resto, Perdidas. |
| ¿Cuánto se paga? | Monto × factor de pago, acreditado al momento. Lo perdido queda a favor del casino. |
| ¿Se puede pagar dos veces? | No. Una ronda cerrada no se vuelve a procesar. |
| ¿Cuántas rondas abiertas puede haber? | Una por mesa. Los números son correlativos por mesa. |
| ¿Por qué el póker es distinto? | No se registra apuesta por apuesta durante la mano: se registra al final, con el resultado ya conocido. |
| ¿Y si el turno termina con una ronda abierta? | La cierra el crupier del turno siguiente en esa mesa. |

---

## Persona 3 — Control: jugadores, suspensiones y ganancias

**Tu tema:** lo que ven los jugadores, las reglas que suspenden automáticamente, cómo las atiende el administrador, los conceptos del supervisor y el informe de ganancias.

### P3-1. Lo que ve el jugador
1. En la otra ventana, entra con `laura`.
2. **Notificaciones:** debe verse un aviso por cada apuesta resuelta, por ejemplo «Tu apuesta de la ronda 1 fue resuelta: Ganada.». **Marcar todas como leídas** deja el contador en 0.
3. **Mi cuenta:** Saldo 1050.00, Estado **Activo**.
4. **Mi historial:** 3 apuestas resueltas, 250.00 apostado, 300.00 en premios, resultado neto 50.00 (1000 + 50 = 1050). La apuesta anulada no aparece.

### P3-2. Pausa voluntaria (el jugador se autoexcluye 24 horas)
1. Con `laura`: **Mi cuenta** → **Solicitar pausa de 24 horas** → **Aceptar**.
2. **Debe verse:** «Tu pausa de 24 horas comenzó.» y Estado **Suspendido**, «Pausa voluntaria hasta …». Ningún crupier puede registrarle apuestas, como mostró la Persona 2 en P2-6.

### P3-3. Suspensión por pérdida diaria
1. Entra con `crupier3` → **Mi mesa**. En la ronda de póker cerrada, registra `CLI-101`, `Mano perdida`, `1900`, `1`, **Perdida**.
2. Intenta otra apuesta de `CLI-101` por `50`, Ganada.
3. **Debe verse:** «El jugador está suspendido: Pérdida diaria hasta …» (24 horas). Hoy Mateo pierde 1050 netos, más que el umbral de 1000.
4. Entra con `mateo` → **Mi cuenta**: Estado Suspendido y saldo 950. En **Notificaciones**: «Tu cuenta fue suspendida por Pérdida diaria hasta …».

### P3-4. Empleados que juegan: deuda e investigación
1. Con `crupier3`, en la misma ronda, registra `EMP-CLI-101` (Sofía), `Mano perdida`, `900`, `1`, **Perdida**. Una segunda apuesta de Sofía da «El jugador está suspendido: Pérdida de empleado.». Sofía debe 900, más que dos salarios (800).
2. Registra `EMP-CLI-100` (Pedro), `Mano ganadora`, `500`, `3`, **Ganada**. Una segunda apuesta de Pedro da «El jugador está suspendido: Investigación.». Pedro ganó 1000 netos, más que un salario (900).

### P3-5. El administrador atiende las suspensiones
1. En la ventana de `admin`, **Notificaciones**: «La cuenta de Pedro Gómez fue suspendida por Investigación.», «La cuenta de Sofía Herrera fue suspendida por Pérdida de empleado.» y «La cuenta de Mateo Ruiz fue suspendida por Pérdida diaria hasta …».
2. **Suspensiones:** la lista de activas, con motivo, inicio y fin. Las de 24 horas tienen fecha de fin; la deuda y la investigación dicen «Indefinido».
3. **Abono:** **Jugadores** → **Abonos** en la fila de Sofía. Debe verse «Deuda actual: 900.00» y «Límite de dos salarios: 800.00». Escribe `200` y pulsa **Registrar abono**: «Abono registrado.», la deuda baja a 700 y Sofía se reactiva sola.
4. **Fin de investigación:** **Suspensiones** → **Finalizar investigación** en la fila de Pedro → **Aceptar**. Debe verse «Investigación finalizada.».
5. Con `sofia` y con `pedro`, **Notificaciones**: cada uno recibe su aviso de reactivación.

### P3-6. Historial de un jugador (administrador)
1. **Jugadores** → **Historial** en la fila de `CLI-100`.
2. **Debe verse:** sus datos, totales, todas sus apuestas (mesa, ronda, tipo, monto, factor, estado y premio) y su pausa voluntaria.

### P3-7. Ganancias
1. Con `admin`: **Ganancias** (por defecto, hoy) → **Filtrar**. Debe verse:

   | Mesa | Apuestas | Apostado | Premios | Consolidado | Neto |
   |---|---|---|---|---|---|
   | P-01 | 5 | 4500.00 | 1700.00 | 3900.00 | 2800.00 |
   | R-02 | 4 | 320.00 | 1120.00 | 100.00 | −800.00 |
   | Total | 9 | 4820.00 | 2820.00 | 4000.00 | 2000.00 |

   B-01 y R-01 aparecen en ceros.
2. Con `sofia` → **Ganancias**: solo ve las mesas que supervisa (R-02 con −800 y R-01 en ceros).

### P3-8. Concepto del supervisor
1. Entra con `supervisor1` → **Mis conceptos**. Los turnos en curso dicen «Pendiente de finalizar turno».
2. En el turno más antiguo de P-01, elige **Sospecha de fraude** → **Registrar concepto**. Debe verse «Concepto registrado correctamente.».
3. Con `admin`, en **Notificaciones**: «Se registró una sospecha de fraude en la mesa P-01.».

### Preguntas que te pueden hacer

| Pregunta | Respuesta |
|---|---|
| ¿Cuándo se suspende a un jugador? | En 4 casos: pérdida diaria mayor que el umbral (24 h), pausa voluntaria (24 h), empleado que debe más de dos salarios y empleado que gana más de un salario (investigación). |
| ¿Cuál es el umbral de pérdida diaria? | El enunciado dice «umbral configurado» sin dar un número; en la aplicación se configura y en la VM vale 1000. Se cuenta la pérdida neta del día. |
| ¿Quién suspende? | El sistema, automáticamente al resolver una apuesta. La pausa la pide el propio jugador. |
| ¿Cómo se levanta una suspensión? | Las de 24 horas terminan solas. La deuda de empleado, con abonos que la bajen al límite. La investigación, cuando el administrador la finaliza. |
| ¿Un abono cambia el saldo? | No. Solo reduce la deuda del empleado. |
| ¿Quién recibe notificaciones? | El jugador, por cada apuesta resuelta y por sus suspensiones o reactivaciones. El administrador, por cada suspensión automática y por cada sospecha de fraude. |
| ¿Qué es el concepto del supervisor? | Su opinión sobre el crupier al terminar el turno. Hay 4 opciones y es subjetivo: no se calcula con las ganancias. |
| ¿Qué muestra Ganancias? | Por mesa: lo apostado, los premios, lo consolidado (apuestas perdidas, a favor del casino) y el neto (apostado − premios). El supervisor solo ve sus mesas. |
| ¿El jugador ve a otros jugadores? | No. Solo ve su cuenta, su historial y sus avisos; el administrador ve el historial de todos. |

---

## Si les preguntan algo de otra parte

| Tema | Quién responde |
|---|---|
| Usuarios, roles, empleados, jugadores, mesas, turnos y asignaciones | Persona 1 |
| Rondas, apuestas, resultados, premios y póker | Persona 2 |
| Suspensiones, pausa, abonos, investigación, notificaciones, historial, conceptos y ganancias | Persona 3 |
