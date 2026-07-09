<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $vehicles = $request->user()
            ->vehicles()
            ->latest()
            ->get();

        return VehicleResource::collection($vehicles);
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $request->user()->vehicles()->create($request->validated());

        return response()->json([
            'data' => new VehicleResource($vehicle),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Vehicle $vehicle): VehicleResource|JsonResponse
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        return new VehicleResource($vehicle);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): VehicleResource|JsonResponse
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $vehicle->update($request->validated());

        return new VehicleResource($vehicle);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $vehicle->delete();

        return response()->noContent();
    }
}
