<?php

namespace App\Policies;

use App\Models\ContactRequest;
use App\Models\User;

/** Visitors create requests through the public form; only administrators read or delete them. */
class ContactRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, ContactRequest $request): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContactRequest $request): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, ContactRequest $request): bool
    {
        return $user->is_admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin;
    }
}
