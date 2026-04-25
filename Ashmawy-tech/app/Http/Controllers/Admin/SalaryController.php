<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryRequest;
use App\Models\Branch;
use App\Models\Salary;
use App\Models\User;
use App\Models\WorkerPenalty;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalaryController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->input('month')
            ? CarbonImmutable::parse($request->input('month').'-01')->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();
        $salaries = Salary::query()->with(['user', 'branch'])->whereDate('for_month', $month)->latest()->paginate(20);
        $penaltiesByUser = WorkerPenalty::query()
            ->whereDate('applied_for_month', $month)
            ->selectRaw('user_id, SUM(amount) as penalty_total')
            ->groupBy('user_id')
            ->pluck('penalty_total', 'user_id');

        return view('admin.salaries.index', [
            'salaries' => $salaries,
            'month' => $month,
            'penaltiesByUser' => $penaltiesByUser,
        ]);
    }

    public function create(): View
    {
        return view('admin.salaries.create', [
            'users' => User::query()->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function store(SalaryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['for_month'] = CarbonImmutable::parse($data['for_month'])->startOfMonth();
        $data['created_by'] = $request->user()->id;
        Salary::query()->updateOrCreate(
            ['user_id' => $data['user_id'], 'for_month' => $data['for_month']],
            $data
        );

        return redirect()->route('admin.salaries.index')->with('status', 'Salary saved.');
    }

    public function edit(Salary $salary): View
    {
        return view('admin.salaries.edit', [
            'salary' => $salary,
            'users' => User::query()->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function update(SalaryRequest $request, Salary $salary): RedirectResponse
    {
        $data = $request->validated();
        $data['for_month'] = CarbonImmutable::parse($data['for_month'])->startOfMonth();
        $salary->update($data);

        return redirect()->route('admin.salaries.index')->with('status', 'Salary updated.');
    }

    public function destroy(Salary $salary): RedirectResponse
    {
        $salary->delete();

        return redirect()->route('admin.salaries.index')->with('status', 'Salary deleted.');
    }
}
