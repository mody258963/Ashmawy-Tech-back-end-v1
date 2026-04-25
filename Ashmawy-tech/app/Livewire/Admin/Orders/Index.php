<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->ordersQuery()->paginate(15),
        ]);
    }

    private function ordersQuery()
    {
        $query = trim($this->search);

        return Order::query()
            ->with(['branch', 'customer', 'device'])
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $searchBuilder) use ($query): void {
                    $searchBuilder->where('order_number', 'like', '%'.$query.'%')
                        ->orWhere('status', 'like', '%'.$query.'%')
                        ->orWhereHas('customer', function (Builder $customerBuilder) use ($query): void {
                            $customerBuilder->where('name', 'like', '%'.$query.'%')
                                ->orWhere('phone', 'like', '%'.$query.'%');
                        })
                        ->orWhereHas('device', function (Builder $deviceBuilder) use ($query): void {
                            $deviceBuilder->where('type', 'like', '%'.$query.'%')
                                ->orWhere('serial_number', 'like', '%'.$query.'%');
                        });
                });
            })
            ->orderByDesc('id');
    }
}
