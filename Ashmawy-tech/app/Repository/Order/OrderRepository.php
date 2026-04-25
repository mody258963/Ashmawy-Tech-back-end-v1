<?php

namespace App\Repository\Order;

interface OrderRepository
{
    public function all();

    public function paginate(int $perPage = 15);

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}
