# Manual de usuario extendido - SGCE 1.0.200

**Ayuda operativa para administrador, administrativo y maestro**

Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

## Índice operativo

1. Portada y propósito del manual
2. Conceptos básicos de SGCE
3. Roles del sistema
4. Inicio de sesión
5. Pantalla principal
6. Orden recomendado de trabajo
7. Módulo Maestros
8. Usuarios docentes
9. Módulo Grupos
10. Módulo Materias
11. Módulo Alumnos
12. Bajas e historial
13. Asignaciones
14. Portal del maestro
15. Pase de lista
16. Corrección de asistencia
17. Consulta de asistencia
18. Calificaciones
19. Escala de calificación
20. Reportes de calificaciones
21. Kardex individual
22. Conducta
23. Planeaciones docentes
24. Revisión de planeaciones
25. Avisos
26. Periodos
27. Configuración
28. Importar alumnos
29. Importar docentes
30. Importar grupos
31. Importar materias
32. Errores de importación
33. Exportaciones PDF
34. Exportaciones Excel
35. Respaldos
36. Restaurar respaldo
37. Bitácora
38. Migración de ciclo
39. Migrar grupo
40. Migrar ciclo completo
41. Consulta pública
42. Consulta pública de asistencia
43. Consulta pública de calificaciones
44. Uso en celular
45. Buenas prácticas de captura
46. Errores comunes de usuario
47. Seguridad para usuarios
48. Trabajo diario del administrador
49. Trabajo diario del maestro
50. Trabajo de control escolar
51. Cierre de periodo
52. Cierre de ciclo
53. Glosario de usuario
54. Cierre del manual de usuario


## Hoja 1: Portada y propósito del manual

Objetivo de esta hoja: explicar portada y propósito del manual para usuarios finales de SGCE, con enfoque en presentar SGCE al usuario final. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, portada y propósito del manual debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Manual para Administrador, Administrativo y Maestro.
- Versión 1.0.200.
- Ayuda diaria para operación escolar.

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

## Hoja 2: Conceptos básicos de SGCE

Objetivo de esta hoja: explicar conceptos básicos de sgce para usuarios finales de SGCE, con enfoque en entender ciclos, ofertas, grupos, materias y alumnos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, conceptos básicos de sgce debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Ciclo escolar activo.
- Oferta educativa activa.
- Grupo y turno.
- Materia y asignación.

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

## Hoja 3: Roles del sistema

Objetivo de esta hoja: explicar roles del sistema para usuarios finales de SGCE, con enfoque en saber qué puede hacer cada perfil. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, roles del sistema debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Administrador: configura y controla.
- Administrativo: captura y consulta según permisos.
- Maestro: asistencia, calificaciones y planeaciones.

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

## Hoja 4: Inicio de sesión

Objetivo de esta hoja: explicar inicio de sesión para usuarios finales de SGCE, con enfoque en entrar correctamente al sistema. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, inicio de sesión debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar usuario y contraseña.
- No hay 2FA por alcance.
- Cerrar sesión al terminar.
- No compartir credenciales.

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

## Hoja 5: Pantalla principal

Objetivo de esta hoja: explicar pantalla principal para usuarios finales de SGCE, con enfoque en leer el Dashboard. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, pantalla principal debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Totales institucionales.
- Accesos rápidos.
- Alertas y tarjetas.
- Menú lateral o superior según dispositivo.

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

## Hoja 6: Orden recomendado de trabajo

Objetivo de esta hoja: explicar orden recomendado de trabajo para usuarios finales de SGCE, con enfoque en capturar sin errores. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, orden recomendado de trabajo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Maestros.
- Grupos.
- Materias.
- Alumnos.
- Asignaciones.
- Asistencia y calificaciones.

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

## Hoja 7: Módulo Maestros

Objetivo de esta hoja: explicar módulo maestros para usuarios finales de SGCE, con enfoque en registrar docentes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, módulo maestros debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Alta manual.
- Edición.
- Desactivación.
- Evitar duplicados.

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

