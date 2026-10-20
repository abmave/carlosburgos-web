# CB-DEPLOY-001 — Preparación Yachay

Fecha: 19 de octubre de 2026. Dominio autorizado: **carlosburgosh.com**, canónico HTTPS; www debe redirigir al principal.
Rama: feature/CB-DEPLOY-001, creada desde 5e9d4b63dfeb9bafa81d9fea5474642a7a95e5cc de feature/CB-CMS-002. PR dependiente del PR #1, sin fusionarlo. El SHA fuente exacto de cada paquete está en VERSION y sus hashes en SHA256SUMS.

## Estado real

**No publicado en Yachay.** Se dispone de la URL del portal https://yachay.lat/login, pero no de una sesión administrable: el controlador del navegador falla antes de abrirlo, incluso tras reiniciarlo. Se solicitó abrirlo en el panel de Codex. No se han utilizado credenciales ajenas ni realizado escrituras en hosting/DNS.
El bloqueo externo es acceder efectivamente al panel autorizado. Hasta entonces no se pueden acreditar cuotas, aislamiento, PHP por dominio, bases disponibles, qpdf/exec, certificados ni transferencia.

DNS local de carlosburgosh.com y www devuelve error de servidor. Consultas directas a resolutores 1.1.1.1/8.8.8.8 agotaron espera y DNS-over-HTTPS no estuvo disponible desde esta red. Esto **no demuestra** que el dominio esté libre, comprado, mal delegado o apuntando a Yachay. Verificar zona y delegación con acceso efectivo; no cambiar nameservers ni registros de DaleOK.
daleok.pe resolvió 161.132.57.188 y respondió HTTPS 200, Apache, cuerpo de 485 bytes. Esa IP no se asignó a Carlos por suposición.
La comprobación Windows utilizó validación TLS normal con revocación best-effort porque Schannel no pudo consultar revocación; no acredita una auditoría TLS completa del proveedor.

## Cambios y paquete

- deploy/build.ps1 construye React con Node en Docker; exige checkout limpio para release. -AllowDirty produce solo un ensayo marcado dirty.
- Solo se empaquetan estáticos y fuentes PHP/SQL versionadas. No .env, config.local.php, invitaciones, bases reales, tests, dependencias, .git ni worktrees.
- Layout public/ y private/ hermanos; puente PHP en public/api/index.php. Nunca subir el repositorio completo ni copiar dentro de DaleOK.
- Configuración privada de origen HTTPS y ruta de almacenamiento. invite-admin.php valida el origen antes de crear la cuenta y ya no impone localhost en producción.
- PDF se puede deshabilitar con pdf_uploads=false; devuelve un error comprensible y conserva imágenes. No se retiró qpdf: si se habilita PDF, sigue validando el archivo completo. exec deshabilitado también rechaza PDF.
- Reglas propias de Carlos: HTTPS, www→principal, API separada de SPA, assets inexistentes 404 y bloqueo de rutas privadas.
- preflight.php es CLI, sin phpinfo público; comprueba versión/extensiones/BD/origen/almacenamiento y política PDF.
- La CI genera y adjunta el ZIP sin configuración privada. Las pruebas Apache usan certificado autofirmado y credenciales efímeras solo en la red Docker.

## Destino propuesto, aún no confirmado

/home/USUARIO/carlosburgos/public como document root exclusivo; /home/USUARIO/carlosburgos/private fuera de TODOS los document roots y aliases. No son rutas confirmadas de Yachay. El puente exige esa relación de directorios; si el plan no permite aislamiento equivalente, detener publicación.
Base y usuario exclusivos con prefijo cPanel asignado; privilegios solo sobre Carlos. No se han creado ni inventado nombres/credenciales reales.
PHP 8.2 por dominio, PDO MySQL, sesiones, JSON, OpenSSL, fileinfo y GD JPEG/PNG/WebP. Confirmar Apache/rewrite/AllowOverride y límites de carga; no cambiar PHP global ni .htaccess compartido.
Certificado válido para carlosburgosh.com y www antes de activar redirecciones. No confiar en X-Forwarded-Proto arbitrario. Si el proveedor termina TLS en proxy, verificar su integración antes de exponer /admin.
PDF permanecerá deshabilitado hasta comprobar qpdf y exec en el hosting real. El CMS en ese modo tiene esa limitación explícita.

