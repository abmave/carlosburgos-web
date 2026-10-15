# Desarrollo del sitio y backoffice

El primer flujo del CMS permite iniciar sesión, editar textos de Inicio y contacto, y publicarlos en MariaDB. La web pública consulta ese contenido al cargar. El formulario público de consultas sigue sin enviar datos. Especialidades, equipo, casos, multimedia, artículos, citas y administración de usuarios aún no tienen CRUD.

## Desarrollo habitual con Docker

Usar [la guía Docker](DOCKER.md): `docker compose up -d --build --wait --wait-timeout 300`. PHP, Node.js y MariaDB se ejecutan en contenedores. Abrir http://localhost:5180/admin y crear la cuenta con docker/create-admin.ps1.

Los pasos siguientes son una alternativa sin Docker y una referencia para el hosting; no requieren instalar herramientas en Windows para el flujo habitual.

## Requisitos de la alternativa sin Docker

- Node.js compatible con Vite y npm.
- PHP 8.2 o superior con PDO y pdo_mysql, sesiones, JSON y OpenSSL habilitados.
- MariaDB local en ejecución; crear una base exclusiva llamada carlosburgos y un usuario exclusivo con permisos sobre esa base.
- Comprobar `php --version` y `php -m`. Si PHP no está en PATH, usar la ruta del ejecutable de la instalación local.

## Actualizar la copia local

En una terminal distinta de la que ejecuta Vite:

```powershell
cd C:\DEV\carlosburgos
git pull --ff-only
```

## Configurar MariaDB

Crear la base y el usuario desde el administrador de MariaDB. No usar una base de DaleOK para las pruebas.

```powershell
Copy-Item api\config.example.php api\config.local.php
```

Editar api/config.local.php con host, puerto, base, usuario y contraseña locales. Mantener environment en local solo durante desarrollo. El archivo está ignorado por Git y no debe publicarse.

Aplicar las migraciones en orden:

```powershell
php api/bin/console.php migrate
```

Si el hosting no ofrece consola, las migraciones SQL también pueden importarse en orden mediante phpMyAdmin; la creación inicial de la cuenta se debe ejecutar en un entorno seguro con acceso a esa misma base, nunca mediante una ruta pública de instalación.

## Crear el primer administrador

Sustituir el correo de ejemplo por el del administrador real. El script solicita una contraseña sin mostrarla; no existen credenciales predeterminadas.

```powershell
.\api\bin\create-admin.ps1 -Email "administrador@ejemplo.com" -Name "Administrador"
```

La contraseña admite entre 12 y 72 bytes. La cuenta se crea como admin; no se sobrescriben cuentas existentes. El script requiere PHP disponible en PATH. Como alternativa multiplataforma, `php api/bin/console.php create-admin correo "Nombre"` lee la contraseña desde entrada estándar; la terminal puede mostrar esa entrada, por lo que en Windows se recomienda el script de PowerShell.

## Arrancar la API y la web

Terminal 1, desde la raíz del proyecto:

```powershell
php -S 127.0.0.1:8080 api/public/index.php
```

Terminal 2:

```powershell
cd C:\DEV\carlosburgos\web
npm ci
npm run dev
```

Reiniciar Vite si estaba abierto antes de actualizar: hay una nueva configuración de proxy /api. Abrir la URL que anuncie Vite y añadir /admin. Usar siempre el mismo hostname durante la sesión; no alternar localhost con 127.0.0.1.

La API se accede a través del proxy de Vite, sin CORS abierto y sin tokens en localStorage. La API crea api/storage/sessions automáticamente; este directorio debe ser escribible por PHP y permanecer fuera del directorio público.

## Prueba manual del flujo

1. Entrar a /admin con la cuenta creada.
2. Abrir Inicio y configuración, cambiar el título de portada y pulsar Guardar y publicar.
3. Abrir o recargar la web pública y comprobar el título.
4. Cerrar sesión y comprobar que ya no se puede editar.

Los cambios se publican inmediatamente al guardar; esta entrega no incluye borradores. El editor advierte de cambios pendientes y rechaza guardar si alguien publicó una versión más reciente. El registro cms_audit conserva usuario, acción, versión y fecha, sin copiar el contenido completo.

## Pruebas automatizadas

Compilar frontend con `npm ci` y `npm run build` desde web.

`tests/api_smoke.py` requiere Python y una base desechable, con API en ejecución, cuenta de prueba y variables CB_TEST_API, CB_TEST_EMAIL y CB_TEST_PASSWORD. El script publica temporalmente un título y lo restaura. Verifica login, cookie HttpOnly, acceso sin sesión, CSRF, validación, publicación, conflicto de versión y logout. No ejecutarlo sobre producción.

## Hosting compartido

- Servir web/dist con Apache y enrutar /api a api/public/index.php.
- Mantener api/src, api/config.local.php, api/storage, api/bin y database fuera de public_html.
- Si no hay alias ni enlace simbólico, usar un index.php puente en public_html/api que requiera la ruta absoluta del index.php real del proyecto; no mover el bootstrap ni las credenciales al directorio público.
- Instalar las reglas de api/public/.htaccess en el directorio de entrada de la API. El fallback de React debe excluir /api y /media.
- Usar environment production y HTTPS. La cookie es Secure en ese modo; HttpOnly y SameSite Strict en todos los modos. No confiar en cabeceras de proxy proporcionadas por el cliente.
- Configurar PHP para no mostrar errores, realizar backups de MariaDB y programar limpieza de registros login_limits con más de 24 horas. El endpoint /api/health solo verifica PHP; /api/public/settings comprueba la lectura de contenido desde MariaDB.
- El límite de login permite 10 intentos por IP cada 15 minutos, también cuenta accesos correctos. Verificar REMOTE_ADDR si el proveedor usa un proxy: no se interpreta X-Forwarded-For automáticamente.

El despliegue en cPanel y su validación de HTTPS siguen pendientes. Las pruebas realizadas para esta entrega usan PHP 8.3 y MariaDB 10.11; validar también PHP 8.2 del hosting antes de publicar.