## Hoja 8: Usuarios docentes

Objetivo de esta hoja: explicar usuarios docentes para usuarios finales de SGCE, con enfoque en diferenciar maestro de usuario. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, usuarios docentes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- El maestro es dato académico.
- El usuario permite iniciar sesión.
- Ambos deben estar relacionados correctamente.

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

## Hoja 9: Módulo Grupos

Objetivo de esta hoja: explicar módulo grupos para usuarios finales de SGCE, con enfoque en administrar grupos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, módulo grupos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Grado o etapa.
- Grupo.
- Turno.
- Ciclo.
- Programa educativo.

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

## Hoja 10: Módulo Materias

Objetivo de esta hoja: explicar módulo materias para usuarios finales de SGCE, con enfoque en registrar materias. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, módulo materias debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre claro.
- Horas semanales.
- Grupo/ciclo correcto.
- No duplicar por acento o abreviatura.

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

## Hoja 11: Módulo Alumnos

Objetivo de esta hoja: explicar módulo alumnos para usuarios finales de SGCE, con enfoque en capturar alumnos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, módulo alumnos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Datos básicos.
- Grupo.
- Matrícula.
- Estado.
- Inscripción activa.

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

## Hoja 12: Bajas e historial

Objetivo de esta hoja: explicar bajas e historial para usuarios finales de SGCE, con enfoque en mantener información histórica. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, bajas e historial debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- No borrar si hay historial.
- Usar baja desde inscripción.
- Conservar ciclo y grupo.
- Revisar reportes después.

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

## Hoja 13: Asignaciones

Objetivo de esta hoja: explicar asignaciones para usuarios finales de SGCE, con enfoque en unir docente, materia y grupo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, asignaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Buscar por select con filtro.
- Confirmar horas.
- Confirmar ciclo activo.
- Evitar duplicar asignación.

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

## Hoja 14: Portal del maestro

Objetivo de esta hoja: explicar portal del maestro para usuarios finales de SGCE, con enfoque en trabajo diario docente. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, portal del maestro debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Ver asignaciones.
- Pasar lista.
- Calificar.
- Subir planeaciones.

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

## Hoja 15: Pase de lista

Objetivo de esta hoja: explicar pase de lista para usuarios finales de SGCE, con enfoque en registrar asistencia. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, pase de lista debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Elegir grupo/asignación.
- Elegir fecha.
- Marcar estado.
- Guardar y confirmar.

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

## Hoja 16: Corrección de asistencia

Objetivo de esta hoja: explicar corrección de asistencia para usuarios finales de SGCE, con enfoque en resolver errores de captura. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, corrección de asistencia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Buscar fecha correcta.
- Editar si el sistema lo permite.
- Evitar duplicar pase.
- Reportar cambios sensibles.

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

## Hoja 17: Consulta de asistencia

Objetivo de esta hoja: explicar consulta de asistencia para usuarios finales de SGCE, con enfoque en revisar asistencia por filtros. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, consulta de asistencia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Día.
- Semana.
- Mes.
- Rango personalizado.
- Exportación si aplica.

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

## Hoja 18: Calificaciones

Objetivo de esta hoja: explicar calificaciones para usuarios finales de SGCE, con enfoque en capturar evaluación. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Elegir periodo.
- Capturar valor válido.
- NC cuando no hay captura.
- Guardar y revisar resumen.

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

## Hoja 19: Escala de calificación

Objetivo de esta hoja: explicar escala de calificación para usuarios finales de SGCE, con enfoque en entender mínima, aprobatoria y máxima. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, escala de calificación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Mínima configurada.
- Aprobatoria institucional.
- Máxima permitida.
- Decimales según configuración.

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

## Hoja 20: Reportes de calificaciones

