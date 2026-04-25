<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
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
        return view('livewire.admin.customers.index', [
            'customers' => $this->customersQuery()->paginate(15),
        ]);
    }

    private function customersQuery()
    {
        $query = trim($this->search);

        return Customer::query()
            ->with(['branch', 'creator'])
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $searchBuilder) use ($query): void {
                    $searchBuilder->where('name', 'like', '%'.$query.'%')
                        ->orWhere('phone', 'like', '%'.$query.'%')
                        ->orWhere('address', 'like', '%'.$query.'%');
                });
            })
            ->orderByDesc('id');
    }
}
