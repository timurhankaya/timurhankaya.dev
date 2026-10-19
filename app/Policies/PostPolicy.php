<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Post $post): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->is_admin;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->is_admin;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return false;
    }
}
