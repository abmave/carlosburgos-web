# CB DEPLOY 001 Publicación en hosting compartido Yachay

## Instrucción para Codex

Trabaja en `C:\DEV\carlosburgos` y localiza el worktree válido del proyecto. Prepara y ejecuta el despliegue de la web Carlos Burgos Huapaya en el hosting compartido Yachay donde está daleok.pe. El usuario autoriza el despliegue de Carlos Burgos. Conserva intactos el sitio, base, correo, certificados y configuración de DaleOK. No solicitar nuevamente una aprobación genérica para publicar; solicitar únicamente los datos de destino o acceso que realmente falten, sin exponer secretos.

Usa React + Vite, API PHP 8.2 y MariaDB. Docker es el entorno de construcción y pruebas; no instalar Docker ni un servidor Node persistente en el hosting compartido. No cambiar a WordPress ni cambiar de stack. Este bloque comprende diagnóstico del hosting, correcciones de compatibilidad, empaquetado, respaldo del destino si existe, publicación, validación y documentación.

## Base conocida y verificación inicial

Último SHA verificado: `5e9d4b63dfeb9bafa81d9fea5474642a7a95e5cc`, rama `feature/CB-CMS-002`, PR #1 hacia `feature/CB-FOUNDATION-001`, con CI aprobada. La corrección de contraseña está incluida. No asumir que main contiene esta implementación; consultar el estado actual y no retroceder trabajo posterior.

Lee AGENTS.md, README, CB-HANDOFF, el runbook y el reporte de verificación existentes. Inspecciona el checkout, remotos y cambios del usuario antes de modificarlo. Crea `feature/CB-DEPLOY-001` desde la base que realmente incluye el CMS. No fusionar PR anteriores ni hacer force push por este encargo. El despliegue puede construirse desde un SHA explícito sin fusionar a main; registrar la trazabilidad y dependencia del PR.

El último inventario reportaba una cuenta y cero registros editoriales/medios en la base local. No confundir recursos incluidos en Git con contenido guardado en MariaDB. No inventar publicaciones, credenciales, especialidades confirmadas, teléfonos ni clientes.

## Datos de destino y acceso

No se ha confirmado la compra ni el dominio definitivo. carlosburgos.com fue la recomendación, no una prueba de propiedad. Confirmar dominio o subdominio autorizado, document root asignado y acceso al hosting antes de cambios de DNS o publicación. No utilizar daleok.pe como dominio del abogado ni publicar dentro del directorio de la tienda por suposición.

Reutiliza una conexión autorizada disponible. Si falta acceso, pedir al usuario que lo configure mediante el mecanismo seguro de su entorno, sin solicitar contraseñas por chat, archivos versionados o comandos con secretos visibles. No buscar credenciales en proyectos ajenos ni copiar las de DaleOK.

Mientras falten esos datos, completar todo el empaquetado y las pruebas independientes. No afirmar que se publicó sin acceder y validar el destino real.

## Inspección del hosting sin alterar DaleOK

Verificar y registrar:

- Capacidad del plan para otro dominio/subdominio, almacenamiento, inodos, bases y recursos disponibles.
- Document root exclusivo para Carlos; posibilidad de guardar API interna y archivos privados fuera de public_html o del directorio público del dominio.
- PHP 8.2 o versión compatible validada para este proyecto, seleccionable por dominio sin cambiar la versión de DaleOK.
- PDO/pdo_mysql, sesiones, JSON, OpenSSL, fileinfo y GD con JPEG, PNG y WebP.
- MariaDB y permisos para crear base y usuario exclusivos con el prefijo que asigne cPanel.
- HTTPS válido, Apache/rewrite y directivas .htaccess realmente admitidas. No usar directivas incompatibles que produzcan HTTP 500.
- Medio de transferencia admitido: SFTP/SSH, FTPS o mecanismo seguro del proveedor; no asumir SSH por disponer de cPanel.
- qpdf ejecutable y permiso para exec, requeridos por la validación PDF actual. No asumir que un hosting compartido lo permite.

Si no existe qpdf, no retirar la validación ni aceptar PDFs sin comprobación. Preparar un modo explícito que deshabilite la carga PDF con mensaje comprensible y mantenga operativas las imágenes si es viable; documentar la limitación pendiente. Para recuperar PDF, proponer una alternativa compatible y validada, sin servicios externos ni costos no acordados. No declarar el CMS completo en producción si esa capacidad queda limitada.

Cualquier impedimento de aislamiento, TLS o compatibilidad fundamental debe resolverse antes de exponer el panel.

## Distribución de archivos

Generar el build React en un contenedor. El hosting sirve estáticos; no se ejecuta Vite en producción.

Separación lógica requerida, adaptable a las rutas reales de Yachay:

- Document root de Carlos: build web, assets y entrada pública /api.
- Directorio privado del proyecto fuera del document root: api/src, configuración, scripts CLI, migraciones y almacenamiento.
- Almacenamiento privado con sesiones, evidencia y archivos de activación fuera del alcance HTTP.
- Medios públicos servidos por la API conforme a la política actual; nunca publicar evidencia en una carpeta accesible.

El index.php público puede actuar como puente a la ruta absoluta privada; comprobar __DIR__, includes y nombres reales. No copiar todo el repositorio al directorio público.

