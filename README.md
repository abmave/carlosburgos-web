# Carlos Burgos Huapaya

Web institucional y CMS propio.

## Stack

- React + Vite: web pública y backoffice.
- API REST en PHP 8.2.
- MariaDB.
- Apache y hosting compartido con cPanel.

## Desarrollo local Windows

```powershell
cd C:\DEV
git clone https://github.com/abmave/carlosburgos-web.git carlosburgos
cd carlosburgos
```

## Alcance

Inicio, perfil del estudio, especialidades, equipo, casos aprobados, actualidad, multimedia, citas y contacto. CMS para contenido, usuarios y permisos.

## Etapas

1. FOUNDATION-001: estructura y entorno de desarrollo.
2. WEB-001: diseño responsive y contenido validado.
3. CMS-001: autenticación y gestión de contenido.
4. CONTACT-001: consultas y agenda.
5. RELEASE-001: pruebas y despliegue cPanel.

No publicar credenciales, documentos privados, consentimientos ni datos de clientes en este repositorio público.

## Desarrollo con Docker

Solo Docker Desktop con contenedores Linux y Compose v2.

```powershell
cd C:\DEV\carlosburgos
docker compose up -d --build --wait --wait-timeout 300
```

Web: http://localhost:5180 · CMS: http://localhost:5180/admin

[Arranque, creación del administrador y persistencia](docs/DOCKER.md).
