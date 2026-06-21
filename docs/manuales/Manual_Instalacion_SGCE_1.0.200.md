# Manual de instalación extendido - SGCE 1.0.200

**Guía completa para instalación limpia, validación y entrega**

Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

## Índice operativo

1. Portada y alcance de instalación
2. Mapa rápido del paquete
3. Requisitos mínimos de servidor
4. Requisitos recomendados para Plesk
5. Preparación de base de datos vacía
6. Subida segura de archivos
7. Validación previa del instalador
8. Captura de conexión MySQL
9. Datos institucionales
10. Oferta educativa inicial
11. Ciclo escolar inicial
12. Periodos automáticos
13. Planeaciones durante instalación
14. Administrador inicial
15. Ejecución de instalación
16. Archivos generados al terminar
17. Primer acceso administrativo
18. Checklist visual postinstalación
19. Creación de datos mínimos
20. Prueba mínima de asistencia
21. Prueba mínima de calificaciones
22. Prueba de reportes
23. Prueba de respaldo
24. Verificación de carpetas privadas
25. Uso de HTTPS
26. Permisos recomendados
27. OPcache y rendimiento inicial
28. Importaciones iniciales
29. Validación de maestros importados
30. Validación de alumnos importados
31. Validación de materias
32. Validación de asignaciones
33. Configuración posterior
34. Roles y usuarios
35. Sesiones PHP en Plesk
36. Respaldo antes de operación real
37. Migración no requerida en instalación limpia
38. Errores comunes del instalador
39. Reinstalación en pruebas
40. Restauración de respaldo
41. Cron y mantenimiento
42. Archivado de asistencias
43. Comprobación de versión
44. Entrega al cliente
45. Validación móvil
46. Prueba de consulta pública
47. Prueba de documentos descargables
48. Aceptación final
49. Plan de soporte básico
50. Glosario de instalación
51. Cierre del manual de instalación


## Hoja 1: Portada y alcance de instalación

Objetivo de esta hoja: explicar el alcance de instalación limpia para administradores técnicos y responsables de entrega, con enfoque en preparación sin tocar lógica de negocio. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el alcance de instalación limpia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar. Este manual sustituye a los manuales cortos anteriores como guía principal de instalación.

### Puntos de verificación

- Versión documentada: 1.0.200.
- Base funcional: hotfix seguridad tests previo.
- Objetivo: instalación desde cero y validación postinstalación.

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

## Hoja 2: Mapa rápido del paquete

Objetivo de esta hoja: explicar la estructura del paquete Produccion para quien sube archivos a Plesk o Apache, con enfoque en evitar carpetas fuera de lugar. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la estructura del paquete Produccion debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Subir el contenido de Produccion/ al public_html o subdominio.
- Mantener config/, includes/, install/, modules/, public/, reports/, storage/ y assets/.
- No subir herramientas externas ni carpetas tests/.

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

## Hoja 3: Requisitos mínimos de servidor

Objetivo de esta hoja: explicar los requisitos mínimos para personal técnico, con enfoque en compatibilidad PHP/MySQL. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, los requisitos mínimos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- PHP 8.1 o superior.
- MySQL/MariaDB compatible con utf8mb4.
- Extensiones: pdo, pdo_mysql, json, fileinfo, mbstring, openssl y zip.
- Permisos de escritura en storage/.

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

## Hoja 4: Requisitos recomendados para Plesk

Objetivo de esta hoja: explicar la preparación recomendada en Plesk para operadores de hosting, con enfoque en instalación estable. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la preparación recomendada en Plesk debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar PHP-FPM o FastCGI moderno.
- Activar OPcache.
- Crear base vacía antes de abrir Instalar.php.
- Verificar que .htaccess esté habilitado.

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

## Hoja 5: Preparación de base de datos vacía

Objetivo de esta hoja: explicar la creación de base de datos para administradores, con enfoque en instalación limpia. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la creación de base de datos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Crear base con collation utf8mb4_unicode_ci.
- Usar usuario con permisos sobre esa base.
- No mezclar tablas viejas con instalación nueva.
- Guardar host, usuario, contraseña y nombre de base.

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

