<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\StoreMyWorkshopRequest;
use App\Http\Requests\Workshop\UpdateMyWorkshopRequest;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MyWorkshopController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Workshop::class);

        $workshops = Workshop::query()
            ->where('owner_id', $request->user()->id)
            ->with('photos')
            ->latest()
            ->get();

        return WorkshopResource::collection($workshops);
    }

    public function store(StoreMyWorkshopRequest $request): JsonResponse
    {
        $this->authorize('create', Workshop::class);

        $data = $request->safe()->except(['submit']);
        $shouldSubmit = (bool) $request->boolean('submit', true);

        $workshop = Workshop::create([
            ...$data,
            'owner_id' => $request->user()->id,
            'country' => $data['country'] ?? 'VE',
            'slug' => Workshop::uniqueSlugFromName($data['name']),
            'status' => $shouldSubmit
                ? WorkshopStatus::PendingReview
                : WorkshopStatus::Draft,
            'verified' => false,
        ]);

        return response()->json([
            'data' => new WorkshopResource($workshop->load('photos')),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Workshop $workshop): JsonResponse
    {
        $this->authorize('view', $workshop);

        if (! $request->user()->isAdmin() && ! $workshop->isOwnedBy($request->user())) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => new WorkshopResource($workshop->load('photos')),
        ]);
    }

    public function update(UpdateMyWorkshopRequest $request, Workshop $workshop): JsonResponse
    {
        $this->authorize('update', $workshop);

        if (! $workshop->isOwnedBy($request->user()) && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        // Owners cannot change status/verified/owner via this endpoint.
        $workshop->update($request->validated());

        if ($request->has('name') && blank($workshop->slug)) {
            $workshop->update([
                'slug' => Workshop::uniqueSlugFromName($workshop->name),
            ]);
        }

        return response()->json([
            'data' => new WorkshopResource($workshop->fresh()->load('photos')),
        ]);
    }

    public function submit(Request $request, Workshop $workshop): JsonResponse
    {
        $this->authorize('submit', $workshop);

        if (! $workshop->isOwnedBy($request->user()) && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        if ($workshop->status === WorkshopStatus::Published) {
            return response()->json([
                'message' => 'El taller ya esta publicado.',
                'data' => new WorkshopResource($workshop),
            ]);
        }

        if ($workshop->status === WorkshopStatus::PendingReview) {
            return response()->json([
                'message' => 'El taller ya esta en revision.',
                'data' => new WorkshopResource($workshop),
            ]);
        }

        // draft o suspended → pending_review
        $workshop->status = WorkshopStatus::PendingReview;
        $workshop->save();

        return response()->json([
            'message' => 'Taller enviado a revision.',
            'data' => new WorkshopResource($workshop),
        ]);
    }
}
