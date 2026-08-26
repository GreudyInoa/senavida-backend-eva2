<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePictogramRequest;
use App\Models\Pictogram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class PictogramController extends Controller
{
    #[OA\Get(
        path: '/pictograms',
        summary: 'Listar pictogramas activos',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de pictogramas activos')]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Pictogram::class);

        $pictograms = Pictogram::query()
            ->when($request->filled('categoryId'), fn ($q) =>
                $q->where('pictogram_category_id', $request->query('categoryId'))
            )
            ->where('is_active', true)
            ->orderBy('pictogram_category_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pictograms->map(fn ($p) => $this->toArray($p)),
        ]);
    }

    #[OA\Post(
        path: '/pictograms',
        summary: 'Crear un pictograma',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pictogramCategoryId', 'title', 'phrase', 'speechText', 'emoji', 'severity'],
                properties: [
                    new OA\Property(property: 'pictogramCategoryId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', type: 'string', example: 'Dolor de cabeza'),
                    new OA\Property(property: 'phrase', type: 'string', example: 'Me duele la cabeza'),
                    new OA\Property(property: 'speechText', type: 'string', example: 'Tengo dolor de cabeza'),
                    new OA\Property(property: 'emoji', type: 'string', example: '🤕'),
                    new OA\Property(property: 'severity', type: 'string', enum: ['critical', 'warning', 'info', 'neutral']),
                    new OA\Property(property: 'isActive', type: 'boolean', example: true),
                    new OA\Property(property: 'sortOrder', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Pictograma creado correctamente')]
    )]
    public function store(StorePictogramRequest $request): JsonResponse
    {
        $pictogram = Pictogram::create($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ], Response::HTTP_CREATED);
    }

    #[OA\Patch(
        path: '/pictograms/{id}',
        summary: 'Actualizar un pictograma existente',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Pictograma actualizado correctamente')]
    )]
    public function update(StorePictogramRequest $request, Pictogram $pictogram): JsonResponse
    {
        $this->authorize('update', $pictogram);

        $pictogram->update($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ]);
    }

    private function toArray(Pictogram $pictogram): array
    {
        return [
            'id'                  => $pictogram->id,
            'pictogramCategoryId' => $pictogram->pictogram_category_id,
            'title'               => $pictogram->title,
            'phrase'              => $pictogram->phrase,
            'speechText'          => $pictogram->speech_text,
            'emoji'               => $pictogram->emoji,
            'severity'            => $pictogram->severity->value,
            'isActive'            => $pictogram->is_active,
            'sortOrder'           => $pictogram->sort_order,
        ];
    }
}