# Manual técnico extendido - SGCE 1.0.200

**Arquitectura, seguridad, mantenimiento, soporte y decisiones técnicas**

Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

## Índice operativo

1. Portada técnica y alcance
2. Arquitectura general
3. Puntos de entrada
4. Capa config
5. Capa includes
6. Capa services
7. Capa repositories
8. Capa modules
9. Capa views
10. Namespace Sgce
11. Autoload
12. Versionado
13. Seguridad de sesión
14. CSRF general
15. CSRF instalador en tres capas
16. Protección de carpetas
17. Validación de archivos
18. Importación XLSX
19. SQL y prepared statements
20. Escapado de vistas
21. Base de datos principal
22. Calificaciones
23. Asistencias
24. AsistenciasArchivo
25. Decisión de no particionar
26. Índices de rendimiento
27. Kardex sin N+1
28. Caché de ciclo activo
29. Caché de oferta activa
30. Migración de ciclos
31. Cron de mantenimiento
32. Respaldos
33. Restauración
34. Logs y bitácora
35. PDF y exportadores
36. Frontend CSS
37. JavaScript
38. Instalador
39. Compatibilidad Apache/Plesk
40. Compatibilidad Nginx
41. Pruebas disponibles
42. Limitación MySQL del entorno de construcción
43. Despliegue
44. Actualizaciones futuras
45. Código muerto
46. DRY y separación
47. Seguridad operativa
48. Rendimiento esperado
49. Diagnóstico técnico
50. Glosario técnico
51. Cierre técnico


## Hoja 1: Portada técnica y alcance

Objetivo de esta hoja: explicar portada técnica y alcance para equipo técnico de SGCE, con enfoque en delimitar la versión documental. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, portada técnica y alcance debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- SGCE 1.0.200.
- Sin cambios funcionales frente al hotfix previo.
- Documentación ampliada y README actualizado.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 2: Arquitectura general

Objetivo de esta hoja: explicar arquitectura general para equipo técnico de SGCE, con enfoque en entender carpetas productivas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, arquitectura general debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- config/
- includes/
- services/
- repositories/
- modules/
- views/
- reports/
- storage/
- src/

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 3: Puntos de entrada

Objetivo de esta hoja: explicar puntos de entrada para equipo técnico de SGCE, con enfoque en controlar acceso web. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, puntos de entrada debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- index.php.
- Admin.php.
- Maestro.php.
- Instalar.php.
- Exportadores públicos controlados.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 4: Capa config

Objetivo de esta hoja: explicar capa config para equipo técnico de SGCE, con enfoque en centralizar conexión y configuración. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa config debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- database.php.
- database.local.php.
- Conexion.php.
- Archivos protegidos por .htaccess.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 5: Capa includes

Objetivo de esta hoja: explicar capa includes para equipo técnico de SGCE, con enfoque en helpers transversales. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa includes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Seguridad.
- Layout.
- Académico.
- PDF.
- Mantenimiento.
- Importación.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 6: Capa services

Objetivo de esta hoja: explicar capa services para equipo técnico de SGCE, con enfoque en lógica de negocio. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa services debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- AlumnoService.
- AsistenciaService.
- CalificacionService.
- GrupoService.
- ReporteService.
- MigracionService.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 7: Capa repositories

Objetivo de esta hoja: explicar capa repositories para equipo técnico de SGCE, con enfoque en consultas reutilizables. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa repositories debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Paginación.
- Filtros.
- Consultas agrupadas.
- Separación de vista y datos.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 8: Capa modules

Objetivo de esta hoja: explicar capa modules para equipo técnico de SGCE, con enfoque en controladores de pantalla. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa modules debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Admin.
- Asistencia.
- Calificar.
- Configuración.
- Migración.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 9: Capa views

Objetivo de esta hoja: explicar capa views para equipo técnico de SGCE, con enfoque en plantillas de interfaz. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, capa views debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Parciales.
- Tablas.
- Modales.
- Paginadores.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 10: Namespace Sgce

Objetivo de esta hoja: explicar namespace sgce para equipo técnico de SGCE, con enfoque en migración gradual a clases. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, namespace sgce debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Sgce\Foundation\Version.
- Sgce\Foundation\Path.
- Sgce\Support\Text.
- Sgce\Support\Search.
- Sgce\Support\AcademicCalculator.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 11: Autoload

