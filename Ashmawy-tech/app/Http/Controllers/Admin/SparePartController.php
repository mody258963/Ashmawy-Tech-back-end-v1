<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SparePartRequest;
use App\Models\InventoryMovement;
use App\Models\SparePart;
use App\Repository\Branch\BranchRepository;
use App\Repository\SparePart\SparePartRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SparePartController extends Controller
{
    public function __construct(
        private readonly SparePartRepository $spareParts,
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        $total = SparePart::query()->count();
        $lowStock = SparePart::query()->where('quantity', '<', 5)->count();
        $totalQty = (int) SparePart::query()->sum('quantity');
        $now = CarbonImmutable::now();
        $todayStart = $now->startOfDay();
        $movesToday = InventoryMovement::query()->where('created_at', '>=', $todayStart)->count();

        return view('admin.spare-parts.index', [
            'cards' => [
                [
                    'label' => 'Spare parts',
                    'value' => $total,
                    'class' => 'bg-info',
                    'icon' => 'fas fa-cogs',
                    'url' => route('admin.spare-parts.index'),
                ],
                [
                    'label' => 'Low stock (<5)',
                    'value' => $lowStock,
                    'class' => 'bg-danger',
                    'icon' => 'fas fa-exclamation-triangle',
                    'url' => route('admin.spare-parts.index'),
                ],
                [
                    'label' => 'Total qty',
                    'value' => $totalQty,
                    'class' => 'bg-success',
                    'icon' => 'fas fa-boxes',
                    'url' => route('admin.spare-parts.index'),
                ],
                [
                    'label' => 'Movements today',
                    'value' => $movesToday,
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-exchange-alt',
                    'url' => route('admin.spare-parts.index'),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.spare-parts.create', [
            'branches' => $this->branches->all(),
        ]);
    }

    public function store(SparePartRequest $request): RedirectResponse
    {
        $this->spareParts->create($request->validated());

        return redirect()->route('admin.spare-parts.index')->with('status', 'Spare part created.');
    }

    public function edit(int $spare_part): View
    {
        return view('admin.spare-parts.edit', [
            'sparePart' => $this->spareParts->find($spare_part),
            'branches' => $this->branches->all(),
        ]);
    }

    public function update(SparePartRequest $request, int $spare_part): RedirectResponse
    {
        $this->spareParts->update($spare_part, $request->validated());

        return redirect()->route('admin.spare-parts.index')->with('status', 'Spare part updated.');
    }

    public function destroy(int $spare_part): RedirectResponse
    {
        $this->spareParts->delete($spare_part);

        return redirect()->route('admin.spare-parts.index')->with('status', 'Spare part deleted.');
    }
}
