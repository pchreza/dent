<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalFileRequest;
use App\Models\FileAsset;
use App\Models\Patient;
use App\Support\AuditLogger;
use App\Support\MedicalFileStorage;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MedicalFileController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly MedicalFileStorage $storage,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(StoreMedicalFileRequest $request, int $patientId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $patient = $this->patient($patientId);
        $asset = $this->storage->store($tenant, $patient, $request->file('file'), $request->validated(), $request->user());

        $this->auditLogger->record(
            action: 'medical_file.uploaded',
            tenantId: $tenant->id,
            actorId: $request->user()->id,
            subjectType: FileAsset::class,
            subjectId: $asset->id,
            after: [
                'patient_id' => $patient->id,
                'category' => $asset->category,
                'mime_type' => $asset->mime_type,
                'size' => $asset->size,
            ],
            reason: 'آپلود فایل پزشکی بیمار',
        );

        return redirect()->route('patients.show', ['patientId' => $patient->id])->with('status', 'فایل پزشکی با موفقیت ذخیره شد.');
    }

    public function download(Request $request, int $patientId, int $fileId): StreamedResponse
    {
        $tenant = $this->tenantContext->require();
        $patient = $this->patient($patientId);
        $asset = $this->activeFile($tenant->id, $patient->id, $fileId);

        abort_unless($request->user() !== null, 403);
        $this->auditLogger->record(
            action: 'medical_file.downloaded',
            tenantId: $tenant->id,
            actorId: $request->user()->id,
            subjectType: FileAsset::class,
            subjectId: $asset->id,
            after: ['patient_id' => $patient->id, 'category' => $asset->category],
            reason: 'دانلود فایل پزشکی بیمار',
        );

        return response()->streamDownload(
            function () use ($asset): void {
                $stream = $this->storageStream($asset);
                fpassthru($stream);
                fclose($stream);
            },
            $asset->displayName(),
            [
                'Content-Type' => $asset->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'attachment; filename="medical-file-'.$asset->id.'.'.pathinfo($asset->displayName(), PATHINFO_EXTENSION).'"',
            ],
        );
    }

    public function archive(Request $request, int $patientId, int $fileId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $patient = $this->patient($patientId);
        $asset = $this->activeFile($tenant->id, $patient->id, $fileId);
        $asset->forceFill(['deleted_at' => now()])->save();

        $this->auditLogger->record(
            action: 'medical_file.archived',
            tenantId: $tenant->id,
            actorId: $request->user()?->id,
            subjectType: FileAsset::class,
            subjectId: $asset->id,
            before: ['category' => $asset->category, 'deleted_at' => null],
            after: ['patient_id' => $patient->id, 'deleted_at' => $asset->deleted_at?->toIso8601String()],
            reason: 'بایگانی فایل پزشکی بیمار',
        );

        return redirect()->route('patients.show', ['patientId' => $patient->id])->with('status', 'فایل پزشکی بایگانی شد.');
    }

    private function patient(int $patientId): Patient
    {
        return $this->tenantContext->require()->patients()->findOrFail($patientId);
    }

    private function activeFile(int $tenantId, int $patientId, int $fileId): FileAsset
    {
        return FileAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('owner_type', Patient::class)
            ->where('owner_id', $patientId)
            ->where('disk', $this->storage->disk())
            ->whereNull('deleted_at')
            ->findOrFail($fileId);
    }

    /** @return resource */
    private function storageStream(FileAsset $asset)
    {
        $stream = Storage::disk($asset->disk)->readStream($asset->path);

        abort_if($stream === false, 404, 'فایل پزشکی در Storage یافت نشد.');

        return $stream;
    }
}
