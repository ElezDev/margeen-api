<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Request;

class Tenant
{
    public static function resolve(Request $request): int
    {
        $user = $request->user();

        if ($user?->isSuperAdmin() && $request->header('X-Company-Id')) {
            $companyId = (int) $request->header('X-Company-Id');
            $company = Company::query()->find($companyId);

            if (! $company || $company->document === 'PLATFORM') {
                abort(422, 'Empresa de trabajo inválida.');
            }

            $request->attributes->set('company_id', $companyId);
            $request->attributes->set('tenant_override', true);

            return $companyId;
        }

        $request->attributes->set('company_id', $user->company_id);
        $request->attributes->set('tenant_override', false);

        return (int) $user->company_id;
    }

    public static function companyId(?Request $request = null): int
    {
        $request ??= request();

        if ($request->attributes->has('company_id')) {
            return (int) $request->attributes->get('company_id');
        }

        return self::resolve($request);
    }

    public static function isOverride(?Request $request = null): bool
    {
        $request ??= request();

        return (bool) $request->attributes->get('tenant_override', false);
    }

    public static function belongsToTenant(object $model, ?Request $request = null): bool
    {
        return isset($model->company_id)
            && (int) $model->company_id === self::companyId($request);
    }
}
