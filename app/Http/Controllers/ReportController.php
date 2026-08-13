<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Support\AuditLogger;
use App\Support\AuthorizationService;
use App\Support\ReportCsvExporter;
use App\Support\ReportQueryService;
use App\Support\TenantContext;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuthorizationService $authorization,
        private readonly ReportQueryService $reports,
        private readonly ReportCsvExporter $csvExporter,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();
        $visibleReports = $this->reports->visibleDefinitions(request()->user(), $this->authorization);

        return view('reports.index', [
            'tenant' => $tenant,
            'reports' => $visibleReports,
            'title' => 'گزارش‌ها',
        ]);
    }

    public function show(ReportFilterRequest $request, string $report): View
    {
        $result = $this->buildResult($request, $report, 'viewed');

        return view('reports.show', [
            'tenant' => $this->tenantContext->require(),
            'report' => $result,
            'options' => $this->reports->options($this->tenantContext->require()),
            'title' => $result['definition']['title'],
        ]);
    }

    public function print(ReportFilterRequest $request, string $report): View
    {
        $result = $this->buildResult($request, $report, 'printed');

        return view('reports.print', [
            'tenant' => $this->tenantContext->require(),
            'report' => $result,
            'title' => $result['definition']['title'],
        ]);
    }

    public function export(ReportFilterRequest $request, string $report): StreamedResponse|Response
    {
        $result = $this->buildResult($request, $report, 'exported');

        if ($result['tooManyRows']) {
            return back()->withErrors(['report' => 'تعداد ردیف‌ها بیشتر از سقف ۵۰۰۰ مورد است؛ بازه یا فیلتر گزارش را محدود کنید.']);
        }

        $filename = 'dent-'.$report.'-'.now(config('app.timezone', 'Asia/Tehran'))->format('Ymd-His').'.csv';

        return $this->csvExporter->download($result, $filename);
    }

    /** @return array<string, mixed> */
    private function buildResult(ReportFilterRequest $request, string $report, string $action): array
    {
        $tenant = $this->tenantContext->require();
        $definition = $this->reports->definition($report);

        abort_unless($this->authorization->allows($request->user(), 'reports.view'), 403);
        abort_unless($this->authorization->allows($request->user(), $definition['permission']), 403);

        $result = $this->reports->run($report, $tenant, $request->validated());
        $this->auditLogger->record(
            action: 'report.'.$action,
            tenantId: $tenant->id,
            actorId: $request->user()?->id,
            subjectType: ReportQueryService::class,
            after: [
                'report' => $report,
                'filters' => $this->safeFilters($result['filters']),
                'row_count' => $result['totalRows'],
            ],
            reason: 'مرکز گزارش: '.$definition['title'],
        );

        return $result;
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function safeFilters(array $filters): array
    {
        return array_filter([
            'from' => $filters['from_input'] ?? null,
            'to' => $filters['to_input'] ?? null,
            'status' => $filters['status'] ?? null,
            'branch_id' => $filters['branch_id'] ?? null,
            'practitioner_id' => $filters['practitioner_id'] ?? null,
            'treatment_id' => $filters['treatment_id'] ?? null,
            'method' => $filters['method'] ?? null,
            'search_present' => filled($filters['search'] ?? null),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
