<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Tenant;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::InvoicesView->value)
            || $user->can(PermissionEnum::InvoicesViewAll->value);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! Tenant::belongsToTenant($invoice)) {
            return false;
        }

        if (Tenant::isOverride() || $user->can(PermissionEnum::InvoicesViewAll->value)) {
            return true;
        }

        return $user->can(PermissionEnum::InvoicesView->value)
            && $invoice->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::InvoicesCreate->value);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return Tenant::belongsToTenant($invoice)
            && $user->hasPermissionTo(PermissionEnum::InvoicesCancel->value, 'api');
    }

    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
