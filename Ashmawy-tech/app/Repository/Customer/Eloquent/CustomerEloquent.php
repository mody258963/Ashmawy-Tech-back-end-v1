<?php

namespace App\Repository\Customer\Eloquent;

use App\Models\Customer;
use App\Repository\Customer\CustomerRepository;

class CustomerEloquent implements CustomerRepository
{
    protected $model;

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->query()
            ->with(['branch', 'creator'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);

        return $record;
    }

    public function delete($id)
    {
        return $this->find($id)->delete();
    }
}
