<?php

namespace App\Domain\Support\Actions;

use App\Domain\Media\Exceptions\RejectedUpload;
use App\Domain\Media\Support\UploadInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Throwable;

/**
 * Attachments on support tickets (ADR-0017): PDF, JPEG or PNG up to 5 MB, three per message, sniffed by their
 * bytes. Images are re-encoded (which drops anything hidden in them, and their location data); PDFs are kept as
 * they are. Files live on the private disk and are only served to staff or to the requester who proved the ticket.
 */
final class StoreAttachments
{
    public const DISK = 'local';

    public const MAX_BYTES = 5 * 1024 * 1024;

    public const MAX_FILES = 3;

    private const ALLOWED = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(private readonly UploadInspector $inspector) {}

    /** Why a file cannot be attached, or null when it can. */
    public function problem(UploadedFile $upload): ?string
    {
        if (! $upload->isValid() || $upload->getSize() > self::MAX_BYTES) {
            return __('support.validation.file_size', ['max' => 5]);
        }

        try {
            $inspected = $this->inspector->inspect((string) $upload->getRealPath(), (string) $upload->getClientOriginalName());
        } catch (RejectedUpload) {
            return __('support.validation.file_type');
        }

        return array_key_exists($inspected->mime, self::ALLOWED) ? null : __('support.validation.file_type');
    }

    /**
     * Stores checked uploads under the ticket's folder.
     *
     * @param  list<UploadedFile>  $uploads
     * @return list<array{path: string, name: string, size: int, mime: string}>
     */
    public function store(string $ticketId, array $uploads): array
    {
        $stored = [];
        foreach (array_slice($uploads, 0, self::MAX_FILES) as $upload) {
            $inspected = $this->inspector->inspect((string) $upload->getRealPath(), (string) $upload->getClientOriginalName());
            $extension = self::ALLOWED[$inspected->mime] ?? null;
            if ($extension === null) {
                continue;
            }

            $contents = $inspected->mime === 'application/pdf'
                ? (string) file_get_contents((string) $upload->getRealPath())
                : $this->reencode((string) $upload->getRealPath(), $extension);

            $path = "support/{$ticketId}/".Str::random(24).'.'.$extension;
            Storage::disk(self::DISK)->put($path, $contents);
            $stored[] = [
                'path' => $path,
                'name' => Str::limit(Str::ascii(pathinfo((string) $upload->getClientOriginalName(), PATHINFO_FILENAME)), 80, '').'.'.$extension,
                'size' => strlen($contents),
                'mime' => $inspected->mime,
            ];
        }

        return $stored;
    }

    public static function deleteFolder(string $ticketId): void
    {
        Storage::disk(self::DISK)->deleteDirectory("support/{$ticketId}");
    }

    private function reencode(string $source, string $extension): string
    {
        $target = tempnam(sys_get_temp_dir(), 'sup').'.'.$extension;
        try {
            $image = Image::useImageDriver(ImageDriver::Gd)->loadFile($source);
            $maxEdge = 3000;
            if (max($image->getWidth(), $image->getHeight()) > $maxEdge) {
                $image->getWidth() >= $image->getHeight() ? $image->width($maxEdge) : $image->height($maxEdge);
            }
            $image->quality($extension === 'png' ? 90 : 85)->save($target);

            return (string) file_get_contents($target);
        } catch (Throwable) {
            throw RejectedUpload::because('corrupt');
        } finally {
            @unlink($target);
        }
    }
}
