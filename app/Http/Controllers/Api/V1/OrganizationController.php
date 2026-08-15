<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    /**
     * Lista todas las organizaciones activas.
     */
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
}