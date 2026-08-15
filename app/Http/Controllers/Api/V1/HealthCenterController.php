<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HealthCenter;
use Illuminate\Http\JsonResponse;

class HealthCenterController extends Controller
{
    /**
     * Lista todos los centros de salud activos.
     */
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
}