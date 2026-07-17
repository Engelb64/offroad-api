<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\IndexWorkshopRequest;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WorkshopController extends Controller
{
    public function index(IndexWorkshopRequest $request): AnonymousResourceCollection
    {
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $hasPoint = $lat !== null && $lng !== null;
        $radiusKm = $hasPoint
            ? (float) ($request->input('radius_km') ?? 25)
            : null;
        $sort = $request->input('sort', $hasPoint ? 'distance' : 'recent');

        $query = Workshop::query()
            ->where('status', WorkshopStatus::Published);

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

        if ($hasPoint) {
            $pointSql = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography';
            $distanceSql = "ST_Distance(workshops.location, {$pointSql}) / 1000.0";

            $query->select('workshops.*')
                ->selectRaw("{$distanceSql} AS distance_km", [$lng, $lat]);

            if ($radiusKm !== null) {
                $query->whereNotNull('workshops.location')
                    ->whereRaw(
                        "ST_DWithin(workshops.location, {$pointSql}, ?)",
                        [$lng, $lat, $radiusKm * 1000],
                    );
            }

            if ($sort === 'distance') {
                $query->orderByRaw("({$distanceSql}) ASC NULLS LAST", [$lng, $lat]);
            } else {
                $query->latest('workshops.created_at');
            }
        } else {
            $query->latest();
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

        if ($request->filled('lat') && $request->filled('lng') && $workshop->latitude !== null && $workshop->longitude !== null) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');
            $distance = DB::selectOne(
                'SELECT ST_Distance(
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) / 1000.0 AS distance_km',
                [
                    (float) $workshop->longitude,
                    (float) $workshop->latitude,
                    $lng,
                    $lat,
                ],
            );
            $workshop->distance_km = $distance?->distance_km !== null
                ? round((float) $distance->distance_km, 2)
                : null;
        }

        return response()->json([
            'data' => new WorkshopResource($workshop->load('photos')),
        ]);
    }
}
