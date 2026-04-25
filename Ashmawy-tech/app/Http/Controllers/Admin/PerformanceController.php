<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Stats\WorkerPerformanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function index(Request $request, WorkerPerformanceService $performance): View
    {
        $month = $request->query('month');
        $monthStart = $month ? CarbonImmutable::parse($month)->startOfMonth() : CarbonImmutable::now()->startOfMonth();

        $branchId = $request->query('branch_id');
        $branchId = $branchId !== null && $branchId !== '' ? (int) $branchId : null;

        $rows = $performance->technicianMonthly($monthStart, $branchId);
        $users = User::query()
            ->whereIn('id', $rows->pluck('technician_id')->all())
            ->get(['id', 'name', 'role', 'branch_id'])
            ->keyBy('id');

        return view('admin.performance.index', [
            'monthStart' => $monthStart,
            'rows' => $rows,
            'users' => $users,
            'branchId' => $branchId,
        ]);
    }
}
