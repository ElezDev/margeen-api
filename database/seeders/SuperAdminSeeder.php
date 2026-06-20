<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $platformCompany = Company::query()->firstOrCreate(
            ['document' => 'PLATFORM'],
            [
                'name' => 'Margeen Platform',
                'phone' => null,
                'address' => null,
                'default_margin_percent' => 0,
                'invoice_prefix' => 'PLT',
                'next_invoice_number' => 1,
                'is_active' => true,
                'notes' => 'Empresa interna del super administrador.',
            ]
        );

        $superAdmin = User::query()->updateOrCreate(
            [
                'company_id' => $platformCompany->id,
                'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@margeen.com'),
            ],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin Margeen'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
                'document' => 'SUPERADMIN',
                'phone' => null,
                'address' => null,
                'notes' => 'Usuario plataforma — no pertenece a un cliente.',
                'is_active' => true,
            ]
        );

        if (! $superAdmin->hasRole(RoleEnum::SuperAdmin->value)) {
            $superAdmin->syncRoles([RoleEnum::SuperAdmin->value]);
        }
    }
}
