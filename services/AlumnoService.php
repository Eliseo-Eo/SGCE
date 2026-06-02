<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAlumnoContarActivos(PDO $Pdo): int { return SgceAlumnoContarFiltrado($Pdo, []); }

function SgceAlumnoContarFiltrado(PDO $Pdo, array $Filtros = []): int { return SgceRepoAlumnoContar($Pdo, $Filtros); }

function SgceAlumnoListarPaginado(PDO $Pdo, int $Limit, int $Offset): array { return SgceAlumnoListarFiltrado($Pdo, [], $Limit, $Offset); }

function SgceAlumnoListarFiltrado(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array { return SgceRepoAlumnoListar($Pdo, $Filtros, $Limit, $Offset); }

function SgceAlumnoListarActivosPorGrupo(PDO $Pdo, int $GrupoId): array { return SgceRepoAlumnoPorGrupo($Pdo, $GrupoId, 1000, 0); }
