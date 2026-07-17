<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\StoreWorkshopPhotoRequest;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use App\Models\WorkshopPhoto;
use App\Services\WorkshopMediaService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class MyWorkshopPhotoController extends Controller
{
    public function __construct(
        private readonly WorkshopMediaService $media,
    ) {}

    public function storeCover(StoreWorkshopPhotoRequest $request, Workshop $workshop): JsonResponse
    {
        $this->authorizeWorkshop($request, $workshop);

        try {
            $this->media->storeCover($workshop, $request->file('photo'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Foto principal actualizada.',
            'data' => new WorkshopResource($workshop->fresh()->load('photos')),
        ]);
    }

    public function destroyCover(Workshop $workshop): JsonResponse
    {
        $this->authorizeWorkshop(request(), $workshop);

        $this->media->deleteCover($workshop);

        return response()->json([
            'message' => 'Foto principal eliminada.',
            'data' => new WorkshopResource($workshop->fresh()->load('photos')),
        ]);
    }

    public function storeGallery(StoreWorkshopPhotoRequest $request, Workshop $workshop): JsonResponse
    {
        $this->authorizeWorkshop($request, $workshop);

        try {
            $this->media->storeGalleryPhoto($workshop, $request->file('photo'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Foto secundaria agregada.',
            'data' => new WorkshopResource($workshop->fresh()->load('photos')),
        ], Response::HTTP_CREATED);
    }

    public function destroyGallery(Workshop $workshop, WorkshopPhoto $photo): JsonResponse
    {
        $this->authorizeWorkshop(request(), $workshop);

        if ((int) $photo->workshop_id !== (int) $workshop->id) {
            return response()->json(['message' => 'Foto no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $this->media->deleteGalleryPhoto($photo);

        return response()->json([
            'message' => 'Foto secundaria eliminada.',
            'data' => new WorkshopResource($workshop->fresh()->load('photos')),
        ]);
    }

    private function authorizeWorkshop($request, Workshop $workshop): void
    {
        $this->authorize('update', $workshop);

        $user = $request->user();
        if (! $user->isAdmin() && ! $workshop->isOwnedBy($user)) {
            abort(Response::HTTP_FORBIDDEN, 'No autorizado.');
        }
    }
}