Objetivo de esta hoja: explicar autoload para equipo técnico de SGCE, con enfoque en cargar clases sin acoplar. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, autoload debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- app/autoload.php.
- PSR-4 simple.
- Compatibilidad con código procedural.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 12: Versionado

Objetivo de esta hoja: explicar versionado para equipo técnico de SGCE, con enfoque en identificar instalación. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, versionado debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Version::CURRENT.
- VERSION.txt.
- README.
- Historial documental.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 13: Seguridad de sesión

Objetivo de esta hoja: explicar seguridad de sesión para equipo técnico de SGCE, con enfoque en proteger login. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, seguridad de sesión debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Regeneración de sesión.
- Cookies seguras.
- Validación de rol.
- Cierre de sesión.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 14: CSRF general

Objetivo de esta hoja: explicar csrf general para equipo técnico de SGCE, con enfoque en proteger formularios. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, csrf general debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Token por sesión.
- Verificación en POST.
- Mensajes de error.
- No omitir en acciones administrativas.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 15: CSRF instalador en tres capas

Objetivo de esta hoja: explicar csrf instalador en tres capas para equipo técnico de SGCE, con enfoque en documentar respaldo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, csrf instalador en tres capas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Sesión PHP.
- Cookie temporal.
- Archivo hash en storage/locks/.
- Caducidad y restauración de sesión.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 16: Protección de carpetas

Objetivo de esta hoja: explicar protección de carpetas para equipo técnico de SGCE, con enfoque en evitar exposición. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, protección de carpetas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- .htaccess en carpetas internas.
- index.html silencioso.
- Bloqueo tests/tools/scripts/dev.
- Nginx complementario.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 17: Validación de archivos

Objetivo de esta hoja: explicar validación de archivos para equipo técnico de SGCE, con enfoque en evitar cargas peligrosas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, validación de archivos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Firma binaria.
- Extensión esperada.
- Tamaño.
- Directorio privado.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 18: Importación XLSX

Objetivo de esta hoja: explicar importación xlsx para equipo técnico de SGCE, con enfoque en seguridad y consistencia. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, importación xlsx debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- fileinfo.
- zip.
- Vista previa.
- Errores por fila.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 19: SQL y prepared statements

Objetivo de esta hoja: explicar sql y prepared statements para equipo técnico de SGCE, con enfoque en evitar inyección. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, sql y prepared statements debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- PDO.
- Parámetros.
- Consultas agrupadas.
- No concatenar entrada cruda.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 20: Escapado de vistas

Objetivo de esta hoja: explicar escapado de vistas para equipo técnico de SGCE, con enfoque en evitar XSS. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, escapado de vistas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- htmlspecialchars.
- Helpers de texto.
- No imprimir datos crudos.
- Atributos seguros.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 21: Base de datos principal

Objetivo de esta hoja: explicar base de datos principal para equipo técnico de SGCE, con enfoque en conocer tablas base. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, base de datos principal debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- CiclosEscolares.
- OfertasEducativas.
- Grupos.
- Materias.
- Alumnos.
- Asignaciones.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 22: Calificaciones

Objetivo de esta hoja: explicar calificaciones para equipo técnico de SGCE, con enfoque en modelo académico. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Periodos.
- Asignaciones.
- Calificación decimal.
- NC cuando no existe registro.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 23: Asistencias

Objetivo de esta hoja: explicar asistencias para equipo técnico de SGCE, con enfoque en modelo operativo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, asistencias debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Asistencias activa.
- AsistenciasDetalle si aplica.
- Fechas.
- Estados de asistencia.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 24: AsistenciasArchivo

Objetivo de esta hoja: explicar asistenciasarchivo para equipo técnico de SGCE, con enfoque en archivado histórico. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, asistenciasarchivo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Tabla histórica.
- Ciclos cerrados.
- Consultas por ciclo.
- Reportes compatibles.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 25: Decisión de no particionar

