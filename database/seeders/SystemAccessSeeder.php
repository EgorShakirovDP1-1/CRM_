<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SystemAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'organization.manage' => ['organization', 'manage'], 'roles.assign.superadmin' => ['access', 'assign'],
            'roles.assign.admin' => ['access', 'assign'], 'roles.assign.staff' => ['access', 'assign'],
            'employees.manage' => ['team', 'manage'], 'schedules.manage' => ['team', 'manage'],
            'tasks.view-own' => ['tasks', 'view'], 'tasks.manage' => ['tasks', 'manage'], 'reports.view' => ['reports', 'view'],
            'audit.view' => ['audit', 'view'], 'crm.manage' => ['crm', 'manage'], 'booking.manage' => ['booking', 'manage'],
            'communications.manage' => ['communications', 'manage'], 'documents.manage' => ['documents', 'manage'],
            'finance.manage' => ['finance', 'manage'], 'inventory.manage' => ['inventory', 'manage'],
            'risk.manage' => ['risk', 'manage'], 'privacy.manage' => ['privacy', 'manage'],
        ];
        foreach ($permissions as $code => [$module, $action]) {
            Permission::updateOrCreate(['code' => $code], compact('module', 'action') + ['description' => $code]);
        }
        $names = [
            'owner' => ['Владелец', 'Owner'], 'superadmin' => ['Суперадминистратор', 'Super administrator'],
            'admin' => ['Администратор', 'Administrator'], 'manager' => ['Менеджер', 'Manager'],
            'worker' => ['Работник', 'Worker'], 'accountant' => ['Бухгалтер', 'Accountant'],
            'supply_manager' => ['Менеджер поставок', 'Supply manager'],
        ];
        foreach (RoleCode::cases() as $roleCode) {
            [$ru, $en] = $names[$roleCode->value];
            Role::updateOrCreate(['code' => $roleCode->value], ['name_ru' => $ru, 'name_en' => $en, 'rank' => $roleCode->rank(), 'is_system' => true]);
        }
        $all = array_keys($permissions);
        $matrix = [
            'owner' => $all,
            'superadmin' => array_values(array_diff($all, ['organization.manage', 'roles.assign.superadmin'])),
            'admin' => ['roles.assign.staff', 'employees.manage', 'schedules.manage', 'tasks.manage', 'reports.view', 'crm.manage', 'booking.manage', 'communications.manage', 'documents.manage', 'privacy.manage'],
            'manager' => ['schedules.manage', 'tasks.manage', 'reports.view', 'crm.manage', 'booking.manage', 'communications.manage', 'risk.manage'],
            'worker' => ['tasks.view-own', 'booking.manage', 'communications.manage'],
            'accountant' => ['tasks.view-own', 'finance.manage', 'reports.view', 'documents.manage'],
            'supply_manager' => ['tasks.view-own', 'inventory.manage'],
        ];
        foreach ($matrix as $role => $codes) {
            Role::where('code', $role)->firstOrFail()->permissions()->sync(
                Permission::whereIn('code', $codes)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['allowed' => true]])->all(),
            );
        }
    }
}
