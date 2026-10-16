# Operación del CMS editorial

Este documento concentra el arranque, uso, pruebas y recuperación. Los documentos CMS-001 y DOCKER describen la base histórica; para este bloque use esta guía.

## Entorno de esta entrega

Worktree: `C:\DEV\carlosburgos\.worktrees\CB-CMS-002`.
Rama: `feature/CB-CMS-002`, dependiente de `feature/CB-FOUNDATION-001`.
Sitio: http://localhost:5180. Panel: http://localhost:5180/admin.
No se publica en hosting ni se fusionan ramas.

```powershell
Set-Location C:\DEV\carlosburgos\.worktrees\CB-CMS-002
docker compose config --quiet
docker compose up -d --build --wait --wait-timeout 300
docker compose ps -a
```

La configuración privada está en `.env` ignorado. Para una instalación NUEVA, copiar `.env.example` y asignar contraseñas aleatorias; no cambiar esas variables sobre volúmenes existentes creyendo que rotan las cuentas MariaDB. La base local de esta entrega conserva las credenciales de desarrollo con las que se inicializó; no son credenciales de producción. MariaDB no publica ningún puerto al host. API y Vite se comunican por la red Compose y el navegador usa `/api` del mismo origen. Los demás proyectos Docker no se administran desde estos comandos.

El almacenamiento usa `/storage` en volumen propio: sesiones, medios públicos y evidencias privadas se separan en directorios fuera del document root. PHP corre como usuario `app`, sin privilegios, con raíz de solo lectura y `/tmp` temporal. La recarga React usa polling para archivos Windows.

## Acceso y edición

La cuenta inicial se crea por invitación local, sin contraseña predeterminada:

```powershell
docker compose exec -T api php api/bin/invite-admin.php correo-del-administrador nombre
New-Item -ItemType Directory -Force .local | Out-Null
docker compose cp api:/storage/admin-activation.html .local/activar-administrador.html
```

El script falla si el correo ya existe: no reemplaza usuarios. Abrir el archivo HTML privado en el navegador y definir una contraseña de 12–72 bytes. El enlace vence a los 30 minutos y solo funciona una vez. No adjuntar el HTML a issues, PR o reportes. Las cuentas adicionales y sus enlaces se gestionan desde Usuarios. No se envía correo. Cambiar contraseña, rol o estado invalida las sesiones existentes. Debe quedar al menos un administrador activo.

Inicio y configuración publica directamente y conserva versión y auditoría. Los demás módulos permiten guardar borrador, buscar, filtrar y publicar o retirar. Retirar equivale a eliminación lógica: conserva historia y evita romper relaciones. Los enlaces legibles son únicos por módulo. Los conflictos devuelven 409 y requieren recargar antes de guardar.

El cuerpo editorial es texto plano estructurado en campos y párrafos; HTML se muestra como texto mediante React. FAQs se escriben por párrafos. Especialidades y profesionales no confirmados no se crean ni publican automáticamente. Equipo y especialidades se relacionan mediante selección múltiple, y las relaciones públicas solo incluyen registros publicados. La vista previa guardada requiere una sesión activa.

Multimedia admite video, entrevista y galería de hasta 30 imágenes. YouTube y Vimeo son las únicas fuentes de video admitidas: HTTPS con un identificador, sin iframes libres ni parámetros adicionales. El reproductor se carga al pulsarlo. La biblioteca recibe JPEG, PNG, WebP y PDF de hasta 8 MiB; comprueba extensión, contenido y decodificación. Las imágenes se recodifican; PDF se valida con qpdf y se descarga como adjunto. Los nombres son aleatorios. No se elimina un medio utilizado por ningún contenido, borrador o configuración.

## Casos

Flujo: borrador → revisión → aprobación administrativa → publicación. El estado jurídico es un campo separado. La aprobación requiere anonimización y fundamento; cuando se marca que requiere consentimiento, una evidencia privada del mismo caso es obligatoria. Solo el administrador puede consultar y adjuntar evidencia, aprobar y publicar. Se conservan instantáneas y contexto de aprobación en auditoría interna; no se incluyen en respuestas públicas.

Editar título, dirección, contenido, relaciones, imagen u orden de un caso aprobado/publicado revoca la aprobación, lo devuelve a revisión y lo retira inmediatamente de la web. No se mantiene una versión pública anterior. Para volver a publicarlo se requiere otra aprobación. El administrador debe aprobar la versión ya guardada, sin cambios pendientes. Las evidencias son inmutables y no forman parte de la biblioteca pública.

## Respaldos, migraciones y reinicio

Antes de cambios de datos importantes:

```powershell
./docker/backup.ps1
docker compose exec -T api php api/bin/console.php migrate
docker compose down
docker compose up -d --wait --wait-timeout 300
```

El respaldo crea un dump transaccional y copia `/storage` en `.local/backups`, ignorado por Git. Contiene información privada y debe custodiarse. Para un respaldo coordinado en uso multiusuario, suspender temporalmente las escrituras editoriales mientras se copian base y archivos. No usar `down -v` ni borrar volúmenes. Las migraciones 003 y 004 son adicionales; 001 y 002 no se modificaron. Al repetirlas se omiten las ya registradas (001 usa operaciones idempotentes).

Una restauración debe hacerse primero en un proyecto Compose de recuperación aislado: copiar el dump a su contenedor db, importarlo con `mariadb` utilizando las variables privadas del contenedor, copiar el respaldo de almacenamiento al volumen del API y verificar usuarios/contenido antes de sustituir el entorno afectado. No restaurar sobre una base activa sin un respaldo nuevo y una decisión explícita.

## Pruebas reproducibles

```powershell
./tests/run.ps1 -Port 5190
```

El script crea un proyecto con nombre único, usa cuentas aleatorias solo en esa base, ejecuta build/sintaxis/API/Chromium, repite migraciones, realiza `down`/`up` y comprueba persistencia. Finalmente detiene únicamente ese proyecto y conserva los volúmenes. No reejecutar `seed.php` sobre una base con usuarios: se niega expresamente a reemplazarlos. La CI ejecuta la misma secuencia con credenciales efímeras. Las pruebas no requieren runtimes globales de aplicación.

## Preparación para hosting futuro

Publicar únicamente el build React y `api/public` como entradas web; `api/src`, configuración, base y almacenamiento deben permanecer fuera del document root. `CB_STORAGE_PATH` permite elegir una ruta privada. Como defensa adicional, `api/storage.htaccess` deniega todo acceso y ejecución si el almacenamiento se copia por error a un directorio público; debe instalarse como `.htaccess` en ese directorio y Apache debe respetar sus directivas. El servicio de archivos pasa por la API, que solo permite tipos registrados y controla evidencia privada.

El hosting deberá confirmar PHP 8.2, pdo_mysql, GD con JPEG/PNG/WebP, fileinfo, qpdf ejecutable, permiso para `exec`, HTTPS y reglas de reescritura. La validación PDF falla de forma cerrada si qpdf no está disponible. Esa compatibilidad real del hosting no se presume probada en Docker.

El HTML Vite entrega un título institucional fijo y `robots=noindex,nofollow`; no hay metadatos específicos por artículo ni prerender. Las rutas inexistentes tienen una vista de error SPA y la API responde 404, pero el servidor Vite entrega 200 para el shell HTML. SEO de publicación, códigos HTTP del hosting y retirar noindex pertenecen al bloque de despliegue.

Agenda, formulario de consultas operativo, SMTP, recordatorios, pagos, portal de clientes, IA, sincronización social, compra de dominio y despliegue continúan fuera de alcance.