Objetivo de esta hoja: explicar reportes de calificaciones para usuarios finales de SGCE, con enfoque en consultar resultados. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, reportes de calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Por alumno.
- Por grupo.
- Por periodo.
- Boleta pública si aplica.

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

## Hoja 21: Kardex individual

Objetivo de esta hoja: explicar kardex individual para usuarios finales de SGCE, con enfoque en leer historial académico. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, kardex individual debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Varios ciclos.
- Materias cursadas.
- Periodos.
- Calificaciones agrupadas.

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

## Hoja 22: Conducta

Objetivo de esta hoja: explicar conducta para usuarios finales de SGCE, con enfoque en registrar incidencias. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, conducta debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Descripción clara.
- Severidad.
- Estado.
- Seguimiento.

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

## Hoja 23: Planeaciones docentes

Objetivo de esta hoja: explicar planeaciones docentes para usuarios finales de SGCE, con enfoque en subir planeaciones. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, planeaciones docentes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Formato permitido.
- Periodo o ciclo.
- Archivo válido.
- Estatus posterior.

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

## Hoja 24: Revisión de planeaciones

Objetivo de esta hoja: explicar revisión de planeaciones para usuarios finales de SGCE, con enfoque en administrar entregas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, revisión de planeaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Filtrar docente.
- Descargar archivo.
- Actualizar estatus.
- Dar seguimiento.

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

## Hoja 25: Avisos

Objetivo de esta hoja: explicar avisos para usuarios finales de SGCE, con enfoque en publicar información. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, avisos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Título claro.
- Mensaje breve.
- Fecha visible.
- Desactivar cuando ya no aplique.

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

## Hoja 26: Periodos

Objetivo de esta hoja: explicar periodos para usuarios finales de SGCE, con enfoque en administrar periodos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, periodos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Periodo activo.
- Fechas coherentes.
- Ciclo/oferta correcta.
- No duplicar nombres.

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

## Hoja 27: Configuración

Objetivo de esta hoja: explicar configuración para usuarios finales de SGCE, con enfoque en ajustar datos institucionales. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, configuración debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Escuela.
- Calificaciones.
- Planeaciones.
- URL base.

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

## Hoja 28: Importar alumnos

Objetivo de esta hoja: explicar importar alumnos para usuarios finales de SGCE, con enfoque en carga masiva. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, importar alumnos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar formato requerido.
- Revisar vista previa.
- Leer errores.
- Confirmar solo si está correcto.

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

## Hoja 29: Importar docentes

Objetivo de esta hoja: explicar importar docentes para usuarios finales de SGCE, con enfoque en usuarios y docentes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, importar docentes debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombre completo.
- Usuario.
- Contraseña inicial.
- Validación de duplicados.

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

## Hoja 30: Importar grupos

Objetivo de esta hoja: explicar importar grupos para usuarios finales de SGCE, con enfoque en crear grupos masivos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, importar grupos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Orden por turno.
- Grado/etapa.
- Grupo.
- Ciclo correcto.

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

## Hoja 31: Importar materias

Objetivo de esta hoja: explicar importar materias para usuarios finales de SGCE, con enfoque en materias por grupo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, importar materias debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Materia.
- Horas.
- Grupo.
- Validar asignación posterior.

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

## Hoja 32: Errores de importación

Objetivo de esta hoja: explicar errores de importación para usuarios finales de SGCE, con enfoque en entender reportes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, errores de importación debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Fila con problema.
- Campo inválido.
- Mensaje de causa.
- Corrección en archivo fuente.

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

## Hoja 33: Exportaciones PDF

Objetivo de esta hoja: explicar exportaciones pdf para usuarios finales de SGCE, con enfoque en descargar documentos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, exportaciones pdf debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Revisar encabezado.
- Guardar con nombre claro.
- No editar PDF oficial sin control.

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

## Hoja 34: Exportaciones Excel