Objetivo de esta hoja: explicar decisión de no particionar para equipo técnico de SGCE, con enfoque en justificar arquitectura de datos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, decisión de no particionar debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Compatibilidad Plesk/MySQL.
- Menos riesgo en respaldos.
- Integridad referencial simple.
- Archivado + índices como alternativa.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 26: Índices de rendimiento

Objetivo de esta hoja: explicar índices de rendimiento para equipo técnico de SGCE, con enfoque en mantener velocidad. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, índices de rendimiento debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- CicloId.
- GrupoId.
- AlumnoId.
- AsignacionId.
- PeriodoId.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 27: Kardex sin N+1

Objetivo de esta hoja: explicar kardex sin n+1 para equipo técnico de SGCE, con enfoque en performance reportes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, kardex sin n+1 debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Alumno.
- Ciclos.
- Periodos.
- Asignaciones.
- Calificaciones por IN.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 28: Caché de ciclo activo

Objetivo de esta hoja: explicar caché de ciclo activo para equipo técnico de SGCE, con enfoque en evitar consultas repetidas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, caché de ciclo activo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- SgceCicloActivo().
- Contador global.
- Invalidación tras UPDATE.
- Lectura fresca posterior.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 29: Caché de oferta activa

Objetivo de esta hoja: explicar caché de oferta activa para equipo técnico de SGCE, con enfoque en consistencia académica. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, caché de oferta activa debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- SgceOfertaActiva().
- Invalidación al activar.
- Uso en configuración y periodos.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 30: Migración de ciclos

Objetivo de esta hoja: explicar migración de ciclos para equipo técnico de SGCE, con enfoque en proceso académico complejo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, migración de ciclos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Diagnóstico.
- Promoción.
- Egreso.
- Copias opcionales.
- Respaldo previo.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 31: Cron de mantenimiento

Objetivo de esta hoja: explicar cron de mantenimiento para equipo técnico de SGCE, con enfoque en tareas automáticas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, cron de mantenimiento debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- backup_diario.
- backup_semanal.
- mantenimiento_diario.
- archivar_asistencias.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 32: Respaldos

Objetivo de esta hoja: explicar respaldos para equipo técnico de SGCE, con enfoque en recuperación. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, respaldos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- SQL generado.
- SET restringidos.
- Carpeta storage/backups.
- Descarga controlada.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 33: Restauración

Objetivo de esta hoja: explicar restauración para equipo técnico de SGCE, con enfoque en riesgos controlados. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, restauración debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Validar archivo.
- Probar en copia.
- No restaurar desconocidos.
- Revisar compatibilidad.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 34: Logs y bitácora

Objetivo de esta hoja: explicar logs y bitácora para equipo técnico de SGCE, con enfoque en trazabilidad. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, logs y bitácora debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Acciones.
- Usuario.
- Fecha.
- Filtros.
- Archivado si crece.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 35: PDF y exportadores

Objetivo de esta hoja: explicar pdf y exportadores para equipo técnico de SGCE, con enfoque en salidas oficiales. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, pdf y exportadores debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- SGCE_Pdf.
- Encabezados.
- Kardex.
- Boletas.
- Asistencia.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 36: Frontend CSS

Objetivo de esta hoja: explicar frontend css para equipo técnico de SGCE, con enfoque en organización visual. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, frontend css debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- assets/css.
- Responsividad.
- Componentes institucionales.
- No tocar diseño en esta versión.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 37: JavaScript

Objetivo de esta hoja: explicar javascript para equipo técnico de SGCE, con enfoque en interacciones. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, javascript debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Confirmaciones.
- Modales.
- Filtros.
- Búsqueda en selects.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 38: Instalador

Objetivo de esta hoja: explicar instalador para equipo técnico de SGCE, con enfoque en flujo técnico. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, instalador debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Requisitos.
- Base.
- Datos escuela.
- Usuario admin.
- Lock final.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 39: Compatibilidad Apache/Plesk

Objetivo de esta hoja: explicar compatibilidad apache/plesk para equipo técnico de SGCE, con enfoque en producción real. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, compatibilidad apache/plesk debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- .htaccess.
- PHP handler.
- Sesiones.
- Permisos.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 40: Compatibilidad Nginx

