# CB-CMS-002 — Reporte de verificación

Fecha: 15 de octubre de 2026. Resultado: implementación editorial y aceptación local ejecutadas en Docker. Revisión del estudio pendiente; sin merge ni despliegue.

## Estado inicial y aislamiento

El checkout original estaba en `feature/CB-FOUNDATION-001`, commit `37c8851`, 26 commits detrás de su referencia remota `1135ea4`. Se conservaron sin mover ni sobrescribir `CB-CMS-002_Instrucciones_Codex.md` y `web/package-lock.json`, ambos sin seguimiento. No había instrucciones AGENTS.md en el árbol examinado. La consulta de PR existentes no devolvió resultados.

Se creó `feature/CB-CMS-002` desde `1135ea4` en `.worktrees/CB-CMS-002`. El PR debe tener como base `feature/CB-FOUNDATION-001`: este bloque depende del anterior, todavía separado de main. No se hizo reset, limpieza forzada ni modificación de volúmenes ajenos.

Docker Desktop estaba encendido con motor Linux. La base aportaba autenticación, configuración pública directa y Compose, pero no los módulos editoriales. Se reprodujo un fallo real: el volumen de almacenamiento no podía montarse debajo del bind mount API de solo lectura. Se trasladó a `/storage`, fuera del document root, con usuario no privilegiado. También se corrigieron el manejo de solicitudes por encima de post_max_size, el rechazo de objetos JSON vacíos y la resolución del host interno Vite para pruebas de navegador.

## Implementación

- API REST PHP con migraciones adicionales 003 y 004, consultas parametrizadas, paginación, búsqueda, estados, conflictos de versión y auditoría con entidad e instantánea editorial.
- Especialidades, equipo, actualidad, multimedia y casos persistentes; guardar borrador, publicar, retirar, listados y detalles públicos. Sin registros profesionales inventados ni seeds en la base local real.
- Biblioteca JPEG/PNG/WebP/PDF, metadatos y texto alternativo, selección de portada, miniatura y galería, relaciones entre contenido y bloqueo de eliminación de archivos utilizados.
- Configuración con imágenes y redes HTTPS de dominios admitidos, publicación directa y control de versión.
- Casos con revisión/aprobación administrativa, fundamento, anonimización, evidencia privada opcional u obligatoria según el caso y revocación de aprobación al editar. La política elegida retira inmediatamente la versión pública al modificar un caso aprobado/publicado.
- Cuentas, roles, activación/desactivación, enlaces de contraseña de un solo uso por 30 minutos, revocación de sesiones y protección del último administrador activo.
- Sitio alimentado por contenido publicado; texto sin ejecución HTML, videos YouTube/Vimeo bajo demanda, estados vacíos, errores y vista previa autenticada.
- Script de respaldos, guía única de operación, script de aceptación y workflow de CI sin secretos versionados.

## Evidencia ejecutada

Aceptación final: `tests/run.ps1 -Port 5190`, proyecto `cb-cms002-test-20261015172954`, salida 0. El proyecto se detuvo al finalizar; sus volúmenes se conservaron. Los experimentos iniciales usaron `cb-cms002-test` y `cb-cms002-acceptance`, nunca la base local real.

```text
PASS 87 API assertions: auth, CSRF, CRUD, drafts, conflicts, cases, evidence,
uploads, roles, account revocation, reset, rate limit.
PASS supplemental: session expiration, reset expiration, invalid image,
traversal containment, account creation/role/conflict, media deletion,
content withdrawal.
PASS browser: login, draft, publication, detail, safe text, unsaved warning,
mobile overflow, keyboard focus, 404, no JavaScript errors.
PASS persisted settings, editorial records and media after down/up.
PASS acceptance: cb-cms002-test-20261015172954.
PASS Apache storage denial probe.php 403
PASS Apache storage denial probe.txt 403
```

