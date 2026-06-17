<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Get the base query for DataTables server-side processing.
     */
    public function getDataTableQuery(): Builder
    {
        return User::query()
            ->with('customer:id,name')
            ->select([
                'users.id',
                'users.name',
                'users.username',
                'users.email',
                'users.customer_id',
                'users.is_active',
                'users.created_at',
            ]);
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return DB::transaction(fn () => User::create($data));
    }

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->update($data);

            return $user->fresh();
        });
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): void
    {
        DB::transaction(fn () => $user->delete());
    }
}
