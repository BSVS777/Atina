# Diario de Decisiones IA

Registro de interacciones relevantes con IA durante el desarrollo: qué se consultó, qué se aceptó, qué se rechazó (y por qué), qué se tuvo que corregir, y qué se aprendió.

---

## 2026-07-30 — Setup inicial del repositorio

**Consulta:** generar `.gitignore` con todo lo que no es necesario versionar en GitHub.

**Aceptado:** ignorar directorios de tooling/config local de IA (`.claude/`, `.agents/`, `.atl/`, `.ai-harness/`) por no ser entregables del proyecto, más ruido estándar de OS/editor (`.DS_Store`, `Thumbs.db`, `*.swp`).

**Rechazado:** no se agregaron ignores específicos de stack (`node_modules/`, `target/`, `venv/`, etc.) porque todavía no hay código ni stack definido — se agregarán cuando se elija.

**Corregido:** nada, no hubo errores en esta iteración.

**Aprendido:** conviene mantener el `.gitignore` mínimo y ampliarlo recién cuando se sepa el stack, en vez de anticipar reglas especulativas.

---

## 2026-08-03 — Reglas permanentes de commits locales y diario de decisiones

**Consulta:** incorporar como regla permanente del harness que (1) cada implementación nueva relevante, aunque sea mínima, genere un commit local en inglés con formato Conventional Commits, sin push al remoto (el push lo controla el usuario), y (2) cada decisión de desarrollo con IA se documente en este diario con: qué se consultó, qué se aceptó, qué se rechazó y por qué, qué se corrigió por incorrecto/incompleto, y qué se aprendió.

**Aceptado:** ambas reglas se agregan a `AI_HARNESS.md` (fuente única, sección nueva "Commits y diario de decisiones" + refuerzo en Definition of Done).

**Rechazado:** no se automatiza con git hooks (pre-commit/post-commit) todavía, porque no hay stack ni gestor de paquetes definido en el repo — queda como disciplina del agente hasta que exista tooling que lo justifique.

**Corregido:** nada, no hubo errores en esta iteración.

**Aprendido:** `CLAUDE.md` y `GEMINI.md` usan `@AI_HARNESS.md` como referencia (un solo lugar para editar), pero `AGENTS.md` y `.github/copilot-instructions.md` son copias estáticas del mismo contenido sin mecanismo de sync en este repo — cualquier cambio a las reglas del harness debe replicarse manualmente en los tres archivos hasta que exista una herramienta de sincronización.

---