## Instalación, migraciones y reversión

Procedimiento completo en [deploy/INSTALL.md](../deploy/INSTALL.md), incluido el paquete.
Confirmar destino vacío o inventariar instalación Carlos existente; respaldar solo Carlos antes de cambiar archivos/base. Preservar configuración y almacenamiento entre releases.
Migrar por CLI en orden con console.php migrate; si no hay CLI, revisar schema_migrations e importar solo SQL pendientes mediante phpMyAdmin. No repetir ALTERs ni crear instaladores HTTP.
No importar fixtures. El inventario real previo es una cuenta y cero contenidos/medios: trasladar datos reales solo privadamente si se decide conservarlos, con copia coherente y restauración aislada. No reemplazar ninguna cuenta existente.
Reversión de archivos al release anterior y, si es necesario, restauración conjunta compatible de base/almacenamiento tras conservar nuevas escrituras. No restaurar una base vacía sobre una activa.

## Pruebas

Suite CMS local en Docker, proyecto cb-cms002-test-20261019154150: **aprobada**. Sintaxis PHP, build Vite, 87 comprobaciones API, suplementarias, 137 regresiones de contraseña, Chromium y persistencia tras down/up. Base desechable, sin modificar cuentas reales.
Ensayo Apache local del paquete: **aprobado**, proyecto cb-deploy-test-20261019204617. Integridad SHA256 y exclusión de privados, PHP 8.2/extensiones/BD, origen de activación HTTPS, redirecciones HTTP/www, SPA y detalle inexistente, API, assets 404, rutas privadas denegadas, cookie Secure/HttpOnly/SameSite, login/CSRF/logout, imagen recuperable y PDF válido/inválido en ambos modos. Chromium escritorio 1440×1000 y móvil 390×844 sin errores JavaScript ni desbordamiento. Certificado autofirmado solo para el ensayo; no acredita TLS real de Yachay.
La portada pública de DaleOK se leyó de nuevo al terminar: hash SHA-256 idéntico, a12f2b173c1a8fd4abc07dab373b1725201d69c43c3f38c66578c8e8f4368410. No hubo escrituras remotas.
La CI final del SHA de esta entrega se registra en el PR y cierre; no se atribuye la CI del CMS anterior al nuevo SHA.
Pruebas Yachay (TLS real, redirección, panel/login, API, archivos privados, imagen/PDF, móvil/escritorio): pendientes de acceso y publicación controlada.

## Contenido y límites

Se mantiene noindex,nofollow: dominio autorizado, pero contenido y salida final aún no validados. No se acredita SEO/prerender.
Contacto utiliza solo valores configurados; WhatsApp es un enlace condicionado al número existente, no chatbot ni botón flotante. No se inventó ningún número. Sin número autorizado no hay enlace que probar.
No se inició agenda, correo, pagos ni otros módulos.

## Continuar

1. Restablecer controlador de navegador y sesión del titular en Yachay; verificar servicio/plan y dominio dentro del panel.
2. Confirmar DNS autoritativo e IP asignada a Carlos; configurar únicamente sus registros, directorio, base y TLS.
3. Validar capacidades, respaldar Carlos si existe, instalar paquete de SHA limpio y configuración privada, migrar sin datos ficticios.
4. Completar pruebas de producción con titular o cuenta temporal expresamente autorizada; lectura de DaleOK antes/después.
5. Registrar URL, SHA desplegado, capacidades reales, pruebas y rollback; conservar límites si PDF queda deshabilitado.

No hay un SHA desplegado en hosting mientras no se complete ese procedimiento.
