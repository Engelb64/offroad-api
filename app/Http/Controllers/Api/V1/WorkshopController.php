<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
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
        $query = Workshop::query()
            ->where('status', WorkshopStatus::Published)
            ->latest();

        if ($request->filled('city')) {
            $query->where('city', 'ilike', '%'.$request->string('city')->toString().'%');
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'ilike', "%{$q}%")
                    ->orWhere('city', 'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%");
            });
        }

        if ($request->filled('service')) {
            $service = $request->string('service')->toString();
            $query->whereJsonContains('services', $service);
        }

        return WorkshopResource::collection($query->get());
    }

    public function show(Request $request, Workshop $workshop): JsonResponse
    {
        $user = $request->user();
        $isOwner = $workshop->isOwnedBy($user);
        $isAdmin = $user->isAdmin();
        $isPublished = $workshop->status === WorkshopStatus::Published;

        if (! $isPublished && ! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Taller no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new WorkshopResource($workshop->load('photos')),
        ]);
    }
}
