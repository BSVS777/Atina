<!-- AI-HARNESS:GENERATED -->

---
ai_harness_profile: professional
ai_harness_version: 3.0.0
source: AI Dev Harness BSVS
---

# Active AI Harness Profile: PROFESSIONAL

# AI Harness Profile: PROFESSIONAL

## Objetivo
Construir software mantenible y entregable con arquitectura proporcional, slices verticales y verificación independiente.

## Metodología
Spec-Driven Vertical Delivery:

```text
Discovery → spec → decisiones → slices → implementación → revisión → entrega
```

## Principios
- Alta cohesión y bajo acoplamiento.
- Dependencias hacia el dominio cuando aplica.
- Arquitectura proporcional, no dogmática.
- Contratos claros y manejo explícito de errores.
- Testing basado en riesgo.
- Seguridad, accesibilidad y observabilidad desde el diseño.
- Diff pequeño y revisable.
- Un solo orquestador metodológico por sesión.

## Flujo
1. Comprende dominio, usuarios, restricciones y estado actual.
2. Define alcance, no-alcance y criterios verificables.
3. Registra decisiones arquitectónicas significativas.
4. Divide en slices que produzcan valor comprobable.
5. Implementa con pruebas relevantes.
6. Revisa seguridad, UI, accesibilidad, rendimiento y regresiones según aplique.
7. Ejecuta validación integral.
8. Documenta operación, riesgos y siguientes pasos.
9. Cierra con diff, comandos y evidencia.

## Reglas
- No cambiar arquitectura sin explicar el problema que resuelve.
- No introducir una dependencia sin evaluar mantenimiento y alternativa nativa.
- No confiar únicamente en tests generados por el mismo agente.
- Usar navegador real para flujos web importantes.
- No exponer secretos ni conectar producción con permisos amplios.
- Para trabajo paralelo, usar worktrees separados.
- Detener y reportar si una migración o acción destructiva requiere decisión humana.

## Definition of Done
- Criterios satisfechos.
- Tests/build/lint ejecutados.
- Flujos críticos verificados.
- Riesgos y deuda visibles.
- Documentación actualizada.
- Diff revisado.
- Rollback o recuperación contemplados cuando aplica.

## Compatibilidad con Gentle AI global
- Este perfil controla el flujo de producto y arquitectura proporcional.
- Gentle AI puede aportar Engram, skills, Context7, subagentes y revisión.
- Usar SDD completo solo después de una propuesta aceptada o una instrucción explícita.
- Para trabajo acotado, preferir implementación directa o delegación limitada.
