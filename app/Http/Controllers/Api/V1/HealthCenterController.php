<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\HealthCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCenterController extends Controller
{
    /**
     * Lista todos los centros de salud activos.
     */
    
        #[OA\Get(
        path: '/health-centers',
        summary: 'Listar centros de salud',
        description: 'Devuelve todos los centros de salud, cada uno con el organizationId al que pertenece.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de centros de salud'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(): JsonResponse
    {
        $healthCenters = HealthCenter::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $healthCenters->map(fn ($center) => [
                'id'             => $center->id,
                'name'           => $center->name,
                'organizationId' => $center->organization_id,
            ]),
        ], 200);
    }

    /**
     * Crea un centro de salud nuevo.
     * Solo un super_admin puede hacerlo.
     */
    
        #[OA\Post(
        path: '/health-centers',
        summary: 'Crear centro de salud',
        description: 'Crea un nuevo centro de salud dentro de una organizacion. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'organizationId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Hospital San Rafael'),
                    new OA\Property(property: 'organizationId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Centro de salud creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede crear centros'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]

    public function store(Request $request): JsonResponse
    {
        // 1. Verificar que quien hace la petición sea super_admin
        if ($request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'No tienes permiso para crear centros de salud.'],
            ], 403);
        }

        // 2. Validar los datos
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'organizationId' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        // 3. Crear el centro
        $healthCenter = HealthCenter::create([
            'name'            => $data['name'],
            'organization_id' => $data['organizationId'],
            'is_active'       => true,
        ]);

        // 4. Responder con los datos creados
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $healthCenter->id,
                'name'           => $healthCenter->name,
                'organizationId' => $healthCenter->organization_id,
            ],
        ], 201);
    }
}