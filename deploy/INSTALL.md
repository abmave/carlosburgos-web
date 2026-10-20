# Instalación exclusiva de Carlos Burgos
No extraer este paquete en DaleOK ni modificar su configuración.
Antes de publicar, confirmar en cPanel dominio carlosburgosh.com, alias www, IP asignada, cuota, PHP por dominio, TLS y base/usuario exclusivos.

Layout requerido: /home/USUARIO/carlosburgos/public (document root exclusivo) y /home/USUARIO/carlosburgos/private (fuera de todo document root). El puente público resuelve private como hermano de public. No usar un directorio privado que sea público mediante otro dominio o alias.

Verificar SHA256SUMS antes de transferir. VERSION contiene el SHA fuente (un sufijo dirty invalida el paquete para producción).
Subir public y private por SFTP/FTPS o cPanel seguro. VERSION, SHA256SUMS e INSTALL.md quedan fuera del document root.
Crear private/api/config.local.php desde config.example.php por mecanismo privado, con base/usuario exclusivos y credenciales reales. No subir .env. Configuración 0600, directorios privados 0700 cuando el PHP del dominio corre como titular; ajustar propietario/grupo con mínimo acceso necesario, nunca 777.
Almacenamiento persistente fuera de los releases si se usan versiones paralelas; configurar storage_path absoluto. No reemplazarlo con un directorio vacío.
Comprobar extensiones PHP y ejecutar private/api/bin/preflight.php por CLI; su resultado es diagnóstico, no sustituye pruebas HTTPS.
Aplicar private/api/bin/console.php migrate desde CLI. Sin CLI: revisar schema_migrations e importar por phpMyAdmin únicamente los SQL pendientes en orden; 003/004 no se pueden repetir ciegamente. No instalar migradores públicos.
Para una base vacía crear invitación CLI con invite-admin.php correo nombre; usa public_origin HTTPS y deja un archivo privado de 30 minutos. Nunca reemplazar cuentas existentes ni publicar esa invitación. Sin CLI, solicitar terminal temporal al proveedor o preparar una operación privada validada; no crear instalador HTTP.
Las reglas de public/.htaccess solo pertenecen a Carlos. Certificar HTTPS tanto para principal como www antes de activar redirección canónica. No confiar en X-Forwarded-Proto proporcionado por clientes.
PDF está deshabilitado explícitamente en el ejemplo. Solo habilitar pdf_uploads tras verificar qpdf/exec y probar un PDF válido y otro inválido. Imágenes requieren GD JPEG/PNG/WebP.
Mantener noindex mientras contenido profesional y salida pública final no estén validados.
Antes de cambiar una instalación Carlos existente, suspender escrituras y respaldar conjuntamente sus archivos, configuración, base y almacenamiento fuera de HTTP. Restaurar primero de forma aislada. No respaldar DaleOK.
Reversión: volver al release anterior con su configuración y almacenamiento compatible. Si cambió el esquema, no revertir base a ciegas: restaurar copia conjunta solo tras preservar nuevas escrituras y revisar compatibilidad. No importar una base vacía.
Verificación final: HTTPS, www→principal, portada, panel, API, cookie Secure/HttpOnly/SameSite, sesión/CSRF/permisos, imagen, política PDF, rutas privadas denegadas, móvil/escritorio y lectura de DaleOK antes/después.

## Ruta privada alternativa
Si el dominio ya apunta a public_html/carlosburgosh, construir con ./deploy/build.ps1 -PrivateRoot /home2/rakhidlp/carlosburgosh-private. Copiar el CONTENIDO de public/ a la raíz asignada al dominio y el CONTENIDO de private/ a esa ruta privada. LAYOUT.json registra la ruta que usa el puente; no mover private/ dentro de public_html. No instalar el ZIP completo en la carpeta pública. El ensayo Docker monta y prueba la misma ruta absoluta. La ruta privada es propuesta y debe crearse; la raíz pública fue confirmada por el titular en cPanel el 20 de octubre de 2026.
