<?php

namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class InventoryService
{
    public function __construct(private readonly TenantContext $context) {}

    public function move(string $warehouseId, string $catalogItemId, string $type, string $delta, ?string $sourceId = null): void
    {
        DB::transaction(function () use ($warehouseId, $catalogItemId, $type, $delta, $sourceId): void {
            $balance = DB::table('stock_balances')->where('warehouse_id', $warehouseId)->where('catalog_item_id', $catalogItemId)->lockForUpdate()->first();
            if (! is_numeric($delta)) {
                throw ValidationException::withMessages(['quantity' => 'Quantity delta must be numeric.']);
            }
            $currentQuantity = data_get($balance, 'quantity', '0');
            $reservedQuantity = data_get($balance, 'reserved_quantity', 0);
            $reorderLevel = data_get($balance, 'reorder_level', 0);
            $createdAt = data_get($balance, 'created_at', now());
            if (! is_string($currentQuantity) && ! is_int($currentQuantity) && ! is_float($currentQuantity)) {
                throw new LogicException('Stored stock quantity is not numeric.');
            }
            $currentQuantity = (string) $currentQuantity;
            if (! is_numeric($currentQuantity)) {
                throw new LogicException('Stored stock quantity is not numeric.');
            }
            $quantity = bcadd($currentQuantity, $delta, 3);
            if (bccomp($quantity, '0', 3) < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot become negative.']);
            }
            DB::table('stock_balances')->updateOrInsert(
                ['warehouse_id' => $warehouseId, 'catalog_item_id' => $catalogItemId],
                [
                    'quantity' => $quantity,
                    'reserved_quantity' => $reservedQuantity,
                    'reorder_level' => $reorderLevel,
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ],
            );
            DB::table('stock_movements')->insert([
                'id' => (string) Str::uuid(), 'organization_id' => $this->context->organizationId(), 'warehouse_id' => $warehouseId,
                'catalog_item_id' => $catalogItemId, 'type' => $type, 'quantity_delta' => $delta,
                'source_type' => $sourceId ? 'manual' : null, 'source_id' => $sourceId, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }
}