## Hoja 6: Subida segura de archivos

Objetivo de esta hoja: explicar la carga de archivos al servidor para quien despliega, con enfoque en evitar exposición de código privado. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la carga de archivos al servidor debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Subir solo el contenido final de Produccion/.
- No subir zips dentro del sitio público.
- Confirmar que config/, includes/, services/ y repositories/ tengan .htaccess.
- No mover rutas internas.

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

## Hoja 7: Validación previa del instalador

Objetivo de esta hoja: explicar la primera pantalla de requisitos para administradores, con enfoque en detección temprana de errores. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la primera pantalla de requisitos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Abrir Instalar.php por HTTPS.
- Revisar extensiones PHP.
- Revisar permisos de storage/.
- Confirmar que install/SGCE.sql existe.

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

## Hoja 8: Captura de conexión MySQL

Objetivo de esta hoja: explicar la conexión con MySQL para administradores técnicos, con enfoque en evitar errores de credenciales. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la conexión con MySQL debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Host normalmente localhost en Plesk.
- Nombre de base exacto.
- Usuario y contraseña sin espacios.
- Probar credenciales desde panel si falla.

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

## Hoja 9: Datos institucionales

Objetivo de esta hoja: explicar los datos de escuela para administrativos, con enfoque en identidad institucional. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, los datos de escuela debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre de escuela.
- CCT.
- Director o responsable.
- Municipio, teléfono, correo y lema.
- Estos datos aparecen en reportes y encabezados.

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

## Hoja 10: Oferta educativa inicial

Objetivo de esta hoja: explicar la oferta educativa para administradores escolares, con enfoque en estructura académica. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la oferta educativa debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nivel educativo.
- Cantidad de años/semestres/etapas.
- Etiqueta visible de grado o periodo.
- Programa educativo inicial si aplica.

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

## Hoja 11: Ciclo escolar inicial

Objetivo de esta hoja: explicar el ciclo inicial para administradores, con enfoque en operación del calendario escolar. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el ciclo inicial debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre del ciclo.
- Fecha de inicio.
- Fecha de fin.
- Debe quedar marcado como activo al finalizar.

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

## Hoja 12: Periodos automáticos

Objetivo de esta hoja: explicar la creación automática de periodos para administrativos, con enfoque en calificaciones ordenadas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la creación automática de periodos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Definir cantidad de periodos.
- Revisar nombres generados.
- Confirmar fechas coherentes.
- Evitar periodos duplicados.

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

## Hoja 13: Planeaciones durante instalación

Objetivo de esta hoja: explicar la configuración inicial de planeaciones para administradores, con enfoque en uso docente posterior. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la configuración inicial de planeaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Tipo por ciclo o por periodo.
- Cantidad permitida entre 1 y 12.
- Formatos permitidos.
- La configuración puede revisarse después en Configuración.

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

## Hoja 14: Administrador inicial

Objetivo de esta hoja: explicar la creación del usuario administrador para responsable del sistema, con enfoque en control de acceso inicial. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la creación del usuario administrador debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar usuario claro y contraseña fuerte.
- Guardar credenciales fuera del servidor.
- No compartir usuario administrador entre varias personas.
- Crear usuarios nominales después de entrar.

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

## Hoja 15: Ejecución de instalación

Objetivo de esta hoja: explicar el proceso de instalación para técnicos, con enfoque en esperar confirmación real. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el proceso de instalación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- No recargar la página mientras instala.
- Esperar mensaje final.
- Si falla, leer el mensaje completo.
- No repetir sobre una base medio instalada sin limpiar.

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

## Hoja 16: Archivos generados al terminar

Objetivo de esta hoja: explicar los archivos posteriores a instalación para administradores técnicos, con enfoque en verificación de cierre. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, los archivos posteriores a instalación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- config/database.local.php.
- storage/install.lock.
- Carpetas storage listas.
- Instalador bloqueado para reinstalación accidental.

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

