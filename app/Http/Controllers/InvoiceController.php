<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();

        return view('invoices.index', [
            'tenant' => $tenant,
            'invoices' => Invoice::query()->with('patient')->where('tenant_id', $tenant->id)->latest('issue_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $tenant = $this->tenantContext->require();

        return view('invoices.create', [
            'tenant' => $tenant,
            'patients' => $tenant->patients()->where('status', 'active')->orderBy('last_name')->get(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $patient = $tenant->patients()->findOrFail($data['patient_id']);
        $quantity = (int) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $subtotal = $quantity * $unitPrice;
        $discount = min($subtotal, (float) ($data['discount'] ?? 0));
        $total = $subtotal - $discount;

        $invoice = DB::transaction(function () use ($tenant, $request, $patient, $data, $quantity, $unitPrice, $subtotal, $discount, $total): Invoice {
            $invoice = Invoice::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'invoice_no' => $this->nextInvoiceNumber($tenant->id),
                'status' => 'issued',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_total' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            InvoiceItem::query()->create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'description' => $data['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $subtotal,
            ]);

            return $invoice;
        });

        $this->auditLogger->record(
            action: 'invoice.created',
            tenantId: $tenant->id,
            subjectType: Invoice::class,
            subjectId: $invoice->id,
            after: ['invoice_no' => $invoice->invoice_no, 'total' => $invoice->total],
            reason: 'ایجاد فاکتور بیمار',
        );

        return redirect()->route('invoices.show', ['invoiceId' => $invoice->id])->with('status', 'فاکتور ایجاد شد.');
    }

    public function show(int $invoiceId): View
    {
        $tenant = $this->tenantContext->require();
        $invoice = Invoice::query()->with(['patient', 'items', 'payments'])->where('tenant_id', $tenant->id)->findOrFail($invoiceId);

        return view('invoices.show', compact('tenant', 'invoice'));
    }

    public function storePayment(StorePaymentRequest $request, int $invoiceId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($tenant, $request, $data, $invoiceId): Invoice {
            $invoice = Invoice::query()->where('tenant_id', $tenant->id)->lockForUpdate()->findOrFail($invoiceId);
            $remaining = (float) $invoice->total - (float) $invoice->paid_total;
            $amount = (float) $data['amount'];

            if ($amount > $remaining) {
                abort(422, 'مبلغ پرداخت بیشتر از ماندهٔ فاکتور است.');
            }

            Payment::query()->create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $invoice->forceFill([
                'paid_total' => (float) $invoice->paid_total + $amount,
                'status' => ((float) $invoice->paid_total + $amount) >= (float) $invoice->total ? 'paid' : 'partially_paid',
                'updated_by' => $request->user()->id,
            ])->save();

            return $invoice->fresh();
        });

        $this->auditLogger->record(
            action: 'invoice.payment_recorded',
            tenantId: $tenant->id,
            subjectType: Payment::class,
            subjectId: $invoice->payments()->latest('id')->value('id'),
            after: ['invoice_id' => $invoice->id, 'paid_total' => $invoice->paid_total],
            reason: 'ثبت پرداخت فاکتور',
        );

        return back()->with('status', 'پرداخت ثبت شد.');
    }

    private function nextInvoiceNumber(int $tenantId): string
    {
        $last = Invoice::query()->where('tenant_id', $tenantId)->latest('id')->value('invoice_no');
        $sequence = $last !== null && preg_match('/(\d+)$/', $last, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'INV-'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT);
    }
}
