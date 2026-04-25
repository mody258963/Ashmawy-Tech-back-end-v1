<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\SparePart;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SparePartStockService
{
    public function recordPurchase(
        SparePart $part,
        int $quantity,
        ?string $unitCost = null,
        ?int $createdBy = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Purchase quantity must be at least 1.');
        }

        return DB::transaction(function () use ($part, $quantity, $unitCost, $createdBy, $notes, $referenceType, $referenceId) {
            $part->refresh();
            $unitCostDecimal = $unitCost !== null ? (string) $unitCost : null;

            $this->applyWeightedAverageCost($part, $quantity, $unitCostDecimal);

            $part->quantity += $quantity;
            $part->save();

            return InventoryMovement::query()->create([
                'spare_part_id' => $part->id,
                'branch_id' => $part->branch_id,
                'movement_type' => InventoryMovement::TYPE_PURCHASE,
                'quantity' => $quantity,
                'unit_amount' => $unitCostDecimal,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function recordSale(
        SparePart $part,
        int $quantity,
        ?string $unitPrice = null,
        ?int $createdBy = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Sale quantity must be at least 1.');
        }

        return DB::transaction(function () use ($part, $quantity, $unitPrice, $createdBy, $notes, $referenceType, $referenceId) {
            $part->refresh();

            if ($part->quantity < $quantity) {
                throw new RuntimeException('Insufficient stock for this sale.');
            }

            $part->quantity -= $quantity;
            $part->save();

            return InventoryMovement::query()->create([
                'spare_part_id' => $part->id,
                'branch_id' => $part->branch_id,
                'movement_type' => InventoryMovement::TYPE_SALE,
                'quantity' => $quantity,
                'unit_amount' => $unitPrice !== null ? (string) $unitPrice : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function recordAdjustmentIn(
        SparePart $part,
        int $quantity,
        ?int $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Adjustment quantity must be at least 1.');
        }

        return DB::transaction(function () use ($part, $quantity, $createdBy, $notes) {
            $part->refresh();
            $part->quantity += $quantity;
            $part->save();

            return InventoryMovement::query()->create([
                'spare_part_id' => $part->id,
                'branch_id' => $part->branch_id,
                'movement_type' => InventoryMovement::TYPE_ADJUSTMENT_IN,
                'quantity' => $quantity,
                'unit_amount' => null,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function recordAdjustmentOut(
        SparePart $part,
        int $quantity,
        ?int $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Adjustment quantity must be at least 1.');
        }

        return DB::transaction(function () use ($part, $quantity, $createdBy, $notes) {
            $part->refresh();

            if ($part->quantity < $quantity) {
                throw new RuntimeException('Cannot remove more stock than on hand.');
            }

            $part->quantity -= $quantity;
            $part->save();

            return InventoryMovement::query()->create([
                'spare_part_id' => $part->id,
                'branch_id' => $part->branch_id,
                'movement_type' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
                'quantity' => $quantity,
                'unit_amount' => null,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    private function applyWeightedAverageCost(SparePart $part, int $addedQty, ?string $unitCost): void
    {
        if ($unitCost === null) {
            return;
        }

        $oldQty = $part->quantity;
        $oldCost = $part->cost_price;
        $newTotalQty = $oldQty + $addedQty;

        if ($newTotalQty < 1) {
            return;
        }

        if ($oldQty > 0 && $oldCost !== null) {
            $avg = (($oldQty * (float) $oldCost) + ($addedQty * (float) $unitCost)) / $newTotalQty;
            $part->setAttribute('cost_price', round($avg, 2));
        } else {
            $part->setAttribute('cost_price', round((float) $unitCost, 2));
        }
    }
}
