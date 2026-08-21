<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lista los usuarios visibles para quien hace la petición.
     * super_admin ve todos; admin_institucional solo los de su propio centro.
     */
    #[OA\Get(
        path: '/users',
        summary: 'Listar usuarios',
        description: 'Devuelve los usuarios visibles para quien hace la peticion. El super_admin ve todos; el admin_institucional solo los de su propio centro de salud.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', description: 'Filtrar por estado: active (por defecto), inactive, all', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'all'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de usuarios'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para listar usuarios'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $admin = $request->user();

        $query = User::query();

        if ($admin->role === 'admin_institucional') {
            $query->where('health_center_id', $admin->health_center_id);
        }

        $status = $request->query('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn ($user) => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'organizationId' => $user->organization_id,
                'healthCenterId' => $user->health_center_id,
                'unitId'         => $user->unit_id,
                'isActive'       => $user->is_active,
            ]),
        ], 200);
    }

    /**
     * Devuelve el detalle de un usuario específico.
     */
    #[OA\Get(
        path: '/users/{id}',
        summary: 'Ver un usuario',
        description: 'Devuelve el detalle de un usuario. El admin_institucional solo puede ver usuarios de su propio centro.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos del usuario'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para ver este usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'organizationId' => $user->organization_id,
                'healthCenterId' => $user->health_center_id,
                'unitId'         => $user->unit_id,
                'isActive'       => $user->is_active,
            ],
        ], 200);
    }

    /**
     * Registra un nuevo usuario (funcionario del sistema).
     */
    #[OA\Post(
        path: '/users',
        summary: 'Registrar un nuevo usuario',
        description: 'Crea un usuario del personal de salud. Solo super_admin o admin_institucional pueden hacerlo, y admin_institucional unicamente dentro de su propio centro de salud. La contrasena se cifra automaticamente con bcrypt.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'role', 'organizationId', 'healthCenterId', 'unitId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Enfermera de Prueba'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'enfermera@test.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico'], example: 'categorizacion'),
                    new OA\Property(property: 'organizationId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'healthCenterId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'unitId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso, o intento de crear fuera del propio centro'),
            new OA\Response(response: 422, description: 'Datos invalidos o email duplicado'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $admin = $request->user();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'role'             => ['required', 'in:super_admin,admin_institucional,admision,categorizacion,medico'],
            'organizationId'   => ['required', 'uuid', 'exists:organizations,id'],
            'healthCenterId'   => ['required', 'uuid', 'exists:health_centers,id'],
            'unitId'           => ['required', 'uuid', 'exists:units,id'],
        ]);

        if ($admin->role === 'admin_institucional' && $data['healthCenterId'] !== $admin->health_center_id) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Solo puedes registrar usuarios en tu propio centro de salud.'],
            ], 403);
        }

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => $data['password'],
            'role'              => $data['role'],
            'organization_id'   => $data['organizationId'],
            'health_center_id'  => $data['healthCenterId'],
            'unit_id'           => $data['unitId'],
            'is_active'         => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'user' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'role'     => $user->role,
                    'isActive' => $user->is_active,
                ],
            ],
        ], 201);
    }

    /**
     * Actualiza los datos de un usuario existente.
     */
    #[OA\Put(
        path: '/users/{id}',
        summary: 'Editar un usuario',
        description: 'Actualiza los datos de un usuario. El admin_institucional solo puede editar usuarios de su propio centro.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Enfermera Actualizada'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nueva@test.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico']),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'nuevaClave123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'nuevaClave123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para editar este usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'role'     => ['sometimes', 'in:super_admin,admin_institucional,admision,categorizacion,medico'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill($data);
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'organizationId' => $user->organization_id,
                'healthCenterId' => $user->health_center_id,
                'unitId'         => $user->unit_id,
                'isActive'       => $user->is_active,
            ],
        ], 200);
    }

    /**
     * Desactiva un usuario (soft delete). No borra el registro.
     */
    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Desactivar un usuario',
        description: 'Marca al usuario como inactivo (isActive=false). No elimina el registro de la base de datos.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario desactivado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso, o intento de autodesactivacion'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->is_active = false;
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $user->id,
                'isActive' => $user->is_active,
            ],
        ], 200);
    }

    /**
     * Reactiva un usuario previamente desactivado.
     */
    #[OA\Patch(
        path: '/users/{id}/restore',
        summary: 'Reactivar un usuario',
        description: 'Marca al usuario como activo (isActive=true). El admin_institucional solo puede reactivar usuarios de su propio centro.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario reactivado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para reactivar este usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);

        $user->is_active = true;
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $user->id,
                'isActive' => $user->is_active,
            ],
        ], 200);
    }
}