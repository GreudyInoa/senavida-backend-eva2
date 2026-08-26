<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PictogramPolicy
{
    /**
     * Cualquier miembro del staff autenticado puede ver el catalogo:
     * lo necesita el chat para mostrar pictogramas al paciente.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede crear pictogramas.');
    }

    public function update(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede editar pictogramas.');
    }
}