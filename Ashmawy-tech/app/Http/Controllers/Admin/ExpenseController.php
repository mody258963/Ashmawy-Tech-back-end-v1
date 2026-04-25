<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\Expense;
use App\Repository\Branch\BranchRepository;
use App\Repository\Expense\ExpenseRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        $now = CarbonImmutable::now();
        $todayStart = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        $sumToday = Expense::query()->where('created_at', '>=', $todayStart)->sum('amount');
        $sumWeek = Expense::query()->where('created_at', '>=', $weekStart)->sum('amount');
        $sumMonth = Expense::query()->where('created_at', '>=', $monthStart)->sum('amount');

        return view('admin.expenses.index', [
            'expenses' => $this->expenses->paginate(20),
            'cards' => [
                [
                    'label' => __('messages.expenses_today'),
                    'value' => number_format((float) $sumToday, 2),
                    'class' => 'bg-danger',
                    'icon' => 'fas fa-receipt',
                    'url' => route('admin.expenses.index'),
                ],
                [
                    'label' => __('messages.expenses_this_week'),
                    'value' => number_format((float) $sumWeek, 2),
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-calendar-week',
                    'url' => route('admin.expenses.index'),
                ],
                [
                    'label' => __('messages.expenses_this_month'),
                    'value' => number_format((float) $sumMonth, 2),
                    'class' => 'bg-secondary',
                    'icon' => 'fas fa-calendar-alt',
                    'url' => route('admin.expenses.index'),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.expenses.create', [
            'branches' => $this->branches->all(),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $this->expenses->create($data);

        return redirect()->route('admin.expenses.index')->with('status', __('messages.expense_created'));
    }

    public function edit(int $expense): View
    {
        return view('admin.expenses.edit', [
            'expense' => $this->expenses->find($expense),
            'branches' => $this->branches->all(),
        ]);
    }

    public function update(ExpenseRequest $request, int $expense): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->expenses->find($expense)->created_by ?? $request->user()->id;
        $this->expenses->update($expense, $data);

        return redirect()->route('admin.expenses.index')->with('status', __('messages.expense_updated'));
    }

    public function destroy(int $expense): RedirectResponse
    {
        $this->expenses->delete($expense);

        return redirect()->route('admin.expenses.index')->with('status', __('messages.expense_deleted'));
    }
}
