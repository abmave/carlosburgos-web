# CB CMS 002 Cierre del entorno y CMS editorial funcional

## Instrucción para Codex

Trabaja en `C:\DEV\carlosburgos`, repositorio `https://github.com/abmave/carlosburgos-web.git`. Ejecuta este bloque completo: inspección, implementación, pruebas, correcciones, documentación y publicación de una rama con PR para revisión. No te detengas para pedir al usuario que ejecute comandos rutinarios. Resuelve las decisiones de implementación dentro del alcance y entrega un resultado verificable. No declares completado un componente que solo tenga interfaz, datos simulados o persistencia en el navegador.

El usuario revisará el PR y el reporte de cierre. No fusionar ni desplegar al hosting en este bloque. Si una credencial personal o decisión de negocio es indispensable y no puede resolverse de forma segura, pregunta solo por ese dato, manteniéndolo fuera de logs y Git, y continúa los trabajos independientes.

## Objetivo

Dejar el proyecto ejecutándose íntegramente en contenedores y convertir la base existente en un CMS editorial funcional para el estudio jurídico Carlos Burgos Huapaya. El flujo requerido es: el editor inicia sesión, gestiona contenido, guarda borrador, publica, consulta la web y verifica lo publicado; al reiniciar los servicios el contenido persiste.

## Decisiones establecidas

| Aspecto | Decisión |
| --- | --- |
| Web pública y backoffice | React + Vite |
| Backend | API REST en PHP 8.2 |
| Base de datos | MariaDB |
| Desarrollo local | Docker Desktop y Docker Compose, contenedores Linux |
| Producción futura | Hosting compartido con Apache y cPanel, sin procesos Node permanentes |
| CMS | Propio; sin WordPress y sin cambiar de stack |
| Identidad | Carlos Burgos Huapaya; diseño sobrio con los recursos suministrados |
| Dominio recomendado | carlosburgos.com; disponibilidad y compra no verificadas |
| Flujo de trabajo | Bloques completos ejecutados por Codex; revisión y aprobación del usuario al cierre |

No instalar PHP, MariaDB ni Node globalmente en Windows. Ejecutar herramientas de aplicación, migraciones, builds y pruebas dentro de contenedores. No modificar servicios o volúmenes de LLAMA, MANTO, DaleOK u otros proyectos.

## Estado de partida que debe verificarse

Existe la rama remota `feature/CB-FOUNDATION-001`. Según el trabajo previo, contiene:

- Portada responsive, filtros de especialidades y material visual suministrado.
- Login, logout, sesión HttpOnly/SameSite, CSRF y límite de intentos por IP.
- Tablas de usuarios, configuración pública, auditoría y migraciones.
- Edición y publicación directa de textos de Inicio, presentación y contacto.
- Configuración `compose.yaml`: db, migrate, api y web, con volúmenes propios.
- Acceso local previsto: `http://localhost:5180` y `/admin`.
- Scripts de creación de administrador sin contraseña predeterminada.

La validación previa de API y navegador se realizó fuera de Docker con PHP 8.3 y MariaDB 10.11. La configuración Compose fue validada, pero no se confirmó su arranque en Docker Desktop. La captura enviada en el chat no pudo leerse: no asumir que demuestra contenedores saludables.

Los módulos de Especialidades, Equipo, Casos, Actualidad, Multimedia, Citas y Usuarios estaban pendientes. La configuración publica directamente al guardar; no asumir que ya existe un flujo general de borradores. El formulario público no envía ni almacena consultas.

Verifica la situación real del repositorio antes de implementar; el código local y remoto actual prevalece sobre este resumen. Revisa AGENTS.md y convenciones aplicables. Conserva cambios previos del usuario.

## 1 Inspección y preparación

1. Verifica ruta, árbol de trabajo, ramas, remotos, historial y PR existentes.
2. No hagas reset destructivo, limpieza forzada ni descartes archivos del usuario.
3. Si hay cambios ajenos, aíslalos sin moverlos ni sobrescribirlos; usa un worktree si resulta necesario.
4. Crea `feature/CB-CMS-002` desde la base que realmente contiene todo el trabajo anterior. Si aún no está en main, usa la rama anterior y documenta la dependencia; no fusionarla por tu cuenta.
5. Revisa Compose, API, migraciones, CMS, recursos y pruebas existentes. Reutiliza lo válido y corrige lo defectuoso.
6. Registra un diagnóstico breve en el reporte final. No dedicar esta entrega solamente a discovery.

