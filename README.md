# SGCE 1.0.200 - Corrección visual de botones Cancelar

SGCE 1.0.200 es un parche visual dirigido sobre la rama Hardening de Producción. No cambia lógica académica, calificaciones, asistencia, conducta, Kardex, caché activo, instalador, CSRF, SQL, `.htaccess` ni comportamiento funcional. El objetivo de esta versión es consolidar el estilo de los botones Cancelar en modales y mantener la documentación extensa de ayuda para entrega.

## Alcance de 1.0.200

- Corrección visual de `Cancelar` en modales de confirmación, edición, eliminación, importación y conducta.
- `Cancelar` queda siempre como botón secundario institucional: fondo blanco, texto guinda, borde guinda y sombra suave.
- El botón de aceptar/confirmar conserva su color por contexto: rojo para eliminar/desactivar, verde para importar, azul para guardar.
- Manuales extensos conservados y titulados para 1.0.200.
- README raíz actualizado a 1.0.200.
- Versión identificable en `VERSION.txt` y `Sgce\Foundation\Version::CURRENT`.

## Fuera de alcance

Esta entrega no agrega 2FA, PIN familiar ni token familiar en consulta pública. Tampoco modifica Kardex, caché de ciclo/oferta activa, respaldo CSRF, lógica académica, calificaciones, asistencia, conducta, migración, base de datos, `.htaccess`, CSS ni JavaScript.

## Instalación desde cero

1. Copia el contenido de `Produccion/` al directorio público del dominio o subdominio.
2. Asegura PHP 8.1 o superior con extensiones `pdo`, `pdo_mysql`, `json`, `fileinfo`, `mbstring`, `openssl` y `zip`.
3. Crea una base MySQL/MariaDB vacía con `utf8mb4_unicode_ci`.
4. Abre `Instalar.php` por HTTPS.
5. Captura conexión MySQL, datos institucionales, oferta educativa, ciclo inicial, periodos, planeaciones y usuario administrador.
6. Finaliza instalación y valida `VERSION.txt = 1.0.200`.
7. Entra al Dashboard y realiza pruebas mínimas de maestros, grupos, materias, alumnos, asignaciones, asistencia, calificaciones, reportes y respaldos.

Para pasos detallados, usa `docs/manuales/Manual_Instalacion_SGCE_1.0.200.pdf` o su versión DOCX/MD.

## Documentación oficial incluida

- `docs/manuales/Manual_Instalacion_SGCE_1.0.200.md`
- `docs/manuales/Manual_Instalacion_SGCE_1.0.200.docx`
- `docs/manuales/Manual_Instalacion_SGCE_1.0.200.pdf`
- `docs/manuales/Manual_Tecnico_SGCE_1.0.200.md`
- `docs/manuales/Manual_Tecnico_SGCE_1.0.200.docx`
- `docs/manuales/Manual_Tecnico_SGCE_1.0.200.pdf`
- `docs/manuales/Manual_Usuario_SGCE_1.0.200.md`
- `docs/manuales/Manual_Usuario_SGCE_1.0.200.docx`
- `docs/manuales/Manual_Usuario_SGCE_1.0.200.pdf`

## Historial y evidencia

El historial técnico de 1.0.196, 1.0.197 y 1.0.198 se conserva en `docs/historial/`. La carpeta 1.0.197 mantiene registro real de la versión vulnerable y sus evidencias; la carpeta 1.0.198 documenta la corrección de seguridad que retiró `Produccion/tests/` del paquete instalable. La carpeta 1.0.200 documenta este cierre documental.

## Seguridad de producción

El paquete final no debe contener `tests/`, `__pycache__/`, `*.pyc` ni herramientas ejecutables de prueba dentro de producción. Las evidencias se conservan como texto en `docs/historial/`, no como scripts ejecutables.

## Nota de entorno

La evidencia histórica indica que en el contenedor de construcción no existían `pdo_mysql`, cliente `mysql` ni `mysqld`. Por eso la instalación real contra MySQL debe validarse en el servidor/Plesk final siguiendo el manual de instalación 1.0.200.
