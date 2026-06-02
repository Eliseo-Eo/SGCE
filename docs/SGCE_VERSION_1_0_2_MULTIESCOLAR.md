# SGCE Version 1.0.2 — Motor multiescolar

## Objetivo

La versión 1.0.2 convierte SGCE en un sistema más amplio: puede configurarse para primaria, secundaria, bachillerato, universidad, maestría, doctorado, cursos o diplomados.

El cambio más importante es que la promoción de alumnos ya no depende de reglas fijas como `1 → 2 → 3 → egresado`. Ahora depende de una estructura académica configurable.

## Modelo académico

La estructura nueva se basa en:

- **Oferta educativa:** representa la modalidad general de la institución o programa.
- **Nivel educativo:** primaria, secundaria, bachillerato, universidad, maestría, doctorado o curso.
- **Tipo de organización:** anual, semestral, cuatrimestral o modular.
- **Etapas académicas:** años, grados, semestres, cuatrimestres o módulos.
- **Carreras/programas:** opcionales. Se usan cuando la escuela lo necesita, por ejemplo universidad, CONALEP, bachillerato técnico, maestría o doctorado.

## Tablas agregadas

- `OfertasEducativas`
- `EtapasAcademicas`
- `Carreras`

## Tablas ampliadas

- `Grupos`: ahora puede apuntar a oferta, etapa y carrera.
- `AlumnoInscripciones`: ahora conserva oferta, etapa y carrera de la inscripción histórica.
- `KardexAlumno`: ahora congela también oferta, carrera y etapa como texto histórico.

## Flujo correcto

Un alumno no se copia como persona. Se conserva un solo registro en `Alumnos` y se agregan inscripciones por ciclo escolar:

```text
Alumno: Eliseo
2025-2026 → Secundaria / 1 / B
2026-2027 → Secundaria / 2 / B
2027-2028 → Secundaria / 3 / B
```

En universidad puede ser:

```text
Alumno: Ana
2026-A → Ingeniería en Sistemas / 1 SEMESTRE / A
2026-B → Ingeniería en Sistemas / 2 SEMESTRE / A
```

## Migración académica

Al migrar un grupo o ciclo:

1. El ciclo origen debe estar inactivo/cerrado.
2. El ciclo destino debe ser el ciclo activo.
3. SGCE busca la siguiente etapa configurada.
4. Si existe siguiente etapa, crea o reutiliza el grupo equivalente en el ciclo activo.
5. Si la etapa origen es terminal, el alumno queda como egresado.
6. Antes de cambiar estado, el kardex se congela para proteger boletas históricas.

## Ejemplos de configuración

### Primaria

- Nivel: `PRIMARIA`
- Organización: `ANUAL`
- Etapas: `6`
- Carreras: no

### Secundaria

- Nivel: `SECUNDARIA`
- Organización: `ANUAL`
- Etapas: `3`
- Carreras: no

### Bachillerato general

- Nivel: `BACHILLERATO`
- Organización: `SEMESTRAL`
- Etapas: `6`
- Carreras: opcional no

### Bachillerato técnico / CONALEP

- Nivel: `BACHILLERATO`
- Organización: `SEMESTRAL`
- Etapas: `6`
- Carreras: sí

### Universidad

- Nivel: `UNIVERSIDAD`
- Organización: `SEMESTRAL` o `CUATRIMESTRAL`
- Etapas: normalmente `8`, `9`, `10` o según plan
- Carreras: sí

### Maestría / doctorado

- Nivel: `MAESTRIA` o `DOCTORADO`
- Organización: `SEMESTRAL`, `CUATRIMESTRAL` o `MODULAR`
- Etapas: según plan
- Carreras/programas: sí

## Validaciones importantes

- No se puede migrar desde un ciclo activo.
- No se renombra el grupo viejo.
- No se duplica al alumno como persona.
- No se mezclan grupos iguales de ciclos diferentes.
- En instituciones con carreras, dos grupos pueden llamarse igual si pertenecen a carreras distintas.
- La materia sigue siendo estable; el docente puede cambiar por interinato/relevo sin perder historial.

## Notas de instalación

Desde `Instalar.php` ya se puede elegir:

- nivel educativo;
- tipo de organización;
- cantidad de etapas;
- si usa carreras/programas;
- carreras iniciales.

Después de instalado, esta estructura también puede revisarse desde Configuración.