Objetivo de esta hoja: explicar exportaciones excel para usuarios finales de SGCE, con enfoque en trabajo administrativo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, exportaciones excel debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usar para revisión.
- No reimportar archivos modificados sin validar.
- Conservar copia original.

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

## Hoja 35: Respaldos

Objetivo de esta hoja: explicar respaldos para usuarios finales de SGCE, con enfoque en proteger datos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, respaldos debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Generar periódicamente.
- Descargar fuera del servidor.
- Etiquetar con fecha.
- Probar restauración en copia.

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

## Hoja 36: Restaurar respaldo

Objetivo de esta hoja: explicar restaurar respaldo para usuarios finales de SGCE, con enfoque en recuperación controlada. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, restaurar respaldo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Solo administrador.
- Primero en entorno de prueba.
- Verificar versión.
- No subir SQL desconocido.

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

## Hoja 37: Bitácora

Objetivo de esta hoja: explicar bitácora para usuarios finales de SGCE, con enfoque en revisar actividad. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, bitácora debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Usuario.
- Acción.
- Fecha.
- Filtro.
- Evidencia de cambios.

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

## Hoja 38: Migración de ciclo

Objetivo de esta hoja: explicar migración de ciclo para usuarios finales de SGCE, con enfoque en pasar al siguiente ciclo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, migración de ciclo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Respaldo obligatorio.
- Diagnóstico previo.
- Promoción de alumnos.
- Egreso de terminales.

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

## Hoja 39: Migrar grupo

Objetivo de esta hoja: explicar migrar grupo para usuarios finales de SGCE, con enfoque en mover alumnos de un grupo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, migrar grupo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Fuente.
- Destino.
- Confirmación.
- Copiar asignaciones si aplica.

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

## Hoja 40: Migrar ciclo completo

Objetivo de esta hoja: explicar migrar ciclo completo para usuarios finales de SGCE, con enfoque en avance masivo. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, migrar ciclo completo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Crear destino.
- Promover 1 a 2, 2 a 3.
- Egresar terminal.
- Verificar resultados.

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

## Hoja 41: Consulta pública

Objetivo de esta hoja: explicar consulta pública para usuarios finales de SGCE, con enfoque en uso para familias. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, consulta pública debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Datos exactos.
- Sin PIN familiar.
- Sin token familiar.
- Botón regresar al final.

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

## Hoja 42: Consulta pública de asistencia

Objetivo de esta hoja: explicar consulta pública de asistencia para usuarios finales de SGCE, con enfoque en ver faltas. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, consulta pública de asistencia debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Día.
- Semana.
- Mes.
- Rango.
- Solo alumno coincidente.

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

## Hoja 43: Consulta pública de calificaciones

Objetivo de esta hoja: explicar consulta pública de calificaciones para usuarios finales de SGCE, con enfoque en ver calificaciones. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, consulta pública de calificaciones debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Periodo.
- Materia.
- NC cuando no existe captura.
- Exportar si aplica.

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

## Hoja 44: Uso en celular

Objetivo de esta hoja: explicar uso en celular para usuarios finales de SGCE, con enfoque en trabajar desde móvil. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, uso en celular debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Botones táctiles.
- Tablas adaptadas.
- Scroll vertical.
- Evitar zoom innecesario.

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

## Hoja 45: Buenas prácticas de captura

Objetivo de esta hoja: explicar buenas prácticas de captura para usuarios finales de SGCE, con enfoque en mantener datos limpios. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, buenas prácticas de captura debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Nombres completos.
- Mayúsculas/minúsculas consistentes.
- No abreviar sin criterio.
- Revisar antes de guardar.

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

## Hoja 46: Errores comunes de usuario

Objetivo de esta hoja: explicar errores comunes de usuario para usuarios finales de SGCE, con enfoque en resolver dudas frecuentes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, errores comunes de usuario debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- No aparece grupo: revisar ciclo.
- No aparece materia: revisar asignación.
- No guarda: revisar campos obligatorios.

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

## Hoja 47: Seguridad para usuarios

