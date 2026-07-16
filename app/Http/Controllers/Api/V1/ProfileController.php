<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Convert the authenticated user into a workshop owner.
     */
    public function becomeWorkshopOwner(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Admin) {
            return response()->json([
                'message' => 'Un administrador no puede convertirse en dueño de taller por este endpoint.',
            ], 422);
        }

        if ($user->role !== UserRole::WorkshopOwner) {
            $user->role = UserRole::WorkshopOwner;
            $user->save();
        }

        return response()->json([
            'message' => 'Ahora puedes registrar y administrar tus talleres.',
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
