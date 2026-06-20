<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Client;
use App\Models\User;
use App\Support\Tenant;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ClientsView->value);
    }

    public function view(User $user, Client $client): bool
    {
        return Tenant::belongsToTenant($client)
            && $user->can(PermissionEnum::ClientsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ClientsCreate->value);
    }

    public function update(User $user, Client $client): bool
    {
        return Tenant::belongsToTenant($client)
            && $user->can(PermissionEnum::ClientsUpdate->value);
    }

    public function delete(User $user, Client $client): bool
    {
        return Tenant::belongsToTenant($client)
            && $user->can(PermissionEnum::ClientsDelete->value);
    }
}
