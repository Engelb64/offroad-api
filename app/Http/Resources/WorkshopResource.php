<?php

namespace App\Http\Resources;

use App\Enums\WorkshopStatus;
use App\Services\WorkshopMediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WorkshopMediaService $media */
        $media = app(WorkshopMediaService::class);

        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'services' => $this->services ?? [],
            'schedule' => $this->schedule,
            'status' => $this->status instanceof WorkshopStatus
                ? $this->status->value
                : $this->status,
            'verified' => (bool) $this->verified,
            'photo_path' => $this->photo_path,
            'photo_url' => $media->url($this->photo_path),
            'photos' => $this->whenLoaded('photos', function () use ($media) {
                return $this->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $media->url($photo->path),
                    'path' => $photo->path,
                    'sort_order' => (int) $photo->sort_order,
                ])->values();
            }, []),
            'moderation_note' => $this->moderation_note,
            'moderation_at' => $this->moderation_at?->toISOString(),
            'moderated_by' => $this->moderated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
