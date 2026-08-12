<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MedicalConditionDefinition;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TreatmentStageDefinition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = [
            ['module' => 'platform', 'action' => 'view', 'label' => 'مشاهدهٔ سامانه'],
            ['module' => 'platform', 'action' => 'manage_settings', 'label' => 'مدیریت تنظیمات سامانه'],
            ['module' => 'tenants', 'action' => 'view', 'label' => 'مشاهدهٔ کلینیک‌ها'],
            ['module' => 'tenants', 'action' => 'create', 'label' => 'ایجاد کلینیک'],
            ['module' => 'tenants', 'action' => 'update', 'label' => 'ویرایش کلینیک'],
            ['module' => 'tenants', 'action' => 'archive', 'label' => 'بایگانی کلینیک'],
            ['module' => 'users', 'action' => 'view', 'label' => 'مشاهدهٔ کاربران'],
            ['module' => 'users', 'action' => 'create', 'label' => 'ایجاد کاربر'],
            ['module' => 'users', 'action' => 'update', 'label' => 'ویرایش کاربر'],
            ['module' => 'users', 'action' => 'archive', 'label' => 'بایگانی کاربر'],
            ['module' => 'branches', 'action' => 'view', 'label' => 'مشاهدهٔ شعبه‌ها'],
            ['module' => 'branches', 'action' => 'create', 'label' => 'ایجاد شعبه'],
            ['module' => 'branches', 'action' => 'update', 'label' => 'ویرایش شعبه'],
            ['module' => 'branches', 'action' => 'archive', 'label' => 'بایگانی شعبه'],
            ['module' => 'patients', 'action' => 'view', 'label' => 'مشاهدهٔ بیماران'],
            ['module' => 'patients', 'action' => 'create', 'label' => 'ایجاد بیمار'],
            ['module' => 'patients', 'action' => 'update', 'label' => 'ویرایش بیمار'],
            ['module' => 'patients', 'action' => 'archive', 'label' => 'بایگانی بیمار'],
            ['module' => 'patients', 'action' => 'print', 'label' => 'چاپ پروندهٔ بیمار'],
            ['module' => 'patients', 'action' => 'export', 'label' => 'خروجی بیماران'],
            ['module' => 'clinical', 'action' => 'view', 'label' => 'مشاهدهٔ پروندهٔ درمانی'],
            ['module' => 'clinical', 'action' => 'create', 'label' => 'ثبت سابقهٔ درمانی'],
            ['module' => 'clinical', 'action' => 'update', 'label' => 'ویرایش سابقهٔ درمانی'],
            ['module' => 'clinical', 'action' => 'view_private_notes', 'label' => 'مشاهدهٔ یادداشت خصوصی'],
            ['module' => 'dentistry', 'action' => 'view', 'label' => 'مشاهدهٔ نمودار دندان'],
            ['module' => 'dentistry', 'action' => 'update', 'label' => 'ویرایش نمودار دندان'],
            ['module' => 'treatments', 'action' => 'view', 'label' => 'مشاهدهٔ طرح درمان'],
            ['module' => 'treatments', 'action' => 'create', 'label' => 'ایجاد طرح درمان'],
            ['module' => 'treatments', 'action' => 'update', 'label' => 'ویرایش طرح درمان'],
            ['module' => 'scheduling', 'action' => 'view', 'label' => 'مشاهدهٔ تقویم'],
            ['module' => 'scheduling', 'action' => 'create', 'label' => 'ثبت نوبت'],
            ['module' => 'scheduling', 'action' => 'update', 'label' => 'ویرایش نوبت'],
            ['module' => 'scheduling', 'action' => 'cancel', 'label' => 'لغو نوبت'],
            ['module' => 'finance', 'action' => 'view', 'label' => 'مشاهدهٔ امور مالی'],
            ['module' => 'finance', 'action' => 'create', 'label' => 'ثبت فاکتور/پرداخت'],
            ['module' => 'finance', 'action' => 'update', 'label' => 'ویرایش امور مالی'],
            ['module' => 'finance', 'action' => 'print', 'label' => 'چاپ رسید'],
            ['module' => 'finance', 'action' => 'export', 'label' => 'خروجی مالی'],
            ['module' => 'reports', 'action' => 'view', 'label' => 'مشاهدهٔ گزارش‌ها'],
            ['module' => 'reports', 'action' => 'export', 'label' => 'خروجی گزارش‌ها'],
            ['module' => 'audit', 'action' => 'view', 'label' => 'مشاهدهٔ ممیزی'],
            ['module' => 'support', 'action' => 'enter_tenant', 'label' => 'ورود پشتیبانی به کلینیک'],
        ];

        $permissionModels = [];
        foreach ($permissions as $permission) {
            $permissionModels[] = Permission::query()->updateOrCreate(
                ['code' => $permission['module'].'.'.$permission['action']],
                $permission,
            );
        }

        $roles = [
            ['code' => 'superadmin', 'name' => 'سوپرادمین', 'permissions' => 'all'],
            ['code' => 'clinic_manager', 'name' => 'مدیر کلینیک', 'permissions' => 'all'],
            ['code' => 'doctor', 'name' => 'پزشک', 'permissions' => ['patients.view', 'clinical.view', 'clinical.create', 'clinical.update', 'dentistry.view', 'dentistry.update', 'treatments.view', 'treatments.create', 'treatments.update', 'scheduling.view']],
            ['code' => 'receptionist', 'name' => 'منشی', 'permissions' => ['patients.view', 'patients.create', 'patients.update', 'patients.print', 'clinical.view', 'scheduling.view', 'scheduling.create', 'scheduling.update', 'scheduling.cancel', 'finance.view', 'finance.create', 'finance.print']],
            ['code' => 'patient', 'name' => 'بیمار', 'permissions' => ['scheduling.view', 'clinical.view', 'treatments.view', 'finance.view']],
        ];

        foreach ($roles as $roleData) {
            $role = Role::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $roleData['code']],
                ['name' => $roleData['name'], 'is_system' => true],
            );

            $codes = $roleData['permissions'] === 'all'
                ? array_map(static fn (Permission $permission): string => $permission->code, $permissionModels)
                : $roleData['permissions'];

            $role->permissions()->sync(
                Permission::query()->whereIn('code', $codes)->pluck('permissions.id')->mapWithKeys(
                    static fn (int $id): array => [$id => ['allowed' => true]],
                )->all(),
            );
        }

        $conditions = [
            ['code' => 'diabetes', 'name' => 'دیابت'],
            ['code' => 'hypertension', 'name' => 'فشار خون بالا'],
            ['code' => 'heart_disease', 'name' => 'بیماری قلبی'],
            ['code' => 'pregnancy', 'name' => 'بارداری'],
            ['code' => 'bleeding_disorder', 'name' => 'اختلال انعقاد خون'],
        ];

        foreach ($conditions as $condition) {
            MedicalConditionDefinition::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $condition['code']],
                ['name' => $condition['name'], 'is_system' => true, 'is_active' => true],
            );
        }

        $stages = [
            ['code' => 'consultation', 'name' => 'معاینه و تشخیص', 'category' => 'diagnosis', 'sort_order' => 10, 'color' => '#0891B2'],
            ['code' => 'filling', 'name' => 'ترمیم و پرکردن', 'category' => 'restorative', 'sort_order' => 20, 'color' => '#0E7490'],
            ['code' => 'root_canal', 'name' => 'عصب‌کشی', 'category' => 'endodontics', 'sort_order' => 30, 'color' => '#7C3AED'],
            ['code' => 'crown', 'name' => 'روکش', 'category' => 'prosthodontics', 'sort_order' => 40, 'color' => '#DB2777'],
            ['code' => 'implant', 'name' => 'ایمپلنت', 'category' => 'implant', 'sort_order' => 50, 'color' => '#EA580C'],
            ['code' => 'surgery', 'name' => 'جراحی', 'category' => 'surgery', 'sort_order' => 60, 'color' => '#B42318'],
            ['code' => 'follow_up', 'name' => 'پیگیری و مراقبت', 'category' => 'follow_up', 'sort_order' => 70, 'color' => '#059669'],
        ];

        foreach ($stages as $stage) {
            TreatmentStageDefinition::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $stage['code']],
                [...$stage, 'is_active' => true],
            );
        }
    }
}
