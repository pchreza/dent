<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FileAsset;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class MedicalFileStorage
{
    private const DISK = 'local';

    private const MAX_BYTES = 1048576;

    /** @param array{category: string, title?: string|null} $data */
    public function store(Tenant $tenant, Patient $patient, UploadedFile $file, array $data, User $actor): FileAsset
    {
        if ((int) $patient->tenant_id !== (int) $tenant->id) {
            throw ValidationException::withMessages(['file' => 'پروندهٔ بیمار در کلینیک فعال یافت نشد.']);
        }

        $size = $file->getSize();
        $realPath = $file->getRealPath();
        $extension = strtolower($file->extension());
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if ($size === false || $size === null || $size > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'حجم فایل نباید بیشتر از ۱ مگابایت باشد.']);
        }

        if ($realPath === false || ! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages(['file' => 'فایل تصویر معتبر نیست.']);
        }

        $dimensions = @getimagesize($realPath);
        if ($dimensions === false || ! isset($dimensions[0], $dimensions[1])) {
            throw ValidationException::withMessages(['file' => 'محتوای فایل تصویر قابل تشخیص نیست.']);
        }

        $path = 'medical/'.$tenant->id.'/'.$patient->id.'/'.Str::uuid().'.'.$extension;
        $storedPath = null;

        try {
            $storedPath = Storage::disk(self::DISK)->putFileAs(
                dirname($path),
                $file,
                basename($path),
            );

            if ($storedPath === false) {
                throw new \RuntimeException('ذخیرهٔ فایل پزشکی ناموفق بود.');
            }

            return DB::transaction(fn (): FileAsset => FileAsset::query()->create([
                'tenant_id' => $tenant->id,
                'owner_type' => Patient::class,
                'owner_id' => $patient->id,
                'category' => $data['category'],
                'disk' => self::DISK,
                'path' => $storedPath,
                'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                'size' => $size,
                'metadata_json' => [
                    'original_name' => $this->sanitizeName($file->getClientOriginalName(), $extension),
                    'title' => $this->sanitizeTitle($data['title'] ?? null),
                    'width' => (int) $dimensions[0],
                    'height' => (int) $dimensions[1],
                    'sha256' => hash_file('sha256', $realPath),
                ],
                'uploaded_by' => $actor->id,
            ]));
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk(self::DISK)->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function disk(): string
    {
        return self::DISK;
    }

    private function sanitizeName(string $name, string $extension): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\pL\pN._ -]+/u', '-', $name) ?: 'medical-file';
        $name = trim(Str::limit($name, 160, ''));

        return $name !== '' ? $name : 'medical-file.'.$extension;
    }

    private function sanitizeTitle(?string $title): ?string
    {
        if ($title === null || trim($title) === '') {
            return null;
        }

        $title = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($title)) ?: '';

        return trim(Str::limit($title, 120, '')) ?: null;
    }
}
