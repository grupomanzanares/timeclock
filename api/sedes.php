<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'listar':
        $rows = DB::fetchAll(
            'SELECT s.*, u.nombre AS supervisor_nombre
             FROM sedes s
             LEFT JOIN usuarios u ON u.id = s.supervisor_id
             WHERE s.activo = 1
             ORDER BY s.nombre ASC'
        );
        Response::success($rows);
        break;

    case 'crear':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre    = Helpers::clean($body['nombre'] ?? '');
        $direccion = Helpers::clean($body['direccion'] ?? '');
        $supId     = !empty($body['supervisor_id']) ? (int)$body['supervisor_id'] : null;

        if (!$nombre) Response::error('Nombre requerido');

        $id = DB::insert(
            'INSERT INTO sedes (nombre, direccion, supervisor_id) VALUES (?, ?, ?)',
            [$nombre, $direccion, $supId]
        );
        Response::success(['id' => $id], 'Sede creada');
        break;

    case 'editar':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int)($body['id'] ?? 0);
        if (!$id) Response::error('ID requerido');

        DB::execute(
            'UPDATE sedes SET nombre=?, direccion=?, supervisor_id=? WHERE id=?',
            [
                Helpers::clean($body['nombre'] ?? ''),
                Helpers::clean($body['direccion'] ?? ''),
                !empty($body['supervisor_id']) ? (int)$body['supervisor_id'] : null,
                $id,
            ]
        );
        Response::success(null, 'Sede actualizada');
        break;

    case 'eliminar':
        Auth::requireRole('admin');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID requerido');
        DB::execute('UPDATE sedes SET activo = 0 WHERE id = ?', [$id]);
        Response::success(null, 'Sede desactivada');
        break;

    default:
        Response::error('Acción no válida', 404);
}
