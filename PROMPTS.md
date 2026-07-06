# PROMPTS.md — HotelFlow

> Plantillas de prompts para trabajar este repositorio con IA.
> Adapta los bloques `[Contexto]` y `[Tarea]` en cada sesion.
> `CLAUDE.md` debe estar cargado como contexto base.

---

## Como usar este archivo

Cada plantilla usa 5 ejes:

| Eje                   | Pregunta                | Objetivo                                 |
| --------------------- | ----------------------- | ---------------------------------------- |
| **Rol**               | Quien eres?             | Define especialidad y nivel tecnico      |
| **Contexto**          | Donde estamos?          | Aterriza stack, modulo y archivos reales |
| **Tarea exacta**      | Que hay que hacer?      | Evita respuestas genericas               |
| **Restricciones**     | Que no se puede romper? | Protege convenciones del proyecto        |
| **Formato de salida** | Como lo quiero?         | Estandariza entregables                  |

**Reglas de uso del equipo**

- Cargar `CLAUDE.md` al inicio si la herramienta no lo hace sola.
- Un prompt por subtarea (feature, bug, review, etc.).
- Si el resultado falla, ajustar restricciones y volver a ejecutar.
- Definir criterios de aceptacion antes de pedir implementacion.
- Guardar aqui los prompts que mejor funcionen.

---

## Plantilla base (copiar y completar)

```txt
[Rol]
Actua como desarrollador PHP Senior especializado en MVC sin framework.

[Contexto]
Proyecto: HotelFlow (PHP 8.2+, MariaDB 10.4+).
Arquitectura: MVC custom con require/include, sin Composer ni framework.
Stack frontend: AdminLTE 3, Bootstrap 4, jQuery 3, DataTables, SweetAlert2, Select2.
Modulo activo: _______________

[Tarea]
_______________

[Restricciones]
- Respetar estructura existente: controllers/, models/, views/, public/js/modules/, public/css/modules/
- Modelos con PDO + prepared statements y Conexion::getInstance()->getConnection()
- Sesiones y auth segun views/layouts/session.php (requireLogin, requireRole, CSRF helpers)
- CSRF obligatorio (no opcional) en formularios y endpoints POST/acciones GET destructivas: generateCSRFToken(), verifyCSRFToken(), regenerateCSRFToken()
- Verificacion de permisos por modulo en cada endpoint (AuthorizationService::esAdministrador / puedeAccederModulo), ademas de requireLogin()
- Nunca confiar en valores del cliente para estado o dinero: recalcular estado/totales desde la BD antes de persistir
- Sanitizar y validar entradas siguiendo el patron del modulo de referencia
- Uploads con ImagenService (services/ImagenService.php), validando el tipo real del archivo (contenido), no el MIME/nombre enviado por el cliente
- Mantener mensajes de feedback via $_SESSION['mensaje'] y $_SESSION['icono']
- No exponer mensajes de excepcion crudos al usuario; loguear y devolver mensaje generico
- No agregar nuevas librerias ni cambiar arquitectura base sin justificacion explicita

[Formato de salida]
_______________
```

---

## Plantilla 1 — Implementar feature

```txt
[Rol]
Actua como desarrollador PHP Senior enfocado en PHP MVC, SQL y seguridad web.

[Contexto]
Proyecto: HotelFlow.
Modulo activo: [ejemplo: habitaciones, recepcion, ventas, usuarios].

Archivos habituales del modulo:
- controllers/[modulo]/[Modulo]Controller.php
- controllers/[modulo]/[acciones_ajax_o_post].php
- models/[Entidad].php
- views/[modulo]/index.php, create.php, update.php, show.php
- public/js/modules/[modulo]/*.js

[Tarea]
Implementar: [nombre exacto del requerimiento]

Criterios de aceptacion:
[lista concreta]

[Restricciones]
- Seguir convenciones del modulo existente (nombres, flujo y estructura de archivos)
- No mover logica de negocio fuera del patron actual del proyecto
- Todo acceso a BD con prepared statements
- Aplicar CSRF en POST y validacion de sesion donde corresponda
- Mantener compatibilidad con roles: Administrador, Recepcionista, Limpieza
- Si hay SQL nuevo, que sea compatible con database/db_hotel_flow.sql
- Mantener mensajes y respuestas consistentes con el modulo (session flash o JSON)

[Formato de salida]
1. Archivos a crear/modificar
2. Codigo final por archivo
3. SQL (si aplica)
4. Checklist de pruebas manuales (happy path + edge cases)
```

---

## Plantilla 2 — Debug de error

