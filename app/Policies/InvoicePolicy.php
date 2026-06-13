<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::InvoicesView->value)
            || $user->can(PermissionEnum::InvoicesViewAll->value);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($invoice->company_id !== $user->company_id) {
            return false;
        }

        if ($user->can(PermissionEnum::InvoicesViewAll->value)) {
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
        return $invoice->company_id === $user->company_id
            && $user->hasPermissionTo(PermissionEnum::InvoicesCancel->value, 'api');
    }

    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
