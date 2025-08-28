<?php

namespace App\Repositories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Collection;

class AppUserRepository
{
    public function findByPhone(string $phone): ?AppUser
    {
        return AppUser::where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?AppUser
    {
        return AppUser::where('email', $email)->first();
    }

    public function findById(string $id): ?AppUser
    {
        return AppUser::find($id);
    }

    public function create(array $data): AppUser
    {
        return AppUser::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        
        return $user->update($data);
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        
        return $user->delete();
    }

    public function all(): Collection
    {
        return AppUser::all();
    }
}