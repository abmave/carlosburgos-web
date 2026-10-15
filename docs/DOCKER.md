# Entorno local en contenedores

Solo se requiere Docker Desktop iniciado, con contenedores Linux y Docker Compose v2. PHP, Node.js y MariaDB se ejecutan dentro de contenedores. El despliegue final sigue siendo en hosting compartido con cPanel.

## Arranque en Windows

```powershell
cd C:\DEV\carlosburgos
git pull --ff-only
docker compose up -d --build --wait --wait-timeout 300
docker compose ps -a
```

La primera ejecución descarga imágenes y dependencias. Deben quedar db, api y web saludables; migrate debe terminar con código 0. Si excede el tiempo inicial, revisar `docker compose logs --tail 80` y volver a ejecutar el comando de arranque.

Web: http://localhost:5180

CMS: http://localhost:5180/admin

No es necesario detener otro Vite en 5173. Solo se publica el puerto web en la interfaz local; MariaDB y la API no publican puertos en el equipo.

## Crear el administrador

Sustituir el correo y nombre por los reales:

```powershell
.\docker\create-admin.ps1 -Email "administrador@ejemplo.com" -Name "Administrador"
```

La contraseña se solicita sin mostrarla y se entrega por entrada estándar al proceso PHP del contenedor. No se almacena en el script ni como argumento del comando. Las cuentas duplicadas se rechazan. No existen cuentas CMS ni contraseñas CMS predeterminadas.

Si la política de PowerShell bloquea el script, se puede ejecutar una sola vez mediante `powershell -NoProfile -ExecutionPolicy Bypass -File .\docker\create-admin.ps1 -Email "administrador@ejemplo.com" -Name "Administrador"`; esto no cambia permanentemente la política del equipo.

## Flujo de validación

1. Entrar al CMS.
2. Editar Inicio y configuración y publicar un título.
3. Abrir o recargar la web pública para comprobar el cambio.
4. Ejecutar `docker compose down`, volver a levantar y comprobar que contenido y cuenta se conservan.

## Comandos frecuentes

```powershell
docker compose logs --tail 80 api web migrate
docker compose exec api php api/bin/console.php migrate
docker compose exec web npm run build
docker compose stop
docker compose up -d --wait --wait-timeout 300
docker compose down
```

`down` conserva los volúmenes de base, sesiones y dependencias. No añadir la opción `-v` para el uso habitual, porque elimina los datos persistentes.

Las nuevas migraciones SQL se aplican con el comando explícito del servicio api. El servicio migrate resuelve la inicialización del primer arranque. El código fuente se monta desde la carpeta del proyecto y Vite usa polling para detectar cambios desde Windows. Las dependencias se instalan en un volumen Linux independiente de node_modules del host.

## Configuración

Compose usa valores de base de datos exclusivamente locales para arrancar sin pasos manuales. No son credenciales del CMS. No utilizar este compose como despliegue público. Para personalizarlos, copiar .env.example a .env ANTES del primer arranque y editar allí los valores. Cambiar la contraseña en .env no modifica las cuentas de un volumen MariaDB ya inicializado; se debe rotar también en la base existente.

El proyecto tiene nombre Compose carlosburgos y volúmenes propios, separados de otros proyectos. El puerto web predeterminado es 5180; CB_WEB_PORT permite elegir otro puerto libre. No se requiere api/config.local.php dentro de Docker: las variables CB_DB_* tienen prioridad. El archivo local continúa soportado para el hosting compartido.

## Validación de esta entrega

El entorno de edición no dispone de motor Docker. Se verifica la configuración resuelta con Docker Compose, la sintaxis PHP, la compilación del frontend y la resolución de configuración por variables. La construcción de las imágenes, salud de contenedores y persistencia tras reinicio deben comprobarse en Docker Desktop con los comandos anteriores.
