# GSD Debug Knowledge Base

Resolved debug sessions. Used by `gsd-debugger` to surface known-pattern hypotheses at the start of new investigations.

---

## pdf-nota-tecnica-atinencia — PHP local server discarded writable multipart temp variables
- **Date:** 2026-08-21
- **Error patterns:** PDF, noteForm.document, Error al subir el archivo, unable to create a temporary file, Livewire upload
- **Root cause:** Laravel ServeCommand filtered TEMP/TMP out of the environment used to start the child php -S process. On Windows the child fell back to C:/WINDOWS, where it could not create multipart temporary files, so Livewire surfaced validation.uploaded.
- **Fix:** AppServiceProvider adds TEMP and TMP idempotently to ServeCommand::$passthroughVariables while running in console; a regression test protects the configuration.
- **Files changed:** app/Providers/AppServiceProvider.php, tests/Feature/ServeCommandEnvironmentTest.php
---
