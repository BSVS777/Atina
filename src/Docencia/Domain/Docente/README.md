# Domain / Docente — DO-01

Aggregate `Docente`, entity `AtestadoAcademico`, Value Objects `GradoAcademico`,
`Especialidad`, `AnioObtencion`.

`GradoAcademico` debe reflejar el ENUM real de la tabla `atestados.grado`:
`Diplomado, Bachillerato, Licenciatura, Maestría, Doctorado` (5 valores — el
enunciado original y `MATRIZ_REQUISITOS.md` RN-01 solo mencionaban 4; el
schema del profesor agrega `Diplomado`, es la fuente autoritativa).

`Especialidad` es el mismo vocabulario controlado que usa `catalogo_atinencia_especialidad`
(tabla `especialidades`) — confirma la decisión de diseño de §6 de la matriz.

Estado: no iniciado.
