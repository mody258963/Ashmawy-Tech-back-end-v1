<?php

namespace App\Repository\FollowUp\Eloquent;

use App\Models\FollowUp;
use App\Repository\FollowUp\FollowUpRepository;

class FollowUpEloquent implements FollowUpRepository
{
    public function __construct(protected FollowUp $model) {}

    public function all()
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->query()
            ->with(['customer', 'moderator'])
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
