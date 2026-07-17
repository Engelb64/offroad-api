<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminWorkshopRequest;
use App\Http\Requests\Admin\UpdateAdminWorkshopRequest;
use App\Http\Requests\Admin\UpdateWorkshopStatusRequest;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class WorkshopController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Workshop::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'ilike', "%{$q}%")
                    ->orWhere('city', 'ilike', "%{$q}%");
            });
        }

        return WorkshopResource::collection($query->get());
    }

    public function store(StoreAdminWorkshopRequest $request): JsonResponse
    {
        $data = $request->validated();

        $workshop = Workshop::create([
            ...$data,
            'country' => $data['country'] ?? 'VE',
            'slug' => Workshop::uniqueSlugFromName($data['name']),
            'status' => isset($data['status'])
                ? WorkshopStatus::from($data['status'])
                : WorkshopStatus::Draft,
            'verified' => (bool) ($data['verified'] ?? false),
        ]);

        return response()->json([
            'data' => new WorkshopResource($workshop),
        ], Response::HTTP_CREATED);
    }

    public function show(Workshop $workshop): JsonResponse
    {
        return response()->json([
            'data' => new WorkshopResource($workshop),
        ]);
    }

    public function update(UpdateAdminWorkshopRequest $request, Workshop $workshop): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['status'])) {
            $data['status'] = WorkshopStatus::from($data['status']);
        }

        $workshop->update($data);

        if ($request->has('name') && blank($workshop->slug)) {
            $workshop->update([
                'slug' => Workshop::uniqueSlugFromName($workshop->name),
            ]);
        }

        return response()->json([
            'data' => new WorkshopResource($workshop->fresh()),
        ]);
    }

    public function updateStatus(UpdateWorkshopStatusRequest $request, Workshop $workshop): JsonResponse
    {
        $status = WorkshopStatus::from($request->validated('status'));
        $workshop->status = $status;

        if ($request->exists('verified')) {
            $workshop->verified = $request->boolean('verified');
        }

        if ($status === WorkshopStatus::Suspended) {
            $workshop->moderation_note = trim((string) $request->validated('moderation_note'));
            $workshop->moderation_at = now();
            $workshop->moderated_by = $request->user()?->id;
        }

        if ($status === WorkshopStatus::Published) {
            // Al republicar se limpia el motivo visible para el dueño.
            $workshop->moderation_note = null;
            $workshop->moderation_at = null;
            $workshop->moderated_by = null;

            if (! $request->exists('verified')) {
                $workshop->verified = true;
            }
        }

        $workshop->save();

        return response()->json([
            'message' => 'Estado del taller actualizado.',
            'data' => new WorkshopResource($workshop->fresh()),
        ]);
    }

    public function destroy(Workshop $workshop): Response
    {
        $workshop->delete();

        return response()->noContent();
    }
}
