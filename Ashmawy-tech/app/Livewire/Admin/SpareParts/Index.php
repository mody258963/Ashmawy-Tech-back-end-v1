<?php

namespace App\Livewire\Admin\SpareParts;

use App\Models\InventoryMovement;
use App\Models\SparePart;
use App\Services\Inventory\SparePartStockService;
use App\Repository\SparePart\SparePartRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Index extends Component
{
    use WithPagination;

    private const DEFAULT_QUANTITY = '1';
    private const DEFAULT_ADJUST_DIRECTION = 'in';

    protected string $paginationTheme = 'bootstrap';

    public ?int $inventoryPartId = null;
    public string $search = '';

    public string $purchase_quantity = self::DEFAULT_QUANTITY;

    public string $purchase_unit_cost = '';

    public string $sale_quantity = self::DEFAULT_QUANTITY;

    public string $sale_unit_price = '';

    public string $adjust_quantity = self::DEFAULT_QUANTITY;

    public string $adjust_direction = self::DEFAULT_ADJUST_DIRECTION;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openInventory(int $id): void
    {
        $this->inventoryPartId = $id;
        $this->resetInventoryInputs();
    }

    public function closeInventory(): void
    {
        $this->inventoryPartId = null;
    }

    public function recordPurchase(SparePartRepository $parts, SparePartStockService $stock): void
    {
        $this->validate([
            'purchase_quantity' => ['required', 'integer', 'min:1'],
            'purchase_unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $part = $this->findActivePart($parts);
        $stock->recordPurchase(
            $part,
            (int) $this->purchase_quantity,
            $this->purchase_unit_cost !== '' ? $this->purchase_unit_cost : null,
            $this->actorId(),
            null,
        );
        session()->flash('status', 'Purchase recorded.');
        $this->resetPage();
        $this->closeInventory();
    }

    public function recordSale(SparePartRepository $parts, SparePartStockService $stock): void
    {
        $this->validate([
            'sale_quantity' => ['required', 'integer', 'min:1'],
            'sale_unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $part = $this->findActivePart($parts);
        try {
            $stock->recordSale(
                $part,
                (int) $this->sale_quantity,
                $this->sale_unit_price !== '' ? $this->sale_unit_price : null,
                $this->actorId(),
                null,
            );
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }
        session()->flash('status', 'Sale recorded.');
        $this->resetPage();
        $this->closeInventory();
    }

    public function recordAdjust(SparePartRepository $parts, SparePartStockService $stock): void
    {
        $this->validate([
            'adjust_quantity' => ['required', 'integer', 'min:1'],
            'adjust_direction' => ['required', 'in:in,out'],
        ]);

        $part = $this->findActivePart($parts);
        try {
            if ($this->adjust_direction === 'in') {
                $stock->recordAdjustmentIn($part, (int) $this->adjust_quantity, $this->actorId());
            } else {
                $stock->recordAdjustmentOut($part, (int) $this->adjust_quantity, $this->actorId());
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }
        session()->flash('status', 'Stock adjustment recorded.');
        $this->resetPage();
        $this->closeInventory();
    }

    public function render()
    {
        $spareParts = $this->sparePartsQuery()->paginate(15);
        $ids = $spareParts->getCollection()->pluck('id')->all();

        $salesByPart = $this->salesByPart($ids);
        $movementByPart = $this->movementByPart($ids);
        $summary = $this->summary();

        return view('livewire.admin.spare-parts.index', [
            'spareParts' => $spareParts,
            'salesByPart' => $salesByPart,
            'movementByPart' => $movementByPart,
            'summary' => $summary,
        ]);
    }

    private function sparePartsQuery()
    {
        $query = trim($this->search);

        return SparePart::query()
            ->with('branch')
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $searchBuilder) use ($query): void {
                    $searchBuilder->where('name', 'like', '%'.$query.'%')
                        ->orWhere('code', 'like', '%'.$query.'%');
                });
            })
            ->orderByDesc('id');
    }

    /**
     * @param array<int, int|string> $partIds
     * @return array<int, array{sold_qty:int,last_sold_at:mixed}>
     */
    private function salesByPart(array $partIds): array
    {
        if ($partIds === []) {
            return [];
        }

        return InventoryMovement::query()
            ->selectRaw('spare_part_id, SUM(quantity) as sold_qty, MAX(created_at) as last_sold_at')
            ->where('movement_type', InventoryMovement::TYPE_SALE)
            ->whereIn('spare_part_id', $partIds)
            ->groupBy('spare_part_id')
            ->get()
            ->keyBy('spare_part_id')
            ->map(fn ($row) => [
                'sold_qty' => (int) ($row->sold_qty ?? 0),
                'last_sold_at' => $row->last_sold_at,
            ])
            ->toArray();
    }

    /**
     * @param array<int, int|string> $partIds
     * @return array<int, array{purchase_qty:float,adjust_in_qty:float,adjust_out_qty:float}>
     */
    private function movementByPart(array $partIds): array
    {
        if ($partIds === []) {
            return [];
        }

        return InventoryMovement::query()
            ->selectRaw(
                'spare_part_id,
                SUM(CASE WHEN movement_type = ? THEN quantity ELSE 0 END) as purchase_qty,
                SUM(CASE WHEN movement_type = ? THEN quantity ELSE 0 END) as adjust_in_qty,
                SUM(CASE WHEN movement_type = ? THEN quantity ELSE 0 END) as adjust_out_qty',
                [
                    InventoryMovement::TYPE_PURCHASE,
                    InventoryMovement::TYPE_ADJUSTMENT_IN,
                    InventoryMovement::TYPE_ADJUSTMENT_OUT,
                ],
            )
            ->whereIn('spare_part_id', $partIds)
            ->groupBy('spare_part_id')
            ->get()
            ->keyBy('spare_part_id')
            ->map(fn ($row) => [
                'purchase_qty' => (float) ($row->purchase_qty ?? 0),
                'adjust_in_qty' => (float) ($row->adjust_in_qty ?? 0),
                'adjust_out_qty' => (float) ($row->adjust_out_qty ?? 0),
            ])
            ->toArray();
    }

    /**
     * @return array<string, float|int>
     */
    private function summary(): array
    {
        $soldPartCount = (int) InventoryMovement::query()
            ->where('movement_type', InventoryMovement::TYPE_SALE)
            ->distinct('spare_part_id')
            ->count('spare_part_id');

        $soldCostTotal = (float) InventoryMovement::query()
            ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
            ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
            ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.cost_price, 0)'));

        $soldPriceTotal = (float) InventoryMovement::query()
            ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
            ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
            ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.selling_price, 0)'));

        return [
            'products_total' => (int) SparePart::query()->count(),
            'products_sold' => $soldPartCount,
            'capital_total' => (float) SparePart::query()->sum(DB::raw('quantity * COALESCE(cost_price, 0)')),
            'sold_cost_total' => $soldCostTotal,
            'sold_price_total' => $soldPriceTotal,
            'profit_total' => $soldPriceTotal - $soldCostTotal,
        ];
    }

    private function findActivePart(SparePartRepository $parts): SparePart
    {
        if ($this->inventoryPartId === null) {
            throw new RuntimeException('No spare part selected.');
        }

        return $parts->find($this->inventoryPartId);
    }

    private function resetInventoryInputs(): void
    {
        $this->reset([
            'purchase_quantity',
            'purchase_unit_cost',
            'sale_quantity',
            'sale_unit_price',
            'adjust_quantity',
        ]);

        $this->purchase_quantity = self::DEFAULT_QUANTITY;
        $this->sale_quantity = self::DEFAULT_QUANTITY;
        $this->adjust_quantity = self::DEFAULT_QUANTITY;
        $this->adjust_direction = self::DEFAULT_ADJUST_DIRECTION;
    }

    private function actorId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? $user->id : null;
    }
}
