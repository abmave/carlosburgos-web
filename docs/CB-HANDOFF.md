# CB-HANDOFF — Continuidad verificada

Fecha: 19 de octubre de 2026. Repositorio: https://github.com/abmave/carlosburgos-web.git.
PR abierto: https://github.com/abmave/carlosburgos-web/pull/1. Sin merge ni despliegue.

## Ramas y evidencia de sincronización

| Referencia | SHA verificado antes de este documento | Estado |
| --- | --- | --- |
| feature/CB-CMS-002 | 0d9e2bf74f8086003729470068d48d5ca9d491ce | Worktree .worktrees/CB-CMS-002 limpio e idéntico a origin tras fetch. |
| origin/feature/CB-FOUNDATION-001 | 1135ea4c3ff3507e2fc9dfaa48b3c4612c23acae | Base del PR. |
| Checkout original FOUNDATION | 37c8851508c5bfe52a5a603131160fcc0fc26834 | Ancestro de origin, 26 commits detrás; preservado sin actualizar ni sobrescribir. |
| Corrección P2 | bdb509dbbf33e914f1cc71d541aba617f92df754 | Incluida en CMS; regresión de contraseña terminada. |

La CI del SHA 0d9e2bf está aprobada: [CMS editorial, ejecución 38970072313](https://github.com/abmave/carlosburgos-web/actions/runs/38970072313). Este documento añade un commit documental; su SHA y CI final se registran en el PR y en el cierre. Consultar la ejecución correspondiente al HEAD, sin atribuirle el resultado de un SHA anterior.

Los archivos sin seguimiento del checkout original — CB-CMS-002_Instrucciones_Codex.md, CB-CMS-002_Revision_PR1.md y web/package-lock.json — tienen el mismo blob Git normalizado que origin/feature/CB-CMS-002. Ya están publicados; no deben volver a añadirse a una rama divergente. El directorio .worktrees es el checkout anidado, no un archivo pendiente.

No se detectó código, migración, configuración reproducible, prueba ni documentación necesaria pendiente de publicación. El único agregado de esta revisión es este documento solicitado. Los artefactos de diagnóstico en .local no son requisitos para recuperar el proyecto.

## Verificación local del 19 de octubre

Se ejecutó docker/start-local.ps1 en el worktree CMS, conservando .env y los volúmenes existentes, sin semillas ni cambios de cuentas.

- Docker Compose: db, api y web healthy; migrate terminó con código 0.
- HTTP 200: /, /admin, /api/health, /api/public/settings y /api/admin/session. La sesión anónima devuelve user=null; no se publicó su token CSRF.
- Las listas públicas specialties, team, articles, multimedia y cases responden HTTP 200 con total=0.
- Lectura agregada de la base: 1 cuenta, 0 registros editoriales, 0 medios y 4 migraciones. No se extrajeron datos personales ni hashes.
- Chromium ejecutado en Docker: formulario del panel visible (correo, contraseña y botón de ingreso), portada renderizada y sin errores JavaScript.
- No se probó el login de la cuenta real: requiere la contraseña del titular. No se reemplazó la cuenta ni se regeneró una activación.
- El contenido de configuración pública se lee correctamente. No existen artículos/casos/medios locales para acreditar lectura de un detalle real; las listas vacías no se sustituyeron por contenido ficticio.

El entorno queda disponible en http://localhost:5180 y http://localhost:5180/admin.
La suite completa y la regresión de 137 comprobaciones están documentadas en [verificación del CMS](CB-CMS-002-VERIFICATION.md). Las verificaciones de esta revisión son de lectura; no ejecutan la suite que crea fixtures sobre la base real.

## Recuperación en otro equipo

Requiere Git, PowerShell y Docker Desktop con motor Linux/Compose v2. Usar la rama feature/CB-CMS-002; main todavía no contiene el PR. En PowerShell, el siguiente bloque clona o actualiza exclusivamente un checkout limpio de esa rama; se detiene ante cambios o divergencias:

~~~powershell
$ErrorActionPreference = 'Stop'
if (!(Test-Path -LiteralPath './carlosburgos')) {
    git clone --branch feature/CB-CMS-002 https://github.com/abmave/carlosburgos-web.git carlosburgos
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo clonar.' }
}
Set-Location ./carlosburgos
$branch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or $branch -ne 'feature/CB-CMS-002') { throw 'Use el checkout de feature/CB-CMS-002; conserve el árbol actual.' }
$pending = git status --porcelain
if ($LASTEXITCODE -ne 0 -or $pending) { throw 'Conserve y revise los cambios locales antes de actualizar.' }
git pull --ff-only origin feature/CB-CMS-002
if ($LASTEXITCODE -ne 0) { throw 'Actualización detenida; no forzar ni resetear.' }
./docker/start-local.ps1
if ($LASTEXITCODE -ne 0) { throw 'Revise el estado de Docker.' }
docker compose ps -a
git rev-parse HEAD
~~~