## Hoja 17: Primer acceso administrativo

Objetivo de esta hoja: explicar el primer inicio de sesión para administrador inicial, con enfoque en validación funcional. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el primer inicio de sesión debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Entrar al dominio principal.
- Usar credenciales creadas.
- Revisar Dashboard.
- Confirmar VERSION.txt 1.0.200.

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

## Hoja 18: Checklist visual postinstalación

Objetivo de esta hoja: explicar las revisiones visuales para administrativos, con enfoque en entrega al cliente. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, las revisiones visuales debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Dashboard carga sin errores.
- Menú visible según rol.
- Iconos y estilos cargan correctamente.
- Tablas y botones se ven consistentes en móvil.

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

## Hoja 19: Creación de datos mínimos

Objetivo de esta hoja: explicar el alta de datos iniciales para administradores, con enfoque en probar flujo completo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el alta de datos iniciales debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Crear maestro.
- Crear grupo.
- Crear materia.
- Crear alumno.
- Crear asignación.

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

## Hoja 20: Prueba mínima de asistencia

Objetivo de esta hoja: explicar la prueba de asistencia para maestro y administrador, con enfoque en confirmar módulo docente. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la prueba de asistencia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Entrar como maestro.
- Seleccionar asignación y fecha.
- Guardar pase de lista.
- Exportar o revisar reporte.

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

## Hoja 21: Prueba mínima de calificaciones

Objetivo de esta hoja: explicar la prueba de calificaciones para maestro, con enfoque en validar rangos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la prueba de calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Seleccionar grupo/asignación.
- Elegir periodo.
- Capturar calificación válida.
- Confirmar NC cuando no hay captura.

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

## Hoja 22: Prueba de reportes

Objetivo de esta hoja: explicar los reportes iniciales para administrador, con enfoque en evidencia de salida. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, los reportes iniciales debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Generar lista de alumnos.
- Generar asistencia.
- Generar calificaciones.
- Generar Kardex individual si hay datos suficientes.

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

## Hoja 23: Prueba de respaldo

Objetivo de esta hoja: explicar el respaldo SQL para administradores, con enfoque en recuperación ante incidentes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el respaldo SQL debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Generar respaldo desde módulo.
- Descargar archivo.
- Guardar copia fuera del servidor.
- Nunca dejar respaldos públicos.

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

## Hoja 24: Verificación de carpetas privadas

Objetivo de esta hoja: explicar el bloqueo de carpetas privadas para técnicos, con enfoque en seguridad Apache/Plesk. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el bloqueo de carpetas privadas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Intentar abrir config/ debe negar acceso.
- Intentar abrir includes/ debe negar acceso.
- storage/ no debe listar archivos.
- tests/ no debe existir en producción.

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

## Hoja 25: Uso de HTTPS

Objetivo de esta hoja: explicar el acceso por HTTPS para técnicos, con enfoque en cookies y sesiones seguras. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el acceso por HTTPS debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Instalar certificado SSL.
- Forzar HTTPS desde panel si aplica.
- Validar que cookies funcionen.
- Evitar mezclar http y https.

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

## Hoja 26: Permisos recomendados

Objetivo de esta hoja: explicar los permisos de archivos para administradores de servidor, con enfoque en mínimo privilegio. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, los permisos de archivos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Archivos PHP legibles por servidor.
- storage/ escribible por PHP.
- No dar 777 si no es necesario.
- Revisar propietario en Plesk.

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

## Hoja 27: OPcache y rendimiento inicial

Objetivo de esta hoja: explicar la configuración de OPcache para técnicos, con enfoque en respuesta rápida. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la configuración de OPcache debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Activar OPcache.
- Evitar modo debug en producción.
- Reiniciar PHP-FPM tras cambios de código.
- Medir tiempos desde navegador real.

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

## Hoja 28: Importaciones iniciales

