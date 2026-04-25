<?php

namespace App\Repository\OrderNote\Eloquent;

use App\Models\OrderNote;
use App\Repository\OrderNote\OrderNoteRepository;
use Illuminate\Support\Collection;

class OrderNoteEloquent implements OrderNoteRepository
{
    public function __construct(protected OrderNote $model) {}

    public function forOrder(int $orderId): Collection
    {
        return $this->model->query()
            ->where('order_id', $orderId)
            ->with('user')
            ->orderByDesc('id')
            ->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function delete($id)
    {
        return $this->find($id)->delete();
    }
}
