# CB-HANDOFF — Estado y recuperación del proyecto

Actualizado el 19 de octubre de 2026. Repositorio: https://github.com/abmave/carlosburgos-web.git.

## GitHub y Yachay son estados distintos

**Destino autorizado registrado: carlosburgosh.com, hosting Yachay.** El dominio principal debe usar HTTPS y www.carlosburgosh.com debe redirigir al principal. Esto registra el destino del proyecto; no afirma compra, alta en cPanel, configuración DNS, certificado ni publicación.

**GitHub:** CMS, corrección P2, migraciones, pruebas, configuración reproducible, empaquetado Apache e instrucciones publicados. **Yachay:** no desplegado, sin SHA de producción acreditado. No hubo escrituras remotas en DNS, dominio, base ni archivos. En esta revisión solo se publica continuidad; no hay force push, merge ni despliegue.

## Ramas, PR y trazabilidad

| Rama | SHA de implementación verificado | Estado |
| --- | --- | --- |
| feature/CB-FOUNDATION-001 | 1135ea4c3ff3507e2fc9dfaa48b3c4612c23acae en origin | Publicada. Checkout original conserva 37c8851508c5bfe52a5a603131160fcc0fc26834, ancestro 26 commits detrás. |
| feature/CB-CMS-002 | 5e9d4b63dfeb9bafa81d9fea5474642a7a95e5cc | [PR #1](https://github.com/abmave/carlosburgos-web/pull/1) abierto contra FOUNDATION; worktree limpio. |
| feature/CB-DEPLOY-001 | f2350adee5d6c37d61d1504c91ded80191986349 | [PR #2](https://github.com/abmave/carlosburgos-web/pull/2) abierto contra CMS; contiene todo lo necesario para continuar. |

Esta actualización documental agrega un commit a DEPLOY; su SHA exacto y CI se registran en el PR #2 y cierre. Los SHA de la tabla identifican implementaciones, no sustituyen HEAD.

CI de CMS 5e9d4b6: [aprobada](https://github.com/abmave/carlosburgos-web/actions/runs/39291481770).
CI de implementación DEPLOY f2350ad: [CMS aprobado](https://github.com/abmave/carlosburgos-web/actions/runs/39322476724) y [paquete Apache aprobado y ZIP](https://github.com/abmave/carlosburgos-web/actions/runs/39322476441).
Para un commit nuevo, comprobar sus propias ejecuciones; no atribuirle la CI de otro SHA.

## Auditoría de archivos locales

Tras fetch, ambos worktrees estaban limpios e idénticos a sus ramas remotas. Los originales sin seguimiento del checkout principal se compararon mediante git hash-object normalizado con los blobs de origin/feature/CB-DEPLOY-001: los cuatro son idénticos.

- CB-CMS-002_Instrucciones_Codex.md
- CB-CMS-002_Revision_PR1.md
- CB-DEPLOY-001_YACHAY_CODEX.md
- web/package-lock.json

Esos archivos ya están publicados. Se mantienen las copias originales y los worktrees sin mover ni sobrescribir. No se introduce una copia del worktree dentro de Git ni un commit divergente sobre FOUNDATION antiguo. No se detectó código, migración, prueba, configuración reproducible ni instrucción necesaria que siga solo en esta PC.

## Estado funcional verificado y límites

La suite del CMS aprobó build, sintaxis PHP, 87 comprobaciones API, suplementarias, 137 regresiones de contraseña, Chromium y persistencia. El ensayo del paquete Apache aprobó HTTPS de laboratorio, redirecciones, rutas, protección de archivos, cookies, login, CSRF, logout, imágenes y ambos modos PDF, escritorio y móvil. Esas pruebas usan datos desechables y no acreditan el servidor Yachay.

Último inventario real verificado: una cuenta, cero registros editoriales y cero medios. No se inventó contenido. Posteriormente, a petición del titular, se renovó la invitación local de la cuenta existente; el titular informó haber completado el paso de contraseña. No se conoce ni publica su contraseña y no se ha comprobado un login real posterior. La activación es privada y no pertenece al paquete.

Se mantiene noindex; los datos profesionales y la salida pública final siguen pendientes de validación. PDF puede quedar explícitamente deshabilitado si el hosting no permite qpdf/exec; imágenes requieren GD. No hay agenda, SMTP, pagos ni otros módulos nuevos.

## Retomar en otra PC con Docker

Requisitos: Git, PowerShell 7 y Docker Desktop con motor Linux/Compose v2. No instalar PHP, Node ni MariaDB globalmente. Para retomar todo el trabajo, usar **feature/CB-DEPLOY-001**; main no contiene los PR.

Desde la carpeta donde se desea guardar el proyecto, este bloque clona o actualiza solo un checkout limpio de esa rama. Se detiene ante cambios o divergencias:

~~~powershell
$ErrorActionPreference = 'Stop'
if (!(Test-Path -LiteralPath './carlosburgos')) {
    git clone --branch feature/CB-DEPLOY-001 https://github.com/abmave/carlosburgos-web.git carlosburgos
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo clonar.' }
}
Set-Location ./carlosburgos
$branch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or $branch -ne 'feature/CB-DEPLOY-001') { throw 'Use el checkout DEPLOY; no sobrescriba otro árbol.' }
$pending = git status --porcelain
if ($LASTEXITCODE -ne 0 -or $pending) { throw 'Conserve y revise los cambios antes de actualizar.' }
git pull --ff-only origin feature/CB-DEPLOY-001
if ($LASTEXITCODE -ne 0) { throw 'No forzar ni resetear una rama divergente.' }
./docker/start-local.ps1
docker compose ps -a
git rev-parse HEAD
~~~

Web http://localhost:5180 y panel http://localhost:5180/admin. Una instalación nueva no contiene la cuenta ni los datos privados de esta PC. El script genera .env privado solo si no existe y no hay volúmenes previos; no sustituye credenciales de volúmenes existentes ni crea cuentas predeterminadas. No ejecutar down -v.

Para construir un release: ./deploy/build.ps1 desde checkout limpio. Genera .local/releases con VERSION, SHA256SUMS y ZIP. Ensayo aislado: ./deploy/test.ps1 -PackagePath RUTA_DEL_PAQUETE. El workflow Shared hosting package publica un ZIP temporal como artifact; cuando caduque se reconstruye desde el SHA. No usar paquetes dirty en producción.
Suite CMS desechable, tras configurar el entorno local: ./tests/run.ps1 -Port 5190.

## Exclusiones y traslado privado

| Archivo o dato local | Motivo y recuperación |
| --- | --- |
| .env del worktree CMS y configuración privada | Credenciales; no subir. Transferir de forma segura si se reutilizan sus volúmenes. Para instalación vacía, generar configuración nueva. |
| .local/activar-administrador.html y activaciones del almacenamiento | Enlace privado de un uso; no publicar ni reutilizar como mecanismo de traslado. |
| .local/backups y volúmenes MariaDB/api_storage | Datos privados, cuentas, hashes, auditoría y archivos. Transferencia cifrada separada si se requiere conservar el estado. |
| .local/releases | Paquetes regenerables sin secretos; fuente y constructor en GitHub, artifact en CI. No son el estado privado ni una instalación en Yachay. |
| .local/*.log, pruebas auxiliares, capturas HTML, apache-check y home-continuity-check | Diagnóstico, ensayo o clon de verificación; no son dependencias para continuar. |
| web/node_modules y web/dist | Dependencias y build regenerados por Docker. |

Para trasladar el estado real: copia coherente de base y almacenamiento, suspendiendo escrituras; restaurar y validar primero en instalación aislada. Conservar cualquier configuración asociada. No importar fixtures, no restaurar una base vacía sobre datos existentes ni sobrescribir cuentas.
El script docker/backup.ps1 existe; esta revisión no generó ni trasladó respaldos. Los archivos profesionales privados fuera del proyecto requieren inventario del titular si deben acompañar la mudanza.

## Pendientes para Yachay

1. Restablecer acceso operativo al navegador y entrar con la sesión del titular en https://yachay.lat/clientarea.php. El controlador falló antes de leer la sesión; una pestaña abierta no acredita acceso administrable.
2. Verificar dominio/delegación DNS, IP asignada y capacidades del plan. Las consultas anteriores no confirmaron el DNS; no se presume disponibilidad o propiedad por ese fallo.
3. Confirmar directorio público y almacenamiento privado exclusivos, PHP por dominio, extensiones, base/usuario propios y TLS para principal/www. Las rutas propuestas del reporte no son rutas reales verificadas.
4. En una futura ejecución de despliegue autorizada, respaldar Carlos si existe, instalar el paquete de SHA limpio, configurar privadamente, migrar y validar HTTPS, panel, permisos, contenido y archivos.
5. Revisar PR #1 y #2. Ninguno se fusiona por este encargo.

Detalle de instalación y reversión: [CB-DEPLOY-001-YACHAY.md](CB-DEPLOY-001-YACHAY.md), [INSTALL.md](../deploy/INSTALL.md). Historia del CMS: [verificación](CB-CMS-002-VERIFICATION.md), [runbook](CB-CMS-002-RUNBOOK.md).
