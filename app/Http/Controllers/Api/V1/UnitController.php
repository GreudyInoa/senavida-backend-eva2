<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Lista las unidades activas. Si se envía healthCenterId,
     * filtra solo las unidades de ese centro.
     */

        #[OA\Get(
        path: '/units',
        summary: 'Listar unidades',
        description: 'Devuelve las unidades del sistema. Si se envia el parametro healthCenterId, filtra solo las de ese centro.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'healthCenterId',
                description: 'UUID del centro de salud para filtrar sus unidades (opcional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de unidades'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $query = Unit::where('is_active', true);

        if ($request->has('healthCenterId')) {
            $query->where('health_center_id', $request->query('healthCenterId'));
        }

        $units = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $units->map(fn ($unit) => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
            ]),
        ], 200);
    }

    /**
     * Crea una unidad nueva.
     * super_admin: puede crear en cualquier centro.
     * admin_institucional: solo puede crear en SU propio centro.
     */

        #[OA\Post(
        path: '/units',
        summary: 'Crear unidad',
        description: 'Crea una unidad dentro de un centro de salud. El super_admin puede crearla en cualquier centro; el admin_institucional solo en el suyo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'healthCenterId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Urgencia Adulto'),
                    new OA\Property(property: 'healthCenterId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Unidad creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso, o fuera del propio centro'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Verificar que el rol tenga permiso en absoluto
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'No tienes permiso para crear unidades.'],
            ], 403);
        }

        // 2. Validar los datos
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'healthCenterId' => ['required', 'uuid', 'exists:health_centers,id'],
        ]);

        // 3. Si es admin_institucional, solo puede crear en su propio centro
        if ($user->role === 'admin_institucional' && $data['healthCenterId'] !== $user->health_center_id) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Solo puedes crear unidades en tu propio centro de salud.'],
            ], 403);
        }

        // 4. Crear la unidad
        $unit = Unit::create([
            'name'             => $data['name'],
            'health_center_id' => $data['healthCenterId'],
            'is_active'        => true,
        ]);

        // 5. Responder con los datos creados
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
            ],
        ], 201);
    }
}