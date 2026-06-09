# Manual de instalación - SGCE 1.0.122

## Requisitos recomendados

- PHP 8.2 o superior.
- MySQL/MariaDB compatible con InnoDB.
- Extensiones PHP: PDO, pdo_mysql, mbstring, zip, fileinfo, json y session.
- Servidor web con soporte para `.htaccess` si se usa Apache.
- HTTPS para producción.

## Instalación limpia

1. Sube el contenido de `Produccion/` al directorio público del servidor.
2. Crea una base de datos vacía.
3. Abre `Instalar.php` en el navegador.
4. Captura credenciales de base de datos.
5. Captura datos de la escuela.
6. Configura oferta educativa, etapas, ciclo inicial y periodos.
7. Crea el usuario administrador.
8. Finaliza la instalación.
9. Verifica que exista `storage/install.lock`.
10. Entra al sistema con el usuario administrador.


## Parámetros multinivel en el instalador

Durante la instalación inicial también puedes configurar:

- **Turnos disponibles:** un turno por línea. Ejemplo: MATUTINO, VESPERTINO, NOCTURNO, SABATINO, EN LÍNEA o SIN TURNO.
- **Escala de calificaciones:** mínima, máxima, aprobatoria y si se permiten decimales.
- **Matrícula automática:** activa o desactiva la generación automática y define el prefijo de matrícula.

Estos valores no quedan encerrados en la instalación. Después de entrar como administrador puedes ajustarlos desde **Configuración → Parámetros multinivel**.

## Después de instalar

Revisa:

- Configuración académica.
- Ciclos y periodos.
- Permisos de `storage/`.
- Funcionamiento de importaciones.
- Creación de respaldos.

## Orden recomendado de carga inicial

1. Docentes.
2. Grupos.
3. Materias por grupo.
4. Alumnos.
5. Asignaciones.
6. Pruebas de asistencia y calificación.
7. Planeaciones, si aplica.

## Migración escolar

Para probar migración:

1. Carga datos del ciclo inicial.
2. Crea y activa un ciclo nuevo.
3. Verifica que el nuevo ciclo tenga periodos.
4. Entra a Migración.
5. Revisa diagnóstico.
6. Ejecuta simulación.
7. Migra escribiendo la confirmación exacta.
8. Revisa grupos, materias y alumnos en el ciclo activo.

## Instalación desde cero

Si vas a probar desde cero, borra la base anterior o usa una base nueva. No mezcles instalaciones de prueba con datos reales sin respaldo.
