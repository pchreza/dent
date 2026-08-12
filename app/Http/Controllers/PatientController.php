<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PatientController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $tenant = $this->tenantContext->require();
        $search = trim((string) $request->query('q', ''));

        $patients = $tenant->patients()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_no', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('patients.index', compact('tenant', 'patients', 'search'));
    }

    public function show(int $patientId): View
    {
        $tenant = $this->tenantContext->require();
        $patient = $tenant->patients()
            ->with([
                'conditions.condition',
                'allergies',
                'medications',
                'notes.author',
                'clinicalFieldValues.definition',
                'treatmentPlans.items.stage',
                'treatmentPlans.items.statusHistory',
            ])
            ->findOrFail($patientId);
        $clinicalFieldDefinitions = $tenant->clinicalFieldDefinitions()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        $clinicalFieldValues = $patient->clinicalFieldValues
            ->keyBy('clinical_field_definition_id');

        return view('patients.show', compact(
            'tenant',
            'patient',
            'clinicalFieldDefinitions',
            'clinicalFieldValues',
        ));
    }
}
