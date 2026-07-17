<?php

namespace App\Services;

use App\Models\Workshop;
use App\Models\WorkshopPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Almacenamiento de fotos de taller.
 *
 * Paths relativos al disco MEDIA_DISK (public hoy, s3 mañana):
 *   users/{ownerId}/workshops/{workshopId}/cover.{ext}
 *   users/{ownerId}/workshops/{workshopId}/gallery/{uuid}.{ext}
 *
 * La carpeta del usuario se crea de forma implícita al guardar el primer archivo
 * (local y S3). No se pre-crea al registrar el usuario.
 */
class WorkshopMediaService
{
    public function diskName(): string
    {
        return (string) config('media.disk', 'public');
    }

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk($this->diskName())->url($path);
    }

    public function storeCover(Workshop $workshop, UploadedFile $file): string
    {
        $this->assertWorkshopHasOwner($workshop);

        if ($workshop->photo_path) {
            $this->deletePath($workshop->photo_path);
        }

        $extension = $this->extension($file);
        $path = $this->workshopRoot($workshop).'/cover.'.$extension;

        $stored = Storage::disk($this->diskName())->putFileAs(
            dirname($path),
            $file,
            basename($path),
            ['visibility' => 'public'],
        );

        if ($stored === false) {
            throw new RuntimeException('No se pudo guardar la foto principal.');
        }

        $workshop->photo_path = $stored;
        $workshop->save();

        return $stored;
    }

    public function deleteCover(Workshop $workshop): void
    {
        if ($workshop->photo_path) {
            $this->deletePath($workshop->photo_path);
            $workshop->photo_path = null;
            $workshop->save();
        }
    }

    public function storeGalleryPhoto(Workshop $workshop, UploadedFile $file): WorkshopPhoto
    {
        $this->assertWorkshopHasOwner($workshop);

        $count = $workshop->photos()->count();
        if ($count >= 3) {
            throw new RuntimeException('Solo se permiten 3 fotos secundarias.');
        }

        $extension = $this->extension($file);
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = $this->workshopRoot($workshop).'/gallery';

        $stored = Storage::disk($this->diskName())->putFileAs(
            $directory,
            $file,
            $filename,
            ['visibility' => 'public'],
        );

        if ($stored === false) {
            throw new RuntimeException('No se pudo guardar la foto secundaria.');
        }

        return $workshop->photos()->create([
            'path' => $stored,
            'sort_order' => $count,
        ]);
    }

    public function deleteGalleryPhoto(WorkshopPhoto $photo): void
    {
        $this->deletePath($photo->path);
        $workshopId = $photo->workshop_id;
        $photo->delete();

        $remaining = WorkshopPhoto::query()
            ->where('workshop_id', $workshopId)
            ->orderBy('sort_order')
            ->get();

        foreach ($remaining as $index => $item) {
            if ((int) $item->sort_order !== $index) {
                $item->update(['sort_order' => $index]);
            }
        }
    }

    public function workshopRoot(Workshop $workshop): string
    {
        $ownerId = (int) $workshop->owner_id;

        return "users/{$ownerId}/workshops/{$workshop->id}";
    }

    private function deletePath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $disk = Storage::disk($this->diskName());
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function extension(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');

        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $allowed = config('media.allowed_extensions', ['jpg', 'png', 'webp']);

        if (! in_array($ext, $allowed, true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        return $ext;
    }

    private function assertWorkshopHasOwner(Workshop $workshop): void
    {
        if ($workshop->owner_id === null) {
            throw new RuntimeException('El taller no tiene dueño; no se puede organizar la carpeta de usuario.');
        }
    }
}
