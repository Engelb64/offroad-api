<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\WorkshopStatus;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkshopDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'user@offroad.test'],
            [
                'name' => 'Usuario Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ],
        );

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@offroad.test'],
            [
                'name' => 'Dueño Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::WorkshopOwner,
            ],
        );

        Workshop::query()->updateOrCreate(
            ['slug' => 'taller-4x4-caracas'],
            [
                'owner_id' => $owner->id,
                'name' => 'Taller 4x4 Caracas',
                'description' => 'Suspension, diferencial y preparacion offroad.',
                'phone' => '04141234567',
                'email' => 'taller4x4@offroad.test',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'country' => 'VE',
                'address' => 'Av. Principal, Los Ruices',
                'services' => ['suspension', 'diferencial', 'alineacion'],
                'status' => WorkshopStatus::Published,
                'verified' => true,
            ],
        );

        Workshop::query()->updateOrCreate(
            ['slug' => 'garage-maracay-offroad'],
            [
                'owner_id' => $owner->id,
                'name' => 'Garage Maracay Offroad',
                'description' => 'Pendiente de revision por admin.',
                'phone' => '04149876543',
                'city' => 'Maracay',
                'state' => 'Aragua',
                'country' => 'VE',
                'services' => ['frenos', 'aceite'],
                'status' => WorkshopStatus::PendingReview,
                'verified' => false,
            ],
        );

        Workshop::query()->updateOrCreate(
            ['slug' => 'borrador-taller-demo'],
            [
                'owner_id' => $owner->id,
                'name' => 'Borrador Taller Demo',
                'description' => 'Aun no enviado a revision.',
                'city' => 'Valencia',
                'country' => 'VE',
                'services' => ['diagnostico'],
                'status' => WorkshopStatus::Draft,
                'verified' => false,
            ],
        );
    }
}
