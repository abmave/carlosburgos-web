# Desarrollo y estado

## Arranque

Desde C:\\DEV\\carlosburgos:

```powershell
git fetch origin
git switch --track origin/feature/CB-FOUNDATION-001
cd web
npm install
npm run dev
```

API, en otra terminal desde la raíz:

```powershell
php -S localhost:8080 api/public/index.php
```

Comprobar http://localhost:8080/api/health. Este endpoint comprueba el proceso PHP; no comprueba MariaDB.

## Estado real

Scaffold: portada provisional, vista estática de módulos CMS, endpoint health y migración inicial. No hay aún autenticación, CRUD, reservas ni conexión a MariaDB. No usar en producción.

## Despliegue previsto

Compilar web con npm run build. Apache servirá web/dist y dirigirá /api a api/public/index.php. Configuración, archivos privados y credenciales deben permanecer fuera del directorio público. Configurar fallback de React solo para páginas, nunca para rutas API. Definir reglas Apache y validar el dominio en RELEASE-001.

No almacenar consentimientos ni documentación confidencial en la biblioteca pública de medios.
