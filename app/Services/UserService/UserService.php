<?php

namespace App\Services\UserService;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService implements IUserService
{
    public function list(int $perPage = 15): LengthAwarePaginator{
        return User::query()
            ->when(request('search'), function ($q, $s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%");
            })
            ->when(request('email'), function ($q, $email) {
                $q->where('email', $email);
            })
            ->latest('id')
            ->with('createdBy', 'updatedBy')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function findOrFail(int|string $id): User
    {
        return User::findOrFail($id);
    }

    public function update(User $user, array $data): User
    {
        $data['updated_by'] = auth()->id();
        $user->update($data);
        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
