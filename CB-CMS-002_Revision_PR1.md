# CB CMS 002 Revisión del PR 1 y corrección de contraseña

## Encargo para Codex

Trabaja en `C:\DEV\carlosburgos\.worktrees\CB-CMS-002`, rama `feature/CB-CMS-002`, repositorio `https://github.com/abmave/carlosburgos-web.git`. Lee este documento y corrige el hallazgo confirmado sobre el PR existente `https://github.com/abmave/carlosburgos-web/pull/1`. Conserva cambios ajenos y datos del entorno. No crear otro bloque funcional, no fusionar ramas y no desplegar al hosting. Ejecuta las pruebas y entrega el mismo PR actualizado para revisión.

## Base revisada

SHA examinado: `c648995c807a5be5f0ba03ea22b8840e9688d98d`.

La revisión confirmó mediante GitHub que el PR está abierto hacia `feature/CB-FOUNDATION-001`, sin merge, y que el workflow CMS editorial terminó en success en la ejecución `38818208593` para ese SHA. Se leyeron el código y los documentos de verificación/operación. No se accedió al Docker Desktop ni al archivo privado de activación del usuario.

## Hallazgo P2 Contraseña alterada durante activación o restablecimiento

Ubicación: `api/src/editorial.php`, función `userRoutes`, rama `/api/admin/reset-password`, aproximadamente líneas 168–171 del SHA revisado. La contraseña se obtiene con `textField`, función que devuelve `trim($v)`; el login, en `api/public/index.php`, verifica el valor sin ese recorte.

Consecuencia: el sistema anuncia que la contraseña se actualizó, pero no conserva exactamente lo que escribió la persona. Tanto la activación inicial como un restablecimiento pueden terminar en un acceso rechazado y consumir innecesariamente el enlace de un solo uso.

Reproducción independiente en base MariaDB desechable con PHP 8.3:

1. Obtener un enlace válido y una sesión CSRF.
2. Definir una contraseña de prueba con dos espacios iniciales y dos finales, por ejemplo `  Correct Password 123  `.
3. El endpoint de restablecimiento responde 200.
4. El login con la contraseña exacta responde 401.
5. El login con la versión recortada responde 200.

El ejemplo es exclusivamente de prueba; no es una credencial del usuario ni debe convertirse en un seed. Esta reproducción no sustituye la validación de la corrección con PHP 8.2 en Docker.

## Corrección requerida

- No procesar contraseñas con una función que normalice texto editorial.
- Validar tipo, límite de 12–72 bytes y valores que bcrypt no pueda aceptar, sin alterar silenciosamente el secreto. Preservar los espacios tal como se hace en el login.
- Si se optara por rechazar ciertos caracteres por política, devolver validación explícita antes de actualizar nada; nunca aceptar una contraseña y almacenar otra.
- Verificar que una entrada inválida no consuma el enlace ni modifique el hash o la versión de sesión.
- Conservar hash seguro, transacción, expiración, uso único del token y revocación de sesiones existentes.
- No imprimir contraseñas ni tokens en logs, reportes o Git.

## Pruebas de aceptación

Añadir pruebas de regresión de API para:

1. Activación/restablecimiento con espacios iniciales y finales: login con el valor exacto exitoso; versión recortada rechazada.
2. Contraseña habitual sin espacios y contraseña UTF-8 dentro del límite de bytes.
3. Valores no string, longitud menor a 12 bytes, mayor a 72 bytes y NUL: validación controlada, sin respuesta 500/503 ni consumo del token.
4. Token inválido, vencido y reutilizado; mantener el comportamiento correcto.
5. Cambio de contraseña invalida sesiones previas.

Ejecutar la suite existente dentro del entorno Docker desechable, con PHP 8.2, incluyendo API y navegador cuando corresponda. No borrar volúmenes ni modificar la cuenta real del administrador. Regenerar invitación local solo si el titular la solicita; el archivo privado anterior puede haber vencido y no es una evidencia de acceso fallido por este bug.

## Cierre

Actualizar `docs/CB-CMS-002-VERIFICATION.md` con el hallazgo, la corrección y los resultados reales. Publicar los cambios en la misma rama, esperar CI del nuevo SHA y entregar enlace al PR, SHA completo y resumen de pruebas. No declarar cerrado el hallazgo solo porque pase la CI anterior. No hacer merge ni avanzar al siguiente módulo en este encargo.