Objetivo de esta hoja: explicar la carga masiva de datos para administrativos, con enfoque en evitar capturas manuales largas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la carga masiva de datos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar formatos requeridos.
- Revisar errores antes de confirmar.
- No subir archivos alterados manualmente sin validar.
- Conservar archivo fuente.

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

## Hoja 29: Validación de maestros importados

Objetivo de esta hoja: explicar la importación de docentes para administrativos, con enfoque en usuarios y asignaciones. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la importación de docentes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre completo.
- Usuario.
- Contraseña inicial.
- Verificar duplicados.

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

## Hoja 30: Validación de alumnos importados

Objetivo de esta hoja: explicar la importación de alumnos para control escolar, con enfoque en integridad académica. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la importación de alumnos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Grupo correcto.
- Turno correcto.
- Matrícula si aplica.
- Estado activo o inscripción correcta.

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

## Hoja 31: Validación de materias

Objetivo de esta hoja: explicar materias y horas para administradores académicos, con enfoque en evitar carga incorrecta. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, materias y horas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre consistente.
- Horas semanales coherentes.
- Grupo/ciclo correcto.
- Evitar duplicados por acentos o espacios.

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

## Hoja 32: Validación de asignaciones

Objetivo de esta hoja: explicar la unión maestro-grupo-materia para administradores, con enfoque en operación docente. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la unión maestro-grupo-materia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Maestro activo.
- Materia activa.
- Grupo activo.
- Horas dentro del máximo configurado.

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

## Hoja 33: Configuración posterior

Objetivo de esta hoja: explicar la revisión de Configuración para administrador, con enfoque en ajuste institucional. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la revisión de Configuración debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Datos de escuela.
- Escala de calificaciones.
- Planeaciones.
- URL base si aplica.

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

## Hoja 34: Roles y usuarios

Objetivo de esta hoja: explicar usuarios del sistema para administradores, con enfoque en seguridad operativa. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, usuarios del sistema debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Crear usuarios nominales.
- No reutilizar contraseña inicial.
- Desactivar usuarios que ya no laboran.
- Auditar accesos si hay dudas.

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

## Hoja 35: Sesiones PHP en Plesk

Objetivo de esta hoja: explicar sesiones PHP para técnicos, con enfoque en evitar errores de login. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, sesiones PHP debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Ruta de sesiones escribible.
- Cookie segura bajo HTTPS.
- Regeneración de sesión al entrar.
- Limpiar sesiones viejas si el servidor falla.

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

## Hoja 36: Respaldo antes de operación real

Objetivo de esta hoja: explicar el respaldo inicial para administradores, con enfoque en punto cero de recuperación. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el respaldo inicial debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Hacer respaldo después de importar catálogo base.
- Guardar copia externa.
- Etiquetar respaldo con fecha y versión.
- No depender solo del hosting.

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

## Hoja 37: Migración no requerida en instalación limpia

Objetivo de esta hoja: explicar la migración de ciclos para administradores, con enfoque en diferenciar instalación nueva de operación anual. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la migración de ciclos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- No se usa al instalar desde cero.
- Se usa al cerrar ciclo.
- Requiere respaldo previo.
- Debe leerse diagnóstico antes de ejecutar.

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

## Hoja 38: Errores comunes del instalador

Objetivo de esta hoja: explicar fallos frecuentes para técnicos, con enfoque en solución ordenada. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, fallos frecuentes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Falta pdo_mysql.
- Credenciales incorrectas.
- storage/ sin permisos.
- install/SGCE.sql ausente.
- Base no vacía o con tablas medias.

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

## Hoja 39: Reinstalación en pruebas

Objetivo de esta hoja: explicar reinstalar controladamente para técnicos, con enfoque en laboratorio seguro. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, reinstalar controladamente debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar base vacía.
- Eliminar install.lock solo en pruebas.
- Eliminar database.local.php solo en pruebas.
- Nunca reinstalar sobre producción sin respaldo.

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

## Hoja 40: Restauración de respaldo

