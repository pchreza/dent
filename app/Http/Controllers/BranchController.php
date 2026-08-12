<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Models\Branch;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class BranchController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();

        return view('branches.index', [
            'tenant' => $tenant,
            'branches' => $tenant->branches()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('branches.create', ['tenant' => $this->tenantContext->require()]);
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();

        $branch = $tenant->branches()->create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        $this->auditLogger->record(
            action: 'branch.created',
            tenantId: $tenant->id,
            subjectType: Branch::class,
            subjectId: $branch->id,
            after: $branch->toArray(),
            reason: 'ایجاد شعبه از پنل مدیریت کلینیک',
        );

        return redirect()->route('branches.index')->with('status', 'شعبه با موفقیت ایجاد شد.');
    }
}
