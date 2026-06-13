<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ProductsView->value);
    }

    public function view(User $user, Product $product): bool
    {
        return $product->company_id === $user->company_id
            && $user->can(PermissionEnum::ProductsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ProductsCreate->value);
    }

    public function update(User $user, Product $product): bool
    {
        return $product->company_id === $user->company_id
            && $user->can(PermissionEnum::ProductsUpdate->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->company_id === $user->company_id
            && $user->can(PermissionEnum::ProductsDelete->value);
    }
}
