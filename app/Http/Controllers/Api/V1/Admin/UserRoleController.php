<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function update(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->role = UserRole::from($request->validated('role'));
        $user->save();

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'user' => new UserResource($user),
        ]);
    }
}