Objetivo de esta hoja: explicar compatibilidad nginx para equipo técnico de SGCE, con enfoque en defensa adicional. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, compatibilidad nginx debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Plantilla docs/nginx.
- Bloqueo storage.
- No reemplaza .htaccess en Apache.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 41: Pruebas disponibles

Objetivo de esta hoja: explicar pruebas disponibles para equipo técnico de SGCE, con enfoque en evidencia histórica. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, pruebas disponibles debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- php -l.
- Fallback PHP histórico.
- Kardex SQLite.
- Logs en historial.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 42: Limitación MySQL del entorno de construcción

Objetivo de esta hoja: explicar limitación mysql del entorno de construcción para equipo técnico de SGCE, con enfoque en transparencia. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, limitación mysql del entorno de construcción debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Sin pdo_mysql local.
- Sin mysqld.
- Checklist en servidor real.
- No inventar resultados.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 43: Despliegue

Objetivo de esta hoja: explicar despliegue para equipo técnico de SGCE, con enfoque en pasar a producción. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, despliegue debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Subir Produccion/.
- Configurar base.
- Instalar.
- Validar.
- Respaldar.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 44: Actualizaciones futuras

Objetivo de esta hoja: explicar actualizaciones futuras para equipo técnico de SGCE, con enfoque en mantener orden. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, actualizaciones futuras debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- No mezclar versiones.
- Actualizar VERSION.txt.
- Documentar changelog.
- Probar antes de subir.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 45: Código muerto

Objetivo de esta hoja: explicar código muerto para equipo técnico de SGCE, con enfoque en mantenibilidad. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, código muerto debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Funciones Sgce con llamador.
- Clases Sgce referenciadas.
- No dejar wrappers sin uso.
- Conservar justificaciones.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 46: DRY y separación

Objetivo de esta hoja: explicar dry y separación para equipo técnico de SGCE, con enfoque en mantener sistema limpio. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, dry y separación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Servicios para lógica.
- Repositorios para datos.
- Vistas para presentación.
- Helpers compartidos.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 47: Seguridad operativa

Objetivo de esta hoja: explicar seguridad operativa para equipo técnico de SGCE, con enfoque en recomendaciones. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, seguridad operativa debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- HTTPS.
- Contraseñas.
- Backups fuera del servidor.
- No exponer herramientas.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 48: Rendimiento esperado

Objetivo de esta hoja: explicar rendimiento esperado para equipo técnico de SGCE, con enfoque en operación escolar. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, rendimiento esperado debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Consultas paginadas.
- Búsquedas servidor.
- Kardex agrupado.
- Archivado de asistencias.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 49: Diagnóstico técnico

Objetivo de esta hoja: explicar diagnóstico técnico para equipo técnico de SGCE, con enfoque en soporte. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, diagnóstico técnico debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Leer error.
- Revisar logs.
- Validar permisos.
- Comparar versión.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 50: Glosario técnico

Objetivo de esta hoja: explicar glosario técnico para equipo técnico de SGCE, con enfoque en términos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, glosario técnico debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- N+1.
- CSRF.
- OPcache.
- Archivado.
- Namespace.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---

## Hoja 51: Cierre técnico

Objetivo de esta hoja: explicar cierre técnico para equipo técnico de SGCE, con enfoque en operación sostenible. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, cierre técnico debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Cuando se requiera modificar código, debe abrirse una versión posterior y probarse por separado; este manual no autoriza cambios directos en producción.

### Puntos de verificación

- Código estable.
- Documentación extensa.
- Historial preservado.
- Sin cambios funcionales en 1.0.200.

### Procedimiento recomendado

1. Leer la pantalla completa antes de capturar o confirmar.
1. Validar ciclo, grupo, periodo u oferta activa cuando el módulo dependa de estructura académica.
1. Guardar evidencia del resultado si la acción afecta datos masivos o reportes oficiales.
1. Consultar al administrador antes de repetir una acción que genere datos duplicados.

### Errores que deben evitarse

- Capturar datos de prueba en producción sin autorización.
- Borrar registros históricos cuando basta con desactivar o dar de baja.
- Cambiar la base de datos manualmente para saltarse validaciones del sistema.
- Omitir respaldo antes de procesos masivos o cierres de ciclo.

---
