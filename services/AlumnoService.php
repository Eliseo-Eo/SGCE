<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/repositories/AlumnosRepository.php';

function SgceAlumnoContarActivos(PDO $Pdo): int { return SgceAlumnoContarFiltrado($Pdo, []); }

function SgceAlumnoContarFiltrado(PDO $Pdo, array $Filtros = []): int { return SgceRepoAlumnoContar($Pdo, $Filtros); }

function SgceAlumnoListarFiltrado(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array { return SgceRepoAlumnoListar($Pdo, $Filtros, $Limit, $Offset); }

function SgceAlumnoListarActivosPorGrupo(PDO $Pdo, int $GrupoId): array { return SgceRepoAlumnoPorGrupo($Pdo, $GrupoId, 1000, 0); }