## 2 Cierre real del entorno Docker

Levanta y valida los servicios. Comandos orientativos, que debes adaptar a la situación real:

```powershell
docker compose config --quiet
docker compose up -d --build --wait --wait-timeout 300
docker compose ps -a
docker compose logs --tail 100 db migrate api web
```

Corregir lo necesario hasta comprobar:

- db, api y web saludables; migrate finalizado con código 0.
- PHP 8.2 con pdo_mysql dentro del contenedor.
- La API usa `db` como host por variables de entorno; no depende de configuración local de otro proyecto.
- Vite llega a la API por red interna; navegador accede por el mismo origen.
- El directorio de sesiones es escribible por el usuario de PHP y no se expone públicamente.
- Las migraciones pueden ejecutarse nuevamente sin destruir datos.
- Recarga automática de React desde archivos de Windows.
- Datos persistentes tras `down` y nuevo `up`, sin borrar volúmenes.
- Sin puertos de MariaDB abiertos al host, ni conflictos con proyectos existentes.

No utilizar `docker compose down -v`, borrar volúmenes ni reinicializar una base existente. Usar un proyecto Compose separado y volúmenes desechables para pruebas que requieran datos sintéticos. Una base o cuenta preexistente nunca se reemplaza para facilitar una prueba.

Si Docker Desktop está apagado, indicar ese bloqueo concreto al usuario; no pedirle instalar runtimes globales. Continuar revisión e implementación que no dependan del motor y señalar las comprobaciones pendientes sin presentarlas como aprobadas.

## 3 Backoffice y navegación

Mantener la identidad visual y mejorar la organización del panel. Los módulos funcionales deberán mostrar listados, búsqueda, filtros de estado, formularios, errores y confirmaciones. Los módulos fuera del alcance deben permanecer explícitamente deshabilitados o identificados como pendientes.

- Estados de carga, vacío, error, guardado y publicación comprensibles.
- Aviso al salir con cambios sin guardar.
- Diseño usable en escritorio y móvil.
- Campos con etiquetas, navegación por teclado y foco visible.
- No presentar detalles de tablas, rutas internas ni instrucciones de consola dentro de flujos editoriales.
- No simular éxitos de guardado, cargas ni acciones que la API no ejecutó.

## 4 Alcance funcional de esta entrega

### Inicio y configuración

Conservar el flujo existente y completar su integración: nombre del estudio, textos de portada, presentación, contacto, dirección, horario y redes validadas. Habilitar selección de imágenes desde la biblioteca multimedia. Mantener control de versión y auditoría al publicar. La publicación de configuración puede seguir siendo directa si la interfaz lo explica claramente.

### Biblioteca multimedia

Implementar carga, listado, búsqueda, metadatos, texto alternativo y selección de imágenes en los demás módulos.

- Formatos iniciales: JPEG, PNG, WebP y PDF; sin SVG ni archivos ejecutables.
- Límites de tamaño explícitos y aplicados en API y formulario; coherentes con PHP.
- Comprobar tipo real por contenido, extensión y archivos corruptos; no confiar solo en MIME enviado por el navegador.
- Nombres internos aleatorios, sin rutas controladas por el usuario ni sobrescrituras.
- Archivos públicos separados de los privados. Los consentimientos de casos nunca forman parte de la biblioteca pública.
- No permitir ejecución de PHP u otros scripts en directorios de uploads; verificar también la configuración equivalente para Apache del hosting futuro.
- Evitar eliminar medios referenciados por contenido publicado o advertir y aplicar una política que preserve esas referencias.
- Cargar imágenes desde almacenamiento, sin incrustar nuevos archivos como cadenas base64 dentro del código React.
- Aprovechar los recursos existentes sin deformarlos ni inventar retratos de otros profesionales.

