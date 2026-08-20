<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Lista todas las organizaciones activas.
     */

        #[OA\Get(
        path: '/organizations',
        summary: 'Listar organizaciones',
        description: 'Devuelve todas las organizaciones de salud registradas en el sistema.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de organizaciones'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]

    public function index(): JsonResponse
    {
        $organizations = Organization::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $organizations->map(fn ($org) => [
                'id'   => $org->id,
                'name' => $org->name,
            ]),
        ], 200);
    }

    /**
     * Crea una organización nueva.
     * Solo un super_admin puede hacerlo.
     */
    
        #[OA\Post(
        path: '/organizations',
        summary: 'Crear organizacion',
        description: 'Crea una nueva organizacion de salud. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Servicio de Salud Metropolitano'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Organizacion creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede crear organizaciones'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]

    public function store(Request $request): JsonResponse
    {
        // 1. Verificar que quien hace la petición sea super_admin
        if ($request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'No tienes permiso para crear organizaciones.'],
            ], 403);
        }

        // 2. Validar los datos
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // 3. Crear la organización
        $organization = Organization::create([
            'name'      => $data['name'],
            'is_active' => true,
        ]);

        // 4. Responder con los datos creados
        return response()->json([
            'success' => true,
            'data'    => [
                'id'   => $organization->id,
                'name' => $organization->name,
            ],
        ], 201);
    }
}