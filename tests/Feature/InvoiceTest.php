<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::query()->create(['code' => 'FIN-001', 'name' => 'کلینیک مالی', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'finance_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
        $this->patient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'مریم',
            'last_name' => 'محمدی',
            'national_id' => '0012345678',
            'mobile' => '09123333333',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_issue_invoice_and_record_partial_payment(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/invoices', [
                'patient_id' => $this->patient->id,
                'description' => 'عصب‌کشی دندان ۶',
                'quantity' => 2,
                'unit_price' => 100,
                'discount' => 10,
                'issue_date' => '2025-08-12',
            ]);

        $response->assertRedirect();
        $invoice = Invoice::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
        self::assertSame('190.00', number_format((float) $invoice->total, 2, '.', ''));

        $payment = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/invoices/'.$invoice->id.'/payments', [
                'amount' => 100,
                'method' => 'card',
                'reference' => 'POS-001',
                'paid_at' => '2025-08-12 10:00:00',
            ]);

        $payment->assertRedirect();
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'amount' => 100.00, 'method' => 'card']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'partially_paid', 'paid_total' => 100.00]);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/invoices', [
                'patient_id' => $this->patient->id,
                'description' => 'ویزیت',
                'quantity' => 1,
                'unit_price' => 50,
                'issue_date' => '2025-08-12',
            ]);
        $invoice = Invoice::query()->latest('id')->firstOrFail();

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/invoices/'.$invoice->id.'/payments', [
                'amount' => 51,
                'method' => 'cash',
                'paid_at' => '2025-08-12 10:00:00',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }
}