| Criterio | Resultado y alcance real |
| --- | --- |
| Compose/build/salud | Configuración válida; db, api y web healthy; migrate Exited (0). PHP y React se construyeron dentro de Docker. |
| PHP/MariaDB | PHP 8.2.34, pdo_mysql, GD y fileinfo; MariaDB 10.11.19. No runtimes globales instalados. |
| Proxy/aislamiento | API usa host `db`; `/api/public/settings` responde a través de Vite. Solo 127.0.0.1:5180 publicado en el entorno principal; MariaDB sin puerto de host. |
| Migraciones | 001/002 conservadas; 003/004 nuevas; repetición sin pérdida de datos. |
| Persistencia | `down` y nuevo `up` sin borrar volúmenes; se comprobaron texto de configuración, al menos cinco contenidos y cuatro medios. El navegador publicó otro artículo en el mismo entorno sintético. |
| Recarga Windows | Logs Vite registraron recarga de cms.jsx, editorial.jsx y main.jsx, actualización HMR de CSS y reinicio por vite.config.js desde el bind mount Windows. |
| Autenticación | Acceso correcto/incorrecto, HttpOnly/SameSite, CSRF ausente, logout, expiración de sesión, limitación de intentos y revocación de cuenta. Secure queda configurado para producción HTTPS; no se atribuye a HTTP local. |
| Permisos | Editor no lista usuarios, no aprueba casos ni consulta evidencia; cambios de contraseña/estado invalidan sesiones; último admin protegido. |
| CRUD | Crear, modificar, filtrar, publicar/retirar y conflictos en los módulos. Retirada lógica: no borra físicamente historial. Usuarios: creación, rol, desactivación y reset probado. |
| Publicación | Borradores devuelven 404 a visitantes; publicación visible; versión obsoleta devuelve 409; vistas previas requieren sesión. |
| Casos | Publicación sin aprobación rechazada; aprobación exige fundamento/anonimización; consentimiento requerido sin evidencia rechazado; modificación revoca aprobación y oculta el caso. |
| Medios | JPEG/PNG/WebP/PDF válidos, archivos corruptos, extensión engañosa, script, exceso de tamaño, referencia inválida y eliminación con/sin referencias. PHP normaliza nombres con traversal; almacenamiento usa un nombre aleatorio y no una ruta del cliente. |
| Consentimiento | ID privado no accesible en endpoint público; editor recibe 403; datos de aprobación/evidencia omitidos de respuestas públicas. |
| Contenido malicioso | javascript: en videos/redes rechazado; script en artículo se muestra como texto sin ejecutar en Chromium. |
| Navegador | Chromium en contenedor, 1440×1000 y 390×844; flujo completo borrador→publicación→detalle, aviso de edición, foco por Tab, sin overflow móvil ni errores JavaScript capturados en el panel. No equivale a una auditoría WCAG completa ni prueba en todos los navegadores. |
| Apache | Contenedor httpd separado con las reglas storage.htaccess como .htaccess: probe.php y probe.txt devuelven 403. No es una validación del futuro hosting ni de su instalación PHP. |
| Respaldos | Dump MariaDB y copia del almacenamiento ejecutados antes de migración adicional; guardados en `.local/backups`, fuera de Git. |
| CI | Workflow `.github/workflows/editorial.yml` preparado; la ejecución remota y su estado se informan en el PR y cierre. No se confunde la aceptación local con CI remota. |

Versiones adicionales: Docker Engine 29.7.2, Docker Desktop 4.87.0, Node 22.23.3, Vite 6.4.4, Playwright 1.56.1. El build final del ensayo terminó en 4.56 s. El log bruto local está en `.local/acceptance.log`; no se incluyen contraseñas, tokens ni documentos privados en este reporte.

## Estado de entrega y límites

Sitio local: http://localhost:5180. Panel: http://localhost:5180/admin. Se creó el administrador solicitado por el usuario mediante invitación local; la definición de su contraseña corresponde al titular. El archivo de activación está ignorado y no forma parte del PR.

El contenido profesional real y la aprobación de publicaciones corresponden al estudio. La base principal no contiene los casos, artículos ni personas sintéticas de las pruebas. Las pruebas acreditan persistencia funcional, no revisión de contenido jurídico.

El hosting futuro debe confirmar PHP 8.2, GD, qpdf y ejecución de su validador, rutas privadas, HTTPS y reglas Apache. PDF se descarga como adjunto; no hay antivirus ni OCR. La copia conjunta base/archivos requiere suspender escrituras para un punto de recuperación coherente en operación multiusuario. No se ha realizado restauración sobre la base principal.

La SPA entrega HTML con título institucional fijo y noindex; no implementa prerender ni SEO por artículo. Las rutas API inexistentes responden 404; la vista de error SPA no cambia el HTTP 200 del shell Vite. La aprobación de casos conserva auditoría, pero no permite restaurar versiones desde la interfaz.

Continúan deshabilitados o expresamente pendientes: agenda, envío de consultas, SMTP, recordatorios, pagos, portal de clientes, IA, bandeja omnicanal, sincronización de redes, dominio y despliegue. No hubo merge ni publicación al hosting.

Operación reproducible: [CB-CMS-002-RUNBOOK.md](CB-CMS-002-RUNBOOK.md).