Crear reglas de reescritura exclusivas del dominio Carlos: /api se resuelve en la API y no cae en index.html; assets inexistentes no reciben HTML; las páginas públicas y /admin usan el enrutamiento adecuado. No editar .htaccess de DaleOK ni uno compartido con su tienda sin demostrar su alcance y necesidad.

Mantener deny-by-default para directorios privados; comprobar por HTTP que configuración, migraciones, backups, Git, sesiones y evidencia no sean accesibles. No subir .env, claves, datos de prueba, dependencias, worktrees ni reportes privados al document root.

## Configuración y base de producción

- Crear base y usuario exclusivos; privilegios limitados a la base Carlos.
- Configuración privada con environment production, credenciales del destino y ruta de almacenamiento real.
- HTTPS y cookie Secure, HttpOnly/SameSite; verificar persistencia de sesión con el proxy real del proveedor. No confiar sin validación en cabeceras aportadas por clientes.
- Aplicar migraciones en orden y conservar las ya aplicadas. Usar CLI si existe; de lo contrario preparar SQL y un procedimiento seguro mediante phpMyAdmin. No crear instaladores públicos permanentes.
- No importar datos de pruebas. Si se traslada la base real, respaldo e importación privados, coherentes con medios y evidencia; inventariar qué se importa y no reemplazar datos existentes sin analizar el destino.
- Si es instalación nueva, crear al administrador mediante procedimiento seguro de una sola ejecución. No usar credenciales predeterminadas ni imprimir enlaces de activación en el reporte.
- Evitar el enlace hardcodeado localhost:5180 del script de invitación: usar origen HTTPS confirmado y configurable antes de crear activación en producción.
- Errores en logs privados; display_errors desactivado. Ajustar límites de carga y permisos de archivos/directorios sin usar 777.

## Web y contenido

Verificar que portada y contacto no contengan información presentada falsamente como confirmada. La ausencia de colecciones debe mostrarse como estado vacío cuidado. El acceso WhatsApp actual es un enlace en Contacto condicionado al número configurado; comprobarlo si existe un número autorizado, sin inventarlo y sin afirmar que hay chatbot o botón flotante.

No ampliar este bloque con agenda, SMTP, automatizaciones, pagos o portal privado.

El sitio conserva noindex y metadatos SPA básicos. Mantener noindex para un destino de revisión o contenido no aprobado. Retirarlo únicamente cuando el dominio definitivo y la salida pública estén confirmados y no haya placeholders presentados como datos reales. Registrar explícitamente la decisión. El despliegue no acredita SEO/prerender por artículo si no se implementó.

## Paquete, respaldo y reversión

Preparar paquete reproducible con versión/SHA, manifiesto de archivos y checksums, sin credenciales. Configuración privada por separado; no adjuntarla al PR. Incluir ejemplo de configuración y pasos mínimos de instalación.

Si hay una versión Carlos en el destino, respaldar sus archivos y base antes de cambiarla. No respaldar ni tocar datos de DaleOK como parte de este despliegue. Publicar de forma controlada y disponer de reversión de archivos y esquema/datos compatibles. No sobreescribir la instalación activa con una importación vacía.

Ensayar el paquete con Apache y PHP 8.2 en Docker, aproximando el layout cPanel, no solo contra Vite o el servidor de desarrollo PHP. Evitar depender de qpdf o permisos disponibles en Docker sin comprobarlos en Yachay.

## Pruebas obligatorias

1. Build y pruebas existentes sobre una base desechable, incluyendo la regresión de contraseña.
2. Ensayo Apache del paquete: portada, /admin, rutas de detalle, /api/public/settings, cookies y login de prueba aislado, assets y errores.
3. En Yachay: HTTPS, portada, formulario del panel, API y configuración, lectura de publicaciones reales o estados vacíos.
4. Sesión y permisos: login mediante cuenta de prueba temporal autorizada o titular; sin cambiar su contraseña. Logout, CSRF y accesos anónimos rechazados.
5. Carga de imagen y entrega de archivo en prueba controlada; PDF validado o deshabilitado explícitamente cuando no sea compatible.
6. Evidencia privada, configuración y respaldos inaccesibles sin permiso. No crear exposición temporal para probar.
7. Móvil y escritorio, enlaces y WhatsApp configurado, sin errores JavaScript relevantes.
8. Comprobar lectura de daleok.pe antes y después para detectar regresiones del despliegue, sin escribir datos ni hacer pedidos.
9. No ejecutar fixtures ni suites destructivas sobre producción. Las pruebas de datos deben ser separadas y autorizadas, con limpieza limitada a lo creado para esa prueba.

Si falta el dominio o acceso, registrar paquete y ensayo aprobados por separado de publicación y pruebas en Yachay pendientes.

## Entregables

- Código/configuración reproducible del despliegue y paquete generado sin secretos.
- `docs/CB-DEPLOY-001-YACHAY.md`: rutas, requisitos reales, SHA publicado, capacidades/limitaciones, pasos de migración, backups/reversión y pruebas ejecutadas con resultado.
- Actualización de CB-HANDOFF con el estado de producción y cómo construir el siguiente release.
- Rama publicada y PR de despliegue con base correcta; sin fusionar automáticamente PR existentes.
- Cierre conciso: URL real si se publicó, SHA, CI, resultado de validaciones, estado de DaleOK y pendientes concretos. Si no se publicó, decirlo y precisar el único bloqueo externo que lo impide.

No trasladar al usuario comandos que puedes ejecutar en el entorno autorizado. Pedirle solo dominio, acceso o participación personal indispensable para completar el objetivo.
