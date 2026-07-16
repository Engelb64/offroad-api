<?php

namespace App\Enums;

enum WorkshopStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingReview => 'En revisión',
            self::Published => 'Publicado',
            self::Suspended => 'Suspendido',
        };
    }

    public function isVisibleInDirectory(): bool
    {
        return $this === self::Published;
    }
}