El arranque no crea cuentas predeterminadas. Una instalación nueva carece de cuentas y datos privados; para continuar con los datos originales, coordinar su restauración privada antes de usarla como instalación de trabajo. Nunca usar down -v para retomar. Guía ampliada: [CONTINUIDAD-CASA.md](CONTINUIDAD-CASA.md), [runbook](CB-CMS-002-RUNBOOK.md).

## Qué requiere traslado privado

Git contiene código, migraciones, lockfile, configuración de ejemplo, recursos del repositorio, pruebas e instrucciones. No contiene el estado de los volúmenes Docker.

Para recuperar exactamente los datos reales, trasladar por canal seguro y cifrado:

1. Respaldo coherente de MariaDB (cuentas y hashes, configuración editorial, publicaciones, auditoría y metadatos) junto con los archivos correspondientes de api_storage, especialmente medios y evidencia privada. Suspender escrituras durante la copia y comprobar una restauración aislada antes de reemplazar cualquier instalación.
2. La configuración privada .env asociada a volúmenes trasladados, y cualquier configuración local realmente utilizada. Para una instalación vacía el script genera credenciales nuevas; no sirven automáticamente para volúmenes existentes. Si hay volúmenes y falta .env, el script se detiene.
3. Documentos originales, consentimientos o contenido profesional que solo estén fuera del repositorio/almacenamiento: el titular debe inventariarlos y transferirlos privadamente si son necesarios. Esta revisión no presupone que existan ni que los respaldos antiguos sean suficientes.

docker/backup.ps1 es la herramienta existente de respaldo; sus salidas en .local/backups contienen datos privados y nunca se suben a Git. Esta revisión no creó ni trasladó nuevos respaldos y no acredita una restauración del estado privado actual.

No publicar ni reutilizar automáticamente activaciones, enlaces de reset o sesiones. Las invitaciones caducan; si el titular necesita recuperar acceso, tramitarlo expresamente sin sobrescribir cuentas existentes. No trasladar dependencias, builds ni temporales: se reconstruyen en Docker.

## Pendientes reales

- Titular: verificar login con su contraseña y revisar el PR #1.
- Para otra PC: transferir/restaurar de forma privada los datos necesarios y verificar cuenta, configuración y archivos; Git por sí solo no reproduce esos datos.
- Confirmar con el titular si esperaba contenido editorial: esta base tiene cero registros. Recuperarlo de una fuente autorizada si corresponde, sin inventarlo.
- No hay una corrección de código conocida incompleta del P2. No se inició otro módulo. Hosting y demás módulos permanecen fuera de esta entrega.

## Preparación de despliegue — 19 de octubre de 2026

La rama feature/CB-DEPLOY-001 parte de 5e9d4b6 y depende del CMS, sin merge. Dominio autorizado: carlosburgosh.com. Estado: paquete y ensayos de despliegue; publicación Yachay pendiente de acceso efectivo al navegador/panel. No confundir el entorno Docker con producción.

Construir desde un checkout limpio con ./deploy/build.ps1; VERSION y SHA256SUMS identifican el release. Ensayar la carpeta generada con ./deploy/test.ps1 -PackagePath RUTA_DEL_PAQUETE. La CI también adjunta el ZIP sin configuración privada. No usar paquetes dirty en producción. Detalle de estado, DNS, aislamiento, pruebas y pasos pendientes: [CB-DEPLOY-001-YACHAY.md](CB-DEPLOY-001-YACHAY.md).
