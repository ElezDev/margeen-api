<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Product;
use App\Models\User;
use App\Support\Tenant;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ProductsView->value);
    }

    public function view(User $user, Product $product): bool
    {
        return Tenant::belongsToTenant($product)
            && $user->can(PermissionEnum::ProductsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ProductsCreate->value);
    }

    public function update(User $user, Product $product): bool
    {
        return Tenant::belongsToTenant($product)
            && $user->can(PermissionEnum::ProductsUpdate->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return Tenant::belongsToTenant($product)
            && $user->can(PermissionEnum::ProductsDelete->value);
    }
}
