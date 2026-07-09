<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceRecordRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceRecordRequest;
use App\Http\Resources\MaintenanceRecordResource;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceRecordController extends Controller
{
    public function index(Request $request, Vehicle $vehicle): AnonymousResourceCollection|JsonResponse
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $records = $vehicle->maintenanceRecords()
            ->latest('performed_at')
            ->get();

        return MaintenanceRecordResource::collection($records);
    }

    public function store(StoreMaintenanceRecordRequest $request, Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $record = $vehicle->maintenanceRecords()->create($request->validated());

        return response()->json([
            'data' => new MaintenanceRecordResource($record),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, MaintenanceRecord $maintenanceRecord): MaintenanceRecordResource|JsonResponse
    {
        if ($maintenanceRecord->vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        return new MaintenanceRecordResource($maintenanceRecord);
    }

    public function update(UpdateMaintenanceRecordRequest $request, MaintenanceRecord $maintenanceRecord): MaintenanceRecordResource|JsonResponse
    {
        if ($maintenanceRecord->vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $maintenanceRecord->update($request->validated());

        return new MaintenanceRecordResource($maintenanceRecord);
    }

    public function destroy(Request $request, MaintenanceRecord $maintenanceRecord): JsonResponse
    {
        if ($maintenanceRecord->vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        $maintenanceRecord->delete();

        return response()->noContent();
    }
}
