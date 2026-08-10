<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inicia sesión y devuelve un token de acceso.
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Validar que lleguen email y password con el formato correcto
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Buscar al usuario por su email
        $user = User::where('email', $credentials['email'])->first();

        // 3. Verificar credenciales: que el usuario exista y la contraseña coincida
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        // 4. Verificar que el usuario esté activo
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Tu cuenta está desactivada.'],
            ], 403);
        }

        // 5. Crear el token de Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        // 6. Devolver el token y los datos del usuario
        return response()->json([
            'success' => true,
            'data'    => [
                'token'     => $token,
                'tokenType' => 'Bearer',
                'user'      => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'role'     => $user->role,
                    'isActive' => $user->is_active,
                ],
            ],
        ], 200);
    }
}