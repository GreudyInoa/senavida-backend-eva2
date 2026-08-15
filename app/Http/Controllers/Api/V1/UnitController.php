<?php

namespace App\Http\Controllers\Api\V1;

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
}