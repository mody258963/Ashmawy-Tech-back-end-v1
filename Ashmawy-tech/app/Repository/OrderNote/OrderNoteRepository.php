<?php

namespace App\Repository\OrderNote;

use Illuminate\Support\Collection;

interface OrderNoteRepository
{
    public function forOrder(int $orderId): Collection;

    public function find($id);

    public function create(array $data);

    public function delete($id);
}
