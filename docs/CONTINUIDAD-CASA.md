# Continuidad del proyecto en casa

Actualizado el 16 de octubre de 2026. Repositorio: https://github.com/abmave/carlosburgos-web.git. PR abierto: https://github.com/abmave/carlosburgos-web/pull/1. No se ha fusionado ni desplegado.

## Rama que debe usarse

Trabajar en `feature/CB-CMS-002`. Contiene el bloque FOUNDATION completo, el CMS editorial y la corrección de contraseña. Su base de revisión sigue siendo `feature/CB-FOUNDATION-001`, publicada en `1135ea4c3ff3507e2fc9dfaa48b3c4612c23acae`. No hace falta fusionar ramas para continuar.

La corrección P2 está terminada en `bdb509dbbf33e914f1cc71d541aba617f92df754`; CI de esa revisión: [success, ejecución 38968996943](https://github.com/abmave/carlosburgos-web/actions/runs/38968996943). La entrega de continuidad agrega instrucciones/revisión, el lockfile local con metadatos libc y un arranque reproducible. Su SHA y CI más recientes se consultan en el PR; no confundirlos con los de la corrección.

## Inventario de ambos árboles de origen

- `C:\DEV\carlosburgos`: checkout FOUNDATION antiguo en `37c8851`, 26 commits detrás de su rama remota. No había modificaciones de archivos rastreados. Se conserva sin reset, merge ni sobrescritura.
- Los dos Markdown sin seguimiento de ese checkout se copian íntegros a la raíz de la rama CMS: `CB-CMS-002_Instrucciones_Codex.md` y `CB-CMS-002_Revision_PR1.md`.
- Su `web/package-lock.json` añade únicamente selectores glibc/musl respecto al lockfile anterior del CMS; se conserva en la rama CMS sin cambiar versiones ni integridades.
- `C:\DEV\carlosburgos\.worktrees\CB-CMS-002`: todo el código, migraciones, configuración reproducible, recursos, pruebas y reportes ya estaban publicados; los nuevos archivos de continuidad se agregan a la misma rama.
- Las copias originales de los Markdown y del lockfile permanecen en la PC original. Que sigan apareciendo como no rastreadas en su checkout antiguo no significa que falte publicarlas: la rama CMS contiene esas mismas copias.
- No se versiona el directorio de worktrees dentro del repositorio.

## Estado funcional y pendientes

Funcionan configuración pública, biblioteca, especialidades, equipo, artículos, multimedia/galerías, casos con aprobación administrativa y cuentas/permisos. El restablecimiento preserva los bytes exactos de contraseñas, incluidos espacios, y rechaza entradas inválidas sin consumir tokens. No queda una corrección de código conocida incompleta de la revisión PR1.

Pruebas existentes: build React, PHP 8.2, MariaDB, API editorial, 137 comprobaciones de regresión de contraseña, Chromium y persistencia tras down/up. La aceptación y sus límites están en [CB-CMS-002-VERIFICATION.md](CB-CMS-002-VERIFICATION.md).

Pendientes reales: revisión del PR por el titular, validación del contenido profesional por el estudio y configuración privada de cada instalación nueva. Agenda, envío de consultas, SMTP, recordatorios, pagos, portal de clientes, IA, sincronización social, dominio, SEO/prerender y despliegue siguen fuera del bloque entregado. El hosting futuro debe confirmar qpdf, GD, PHP 8.2, HTTPS y reglas Apache.

## Arranque en otra PC

Requisitos: Git, PowerShell y Docker Desktop encendido con motor Linux/Compose v2. No instalar Node, PHP ni MariaDB globalmente. Clonar directamente la rama CMS; main no contiene todavía el trabajo del PR.

Desde un checkout de `feature/CB-CMS-002`, ejecutar `./docker/start-local.ps1`. Crea un `.env` privado con valores aleatorios únicamente si no existe y no hay volúmenes previos del proyecto. Si ya existe, lo conserva. Si hay volúmenes pero falta la configuración, se detiene para evitar credenciales incompatibles. El script no imprime secretos ni crea cuentas predeterminadas.

Web: http://localhost:5180. Panel: http://localhost:5180/admin. Para otro puerto/proyecto: `./docker/start-local.ps1 -ProjectName carlosburgos-casa -Port 5183`; en los comandos posteriores debe utilizarse el mismo `docker compose -p carlosburgos-casa`. La invitación existente genera un enlace para el puerto 5180: si se usa otro puerto, ajustar únicamente el puerto en el archivo privado local antes de abrirlo.

Una instalación nueva NO contiene la cuenta, contraseñas, medios subidos ni datos privados de la PC de origen. Es deliberado: esos datos no se publican en GitHub. El código, recursos suministrados y migraciones sí están versionados y bastan para iniciar una base nueva. Para trasladar contenido privado real se requiere una transferencia separada y segura de respaldo y configuración; no usar Git para ello.

Para una base nueva, crear el primer administrador con `docker compose exec -T api php api/bin/invite-admin.php CORREO NOMBRE`. Sustituir los argumentos por los del titular. Crear `.local` con `New-Item -ItemType Directory -Force .local` y copiar la invitación con `docker compose cp api:/storage/admin-activation.html .local/activar-administrador.html`. Abrir el HTML local en el navegador y definir la contraseña. El enlace dura 30 minutos, es de un uso y no se debe compartir ni subir. No ejecutar esta creación para reemplazar cuentas existentes.

## Retomar trabajo y pruebas

Antes de actualizar, revisar `git status --short`; conservar/confirmar cambios propios. En la rama CMS, `git pull --ff-only origin feature/CB-CMS-002` permite solo avance directo, sin crear un merge. Si la rama diverge o hay cambios que interfieran, detenerse y revisarlos; no usar reset forzado.

Estado de servicios: `docker compose ps -a`. Logs acotados: `docker compose logs --tail 80 db migrate api web`. Migraciones: `docker compose exec -T api php api/bin/console.php migrate`. Build: `docker compose exec -T web npm run build`. Suite desechable completa: `./tests/run.ps1 -Port 5190`. Esa suite conserva sus volúmenes y no modifica la base real.

Respaldar antes de cambiar datos reales mediante `./docker/backup.ps1`. Para detener: `docker compose down`; para retomar: `./docker/start-local.ps1`. Nunca ejecutar `down -v` para continuar este proyecto.

## Archivos deliberadamente excluidos

`.env`, configuración local de PHP, claves, tokens, invitaciones/activaciones, respaldos, sesiones, consentimientos y otros datos privados, volúmenes, `.local`, dependencias, builds, logs y temporales. Los valores vacíos de `.env.example` y los scripts reproducibles sustituyen la necesidad de copiar secretos para una instalación nueva. Los ejemplos sintéticos del documento de revisión no son credenciales reales ni seeds.

Guía de operación ampliada: [CB-CMS-002-RUNBOOK.md](CB-CMS-002-RUNBOOK.md). Los documentos de instrucciones y revisión son evidencia histórica del encargo, no una orden de volver a ejecutar todo el bloque.