Objetivo de esta hoja: explicar restaurar datos para administradores técnicos, con enfoque en recuperación controlada. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, restaurar datos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Probar primero en copia.
- Verificar compatibilidad de versión.
- No restaurar archivos dudosos.
- Revisar SET restringidos.

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

## Hoja 41: Cron y mantenimiento

Objetivo de esta hoja: explicar tareas programadas para técnicos, con enfoque en operación estable. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, tareas programadas debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- backup_diario.php.
- backup_semanal.php.
- mantenimiento_diario.php.
- archivar_asistencias.php.
- Configurar rutas absolutas en cron.

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

## Hoja 42: Archivado de asistencias

Objetivo de esta hoja: explicar AsistenciasArchivo para técnicos y control escolar, con enfoque en mantener rendimiento. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, AsistenciasArchivo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Ciclos cerrados pueden archivarse.
- Reportes siguen consultando histórico.
- No requiere particionado físico.
- Revisar logs de cron.

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

## Hoja 43: Comprobación de versión

Objetivo de esta hoja: explicar la versión instalada para administradores, con enfoque en distinguir hotfixes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la versión instalada debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- VERSION.txt debe decir 1.0.200.
- Version::CURRENT debe ser 1.0.200.
- README debe mencionar 1.0.200.
- No confundir con evidencia histórica de versiones anteriores.

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

## Hoja 44: Entrega al cliente

Objetivo de esta hoja: explicar la entrega final para responsable del proyecto, con enfoque en documentación y aceptación. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la entrega final debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Entregar URL.
- Entregar credenciales iniciales en canal seguro.
- Entregar manuales PDF/DOCX/MD.
- Entregar checklist firmado.

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

## Hoja 45: Validación móvil

Objetivo de esta hoja: explicar el uso desde celular para usuarios finales, con enfoque en operación cotidiana. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el uso desde celular debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Probar Dashboard.
- Probar Asistencia.
- Probar Calificaciones.
- Probar consultas públicas.
- Revisar botones y tablas.

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

## Hoja 46: Prueba de consulta pública

Objetivo de esta hoja: explicar consulta de asistencia/calificaciones para padres o alumnos, con enfoque en validación externa. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, consulta de asistencia/calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Capturar datos exactos.
- Confirmar resultados.
- Regresar al final de pantalla.
- No existe PIN familiar por alcance.

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

## Hoja 47: Prueba de documentos descargables

Objetivo de esta hoja: explicar exportaciones para administrativos, con enfoque en calidad documental. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, exportaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- PDF abre correctamente.
- Excel descarga correctamente.
- Encabezados institucionales aparecen.
- Nombres y acentos son legibles.

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

## Hoja 48: Aceptación final

Objetivo de esta hoja: explicar la aceptación de instalación para cliente final, con enfoque en cierre formal. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, la aceptación de instalación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Servidor cumple requisitos.
- Sistema entra sin errores.
- Datos mínimos probados.
- Respaldo generado.
- Manuales disponibles en docs/manuales/.

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

## Hoja 49: Plan de soporte básico

Objetivo de esta hoja: explicar soporte posterior para administrador interno, con enfoque en resolver dudas sin tocar código. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, soporte posterior debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Registrar incidencias.
- Revisar manual de usuario.
- Revisar logs si hay error técnico.
- Escalar solo con evidencia clara.

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

## Hoja 50: Glosario de instalación

Objetivo de esta hoja: explicar términos clave para usuarios no técnicos, con enfoque en entender mensajes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, términos clave debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Public_html: carpeta pública del dominio.
- OPcache: caché de PHP.
- Collation: regla de texto de MySQL.
- Lock: archivo que bloquea reinstalación.

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

## Hoja 51: Cierre del manual de instalación

Objetivo de esta hoja: explicar el cierre operativo para quien entrega SGCE, con enfoque en instalación demostrable. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, el cierre operativo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Instalar desde cero.
- Validar módulos básicos.
- Guardar evidencia.
- Mantener manuales 1.0.200 como referencia oficial.

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