Objetivo de esta hoja: explicar seguridad para usuarios para usuarios finales de SGCE, con enfoque en evitar incidentes. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, seguridad para usuarios debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Cerrar sesión.
- No prestar contraseña.
- No usar equipos públicos sin cuidado.
- Reportar accesos extraños.

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

## Hoja 48: Trabajo diario del administrador

Objetivo de esta hoja: explicar trabajo diario del administrador para usuarios finales de SGCE, con enfoque en rutina de control. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, trabajo diario del administrador debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Revisar avisos.
- Revisar pendientes.
- Respaldar.
- Atender errores reportados.

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

## Hoja 49: Trabajo diario del maestro

Objetivo de esta hoja: explicar trabajo diario del maestro para usuarios finales de SGCE, con enfoque en rutina docente. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, trabajo diario del maestro debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Pasar lista.
- Calificar en periodo.
- Subir planeación.
- Revisar grupos asignados.

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

## Hoja 50: Trabajo de control escolar

Objetivo de esta hoja: explicar trabajo de control escolar para usuarios finales de SGCE, con enfoque en rutina administrativa. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, trabajo de control escolar debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Altas/bajas.
- Reportes.
- Importaciones.
- Expedientes.

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

## Hoja 51: Cierre de periodo

Objetivo de esta hoja: explicar cierre de periodo para usuarios finales de SGCE, con enfoque en validar antes de reportar. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, cierre de periodo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Calificaciones completas.
- Asistencias revisadas.
- Reportes exportados.
- Respaldo antes del cierre.

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

## Hoja 52: Cierre de ciclo

Objetivo de esta hoja: explicar cierre de ciclo para usuarios finales de SGCE, con enfoque en preparar migración. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, cierre de ciclo debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Respaldar.
- Revisar grupos.
- Revisar egresados.
- Ejecutar migración controlada.

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

## Hoja 53: Glosario de usuario

Objetivo de esta hoja: explicar glosario de usuario para usuarios finales de SGCE, con enfoque en entender términos. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, glosario de usuario debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- NC: No capturado.
- Ciclo activo: ciclo operativo.
- Asignación: maestro + materia + grupo.
- Kardex: historial académico.

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

## Hoja 54: Cierre del manual de usuario

Objetivo de esta hoja: explicar cierre del manual de usuario para usuarios finales de SGCE, con enfoque en usar SGCE con confianza. Esta documentación corresponde a SGCE 1.0.200, cierre documental sobre la rama Hardening de Producción. La versión mantiene explícitamente fuera de alcance 2FA, PIN familiar y token familiar en consulta pública. No cambia la lógica académica, de calificaciones, asistencia, conducta, Kardex, caché activo ni respaldo CSRF; su propósito es entregar ayuda extensa, clara y operativa para instalación, administración, soporte y uso diario.

En operación real, cierre del manual de usuario debe entenderse como una parte del flujo completo del sistema: configuración inicial, captura ordenada, validación, consulta, respaldo y seguimiento. Los roles principales son Administrador, Administrativo y Maestro. Cada perfil ve solo los módulos necesarios para su operación. La recomendación es no improvisar pasos en producción: primero validar en pruebas, después aplicar en el servidor definitivo.

Buenas prácticas: revisar datos antes de guardar, evitar duplicados, usar nombres consistentes, respetar ciclos y ofertas activas, generar respaldos antes de operaciones masivas y documentar cualquier ajuste realizado. Si aparece un mensaje de validación, debe corregirse el dato de origen en lugar de forzar cambios manuales en base de datos.

Resultado esperado: después de completar esta parte, el usuario debe poder reconocer qué pantalla usar, qué campos capturar, qué errores evitar, cómo confirmar que el proceso terminó correctamente y qué evidencia conservar.

### Puntos de verificación

- Seguir flujo recomendado.
- Respaldar.
- Leer mensajes.
- Pedir soporte con evidencia.

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
