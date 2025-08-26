<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function findById(string $id): ?Customer
    {
        return Customer::find($id);
    }

    public function findByUserId(string $userId): ?Customer
    {
        return Customer::where('user_id', $userId)->first();
    }

    public function update(string $id, array $data): bool
    {
        $customer = $this->findById($id);
        if (!$customer) {
            return false;
        }
        
        return $customer->update($data);
    }

    public function delete(string $id): bool
    {
        $customer = $this->findById($id);
        if (!$customer) {
            return false;
        }
        
        return $customer->delete();
    }

    public function all(): Collection
    {
        return Customer::all();
    }
}