### Especialidades

CRUD persistente con título, slug único, resumen, descripción, imagen, orden, estado y preguntas frecuentes. Listado público y página de detalle; solo mostrar registros publicados. Relacionar, si corresponde, equipo, artículos y casos sin duplicar contenido.

Las especialidades propuestas en el documento original no equivalen a servicios confirmados. Mantenerlas como borradores o pendientes hasta validación del estudio.

### Equipo

CRUD con nombre, cargo, biografía, fotografía, formación, colegiatura, especialidades y estado editorial. Listado y perfil público solo para registros publicados. No inventar títulos, número de colegiatura, experiencia ni integrantes. Datos no suministrados permanecen vacíos o en borrador.

### Actualidad

CRUD de artículos con título, slug, extracto, cuerpo, portada, categoría, autor, fecha y estado. Guardar borrador y publicar; permitir retirar una publicación sin eliminarla físicamente. Listado y detalle públicos, relacionados con especialidades cuando corresponda.

Elegir edición de texto estructurado o sanitización robusta en servidor. Probar que contenido malicioso no ejecuta scripts en web ni backoffice. No usar HTML arbitrario sin sanitización.

### Multimedia editorial

Administrar videos, entrevistas y galería con título, descripción, miniatura, fecha, especialidad, URL y estado. Permitir URLs de plataformas expresamente admitidas y construir los embeds de forma segura; no aceptar iframes o scripts libres. Cargar reproductores bajo demanda cuando sea posible. No implementar scraping ni sincronización automática de redes.

### Casos y experiencia

CRUD con título genérico, especialidad, contexto, estrategia, resultado cuando esté documentado, estado del proceso, integrantes relacionados e imagen o video opcional.

- Separar el estado editorial del estado jurídico del caso.
- Flujo editorial: borrador, en revisión, aprobado, publicado y retirado.
- La API impide publicar sin aprobación vigente registrada por un administrador.
- Registrar revisor, fecha, nivel de anonimización y fundamento de autorización de publicación.
- Si se requiere evidencia de consentimiento, conservarla privada y con acceso restringido al administrador; nunca en respuestas o URLs públicas.
- Editar el contenido de un caso aprobado o publicado revoca su aprobación y lo devuelve a revisión. La versión pública debe dejar de exponerse o conservar explícitamente la última versión aprobada mediante versionado; elegir y documentar una política consistente.
- Incluir la leyenda acordada: “Cada caso es distinto; los resultados anteriores no garantizan resultados similares.”
- No crear testimonios, clientes ni resultados ficticios. Los registros sintéticos de prueba permanecen fuera del sitio real.

Este control editorial es un requisito de producto; no sustituye la aprobación de contenidos por el estudio.

### Usuarios y permisos

Completar gestión básica de cuentas para administrador: listar, crear, activar/desactivar, asignar rol y restablecer contraseña mediante mecanismo seguro. No enviar correos reales sin autorización específica. Si falta proveedor de correo, usar un mecanismo local explícito con expiración y sin exponer secretos en Git o reportes.

Roles mínimos:

| Acción | Admin | Editor |
| --- | --- | --- |
| Editar contenido general | Sí | Sí |
| Publicar especialidades, equipo, actualidad y multimedia | Sí | Sí |
| Aprobar y publicar casos | Sí | No |
| Gestionar usuarios y roles | Sí | No |
| Ver evidencia privada de consentimiento | Sí | No |

Aplicar permisos en API, no solo ocultando botones. Evitar dejar el sistema sin administrador activo mediante desactivación o cambio de rol. Revalidar cuentas activas en las solicitudes y revocar sesiones cuando se cambia contraseña o privilegios. Nunca crear un administrador con contraseña conocida dentro de seeds versionados.

## 5 Integración de la web pública

Sustituir las secciones estáticas que correspondan por contenido real publicado desde la API. Incluir listados y páginas de detalle, slugs consistentes, estados vacíos cuidados y rutas inexistentes con tratamiento correcto. La vista previa de borradores requiere sesión y no debe convertirse en una API pública de contenido privado.

