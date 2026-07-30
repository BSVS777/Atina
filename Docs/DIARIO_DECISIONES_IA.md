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
