<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PenaltyRequest;
use App\Models\WorkerPenalty;
use App\Repository\Branch\BranchRepository;
use App\Repository\User\UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenaltyController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        $penalties = WorkerPenalty::query()
            ->with(['user', 'branch', 'creator'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.penalties.index', [
            'penalties' => $penalties,
        ]);
    }

    public function create(): View
    {
        return view('admin.penalties.create', [
            'users' => $this->users->all(),
            'branches' => $this->branches->all(),
            'monthStart' => CarbonImmutable::now()->startOfMonth()->toDateString(),
        ]);
    }

    public function store(PenaltyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['created_by'] = $request->user()->id;
        $data['applied_for_month'] = CarbonImmutable::parse($data['applied_for_month'])->startOfMonth()->toDateString();

        WorkerPenalty::query()->create($data);

        return redirect()->route('admin.penalties.index')->with('status', 'Penalty added.');
    }

    public function destroy(int $penalty): RedirectResponse
    {
        WorkerPenalty::query()->findOrFail($penalty)->delete();

        return redirect()->route('admin.penalties.index')->with('status', 'Penalty deleted.');
    }
}