```txt
[Rol]
Actua como ingeniero Senior de debugging para PHP MVC + MariaDB.

[Contexto]
Proyecto: HotelFlow.
Archivo afectado: [ruta]
Metodo/flujo afectado: [nombre]

[Tarea]
Error observado:
[mensaje exacto o comportamiento]

Codigo relevante:
[bloque minimo necesario]

Comportamiento esperado:
[resultado esperado]

[Restricciones]
- Corregir causa raiz, no solo el sintoma
- Cambios minimos y seguros, sin refactor masivo
- Mantener compatibilidad con flujo de sesion, permisos y CSRF del proyecto
- Si se toca SQL, respetar tablas y nombres reales del esquema

[Formato de salida]
1. Diagnostico (causa raiz)
2. Fix propuesto (codigo)
3. Riesgos/impacto del cambio
```

---

## Plantilla 3 — Code review antes de merge

```txt
[Rol]
Actua como Tech Lead PHP especializado en revision de codigo productivo.

[Contexto]
Proyecto: HotelFlow.
Rama: [feature/...]
Cambio: [descripcion breve]

[Tarea]
Revisar este diff/codigo:
[pegar diff o archivos]

[Restricciones]
Enfocar revision en:
- Seguridad: SQL injection, XSS, CSRF, validacion de sesion y permisos
- Coherencia MVC: controllers/models/views segun estructura real
- Calidad de datos: validaciones, tipos y manejo de errores
- Integridad funcional: no romper flujos existentes (ventas, recepcion, habitaciones, usuarios)
- Frontend: compatibilidad con DataTables, Select2 y flujo de alertas existente

[Formato de salida]
OK  - Hallazgos correctos y fuertes
OBS - Mejoras recomendadas (no bloqueantes)
FIX - Errores bloqueantes a corregir antes de merge
```

---

## Plantilla 4 — Decision de arquitectura

```txt
[Rol]
Actua como arquitecto de software PHP para sistemas MVC legacy-friendly.

[Contexto]
Proyecto: HotelFlow.
Punto de entrada: index.php (front controller).
Servicios actuales: AuthorizationService, ImagenService, literal.php.
Persistencia: PDO singleton via config/conexion.php.

[Tarea]
Necesito decidir:
[decision tecnica]

Opciones:
- Opcion A: [descripcion]
- Opcion B: [descripcion]

[Restricciones]
- No introducir framework (Laravel, Symfony, etc.)
- No introducir herramientas de build o testing inexistentes salvo justificacion fuerte
- Mantener flujo actual con require/include y estructura de carpetas existente
- Solucion mantenible por equipo junior/intermedio

[Formato de salida]
1. Recomendacion directa
2. Trade-offs de A vs B
3. Impacto tecnico (archivos y modulos afectados)
4. Plan corto de implementacion por fases
```

---

## Plantilla 5 — Nuevo modulo completo (spec-first)

```txt
[Rol]
Actua como desarrollador PHP Senior especializado en diseno de modulos MVC.

[Contexto]
Proyecto: HotelFlow.
Modulo nuevo: [nombre]
Referencia de patron: controllers/[modulo_existente], models/[Entidad], views/[modulo], public/js/modules/[modulo]

[Tarea]
Disenar e implementar modulo [nombre] con:
[CRUD, reportes, AJAX, permisos, etc.]

Criterios de aceptacion:
[lista]

[Restricciones]
- Seguir estructura del proyecto sin introducir capas nuevas
- Definir controlador, modelo, vistas y JS del modulo
- Aplicar validaciones, CSRF y permisos por rol/modulo
- Reutilizar componentes existentes (layouts, utilidades JS, estilos)
- Si se altera BD, incluir migracion SQL compatible con database/db_hotel_flow.sql

[Formato de salida]
1. Especificacion funcional corta
2. Estructura de archivos
3. SQL requerido
4. Codigo por archivo
5. Plan de pruebas manuales
```

---

## Plantilla 6 — Preparar cambios para release open source

```txt
[Rol]
Actua como maintainer open source y desarrollador PHP Senior.

[Contexto]
Proyecto: HotelFlow.
Objetivo: publicar version abierta y util como portafolio tecnico.

[Tarea]
Preparar el proyecto para publicacion open source:
[describe alcance exacto]

[Restricciones]
- No exponer secretos, datos reales ni credenciales
- Mantener .env fuera de control de versiones y documentar .env.example
- Documentar instalacion local, requisitos y flujo de uso minimo
- Priorizar claridad del README, licencia y guia de contribucion
- No eliminar funcionalidad core existente sin aprobacion

[Formato de salida]
1. Lista priorizada de cambios (seguridad, docs, estructura)
2. Archivos a editar
3. Parches de contenido propuestos
4. Checklist final antes de hacer publico el repositorio
```

---

_Ultima actualizacion: 2026-07-06_
_Mantener sincronizado con CLAUDE.md cuando cambie arquitectura o flujo._