La web no debe presentar registros no publicados ni cifras o credenciales inventadas. No prometer SEO completo mediante cambios de título en una SPA: documentar qué metadatos se entregan realmente en HTML y dejar el prerenderizado o solución equivalente para el bloque de publicación si no se implementa ahora.

## 6 Fuera de alcance

Agenda operativa, envío del formulario público, SMTP de producción, recordatorios, pagos, portal privado de clientes, chatbot, IA, bandeja omnicanal, publicación automática en redes, compra del dominio y despliegue al hosting. No aparentar que están activos. Son bloques posteriores; este encargo cierra el CMS editorial y su entorno.

## 7 Persistencia y controles técnicos

- Migraciones nuevas y ordenadas. No reescribir migraciones aplicadas para imponer cambios al usuario.
- Consultas parametrizadas, validación del lado servidor, paginación y control de permisos por operación.
- Cookies HttpOnly, SameSite y Secure en producción; CSRF en mutaciones con sesión y vencimiento de sesiones.
- Mantener o corregir el límite de intentos de acceso. No confiar en X-Forwarded-For arbitrario.
- No enviar contraseñas, hashes, tokens, evidencia privada ni datos internos en respuestas públicas.
- Auditoría de las acciones relevantes sin registrar contraseñas ni tokens.
- Manejo de conflictos para evitar sobrescribir cambios de otro editor.
- Credenciales exclusivamente en configuración ignorada o variables; no publicar la configuración local.
- Incluir backups o instrucciones reproducibles antes de cambios relevantes en datos locales existentes.

## 8 Pruebas y aceptación

Ejecutar dentro de contenedores y conservar evidencia resumida:

| Grupo | Criterios mínimos |
| --- | --- |
| Docker | Build, salud, migraciones, proxy, persistencia tras reinicio y aislamiento de otros proyectos |
| Autenticación | Login correcto/incorrecto, logout, expiración, CSRF, rate limit y cuenta desactivada |
| Permisos | Editor no administra cuentas, no aprueba casos y no accede a evidencia privada |
| CRUD | Crear, modificar, listar, filtrar, publicar/retirar y persistir cada módulo del alcance |
| Publicación | Borradores inaccesibles públicamente; cambios publicados aparecen en web; conflictos no sobrescriben datos |
| Casos | Sin aprobación no se publica; cambios revocan aprobación; consentimiento privado inaccesible públicamente |
| Medios | Carga válida, archivo corrupto, tamaño excedido, extensión engañosa, traversal y control de eliminación |
| Seguridad de contenido | Texto y URLs maliciosas rechazados o renderizados sin ejecución |
| Navegador | Flujo completo editor a web, rutas de detalle, escritorio/móvil, teclado y ausencia de errores JavaScript |
| Compatibilidad | Pruebas de API ejecutadas realmente con PHP 8.2 del contenedor y MariaDB |

Las pruebas destructivas o con datos sintéticos usarán un entorno desechable separado. No confundir pruebas unitarias con validación end-to-end. Si existe CI, ampliarlo según las convenciones; si falta, preparar comprobaciones reproducibles para build, PHP y las pruebas esenciales, sin secretos reales.

## 9 Entregables y cierre

1. Código funcional, migraciones, configuración y documentación actualizada.
2. Reporte `docs/CB-CMS-002-VERIFICATION.md`: estado inicial, cambios, pruebas realmente ejecutadas, resultado por criterio, versiones, evidencia de persistencia, limitaciones y riesgos pendientes.
3. Comandos de arranque y uso en un único documento, sin transferir al usuario los pasos que Codex puede ejecutar.
4. Rama publicada y PR descriptivo con base correcta y dependencia del bloque anterior si corresponde. No fusionar.
5. Resumen final con enlace al PR, SHA, estado de CI, URLs locales, módulos funcionales y pendientes concretos. No exponer secretos de cuentas de prueba ni del usuario.

La revisión del usuario ocurre sobre ese resultado completo. No detenerse al terminar la maqueta, al arrancar Docker o al crear tablas. Si hay un bloqueo externo real, identificarlo con precisión y entregar todo lo independiente terminado, sin declarar aprobado lo que no se ejecutó.
