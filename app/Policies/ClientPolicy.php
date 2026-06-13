<?php

namespace App\Policies;

use App\Enums\Permission as PermissionEnum;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ClientsView->value);
    }

    public function view(User $user, Client $client): bool
    {
        return $client->company_id === $user->company_id
            && $user->can(PermissionEnum::ClientsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ClientsCreate->value);
    }

    public function update(User $user, Client $client): bool
    {
        return $client->company_id === $user->company_id
            && $user->can(PermissionEnum::ClientsUpdate->value);
    }

    public function delete(User $user, Client $client): bool
    {
        return $client->company_id === $user->company_id
            && $user->can(PermissionEnum::ClientsDelete->value);
    }
}
