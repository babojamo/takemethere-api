<?php

namespace App\Services\UserService;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IUserService
{
    public function delete(User $user): void;

    public function findOrFail(int|string $id): User;

    public function list(int $perPage = 15): LengthAwarePaginator;
    
    public function update(User $user, array $data): User;
}
