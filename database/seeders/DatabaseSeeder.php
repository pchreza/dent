<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
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
    }
}
