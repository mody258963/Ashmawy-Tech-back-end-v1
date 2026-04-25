<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use App\Repository\Branch\BranchRepository;
use App\Repository\Customer\CustomerRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        $now = CarbonImmutable::now();
        $weekStart = $now->startOfWeek();

        $total = Customer::query()->count();
        $newWeek = Customer::query()->where('created_at', '>=', $weekStart)->count();
        $converted = Customer::query()->where('status', 'converted')->count();
        $rejected = Customer::query()->where('status', 'rejected')->count();

        return view('admin.customers.index', [
            'cards' => [
                [
                    'label' => __('messages.total_customers'),
                    'value' => $total,
                    'class' => 'bg-success',
                    'icon' => 'fas fa-user-friends',
                    'url' => route('admin.customers.index'),
                ],
                [
                    'label' => __('messages.new_this_week'),
                    'value' => $newWeek,
                    'class' => 'bg-info',
                    'icon' => 'fas fa-user-plus',
                    'url' => route('admin.customers.index'),
                ],
                [
                    'label' => __('messages.converted'),
                    'value' => $converted,
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-check',
                    'url' => route('admin.customers.index'),
                ],
                [
                    'label' => __('messages.rejected'),
                    'value' => $rejected,
                    'class' => 'bg-danger',
                    'icon' => 'fas fa-times',
                    'url' => route('admin.customers.index'),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', [
            'branches' => $this->branches->all(),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $this->customers->create($data);

        return redirect()->route('admin.customers.index')->with('status', __('messages.customer_created'));
    }

    public function edit(int $customer): View
    {
        return view('admin.customers.edit', [
            'customer' => $this->customers->find($customer),
            'branches' => $this->branches->all(),
        ]);
    }

    public function update(CustomerRequest $request, int $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return redirect()->route('admin.customers.index')->with('status', __('messages.customer_updated'));
    }

    public function destroy(int $customer): RedirectResponse
    {
        $this->customers->delete($customer);

        return redirect()->route('admin.customers.index')->with('status', __('messages.customer_deleted'));
    }
}
