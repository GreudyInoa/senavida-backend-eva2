<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PictogramCategory;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PictogramCategoryController extends Controller
{
    #[OA\Get(
        path: '/pictogram-categories',
        summary: 'Listar categorías de pictogramas',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Lista de categorías de pictogramas')]
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Pictogram::class);

        $categories = PictogramCategory::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(fn ($category) => [
                'id'        => $category->id,
                'name'      => $category->name,
                'sortOrder' => $category->sort_order,
            ]),
        ]);
    }
}