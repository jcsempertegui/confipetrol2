<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\SerializedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly CodeGenerator $codes) {}

    public function confirm(DispatchNote $note, int $userId): DispatchNote
    {
        return DB::transaction(function () use ($note, $userId) {
            $note = DispatchNote::whereKey($note->id)->lockForUpdate()->firstOrFail();
            if ($note->status !== 'draft') {
                throw ValidationException::withMessages(['document' => 'Solo se puede confirmar un remito en borrador.']);
            }
            if ($note->corrected_from_id) {
                return $this->confirmDispatchCorrection($note, $userId);
            }

            $items = $note->items()->with(['variant.product', 'serializedItems'])->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agregue al menos un producto al remito.']);
            }

            foreach ($items as $item) {
                $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->firstOrFail();
                $this->assertActiveVariant($variant);
                $quantity = (float) $item->quantity;
                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['items' => 'Todas las cantidades deben ser mayores que cero.']);
                }
                $serialized = $variant->product->tracking_type === 'serialized';
                if ($serialized && (float) $item->serializedItems->count() !== $quantity) {
                    throw ValidationException::withMessages(['items' => 'La cantidad de '.$variant->sku.' debe coincidir con sus números de serie.']);
                }
                if (! $serialized && $item->serializedItems->isNotEmpty()) {
                    throw ValidationException::withMessages(['items' => 'Un producto por cantidad no puede incluir números de serie.']);
                }

                if ($serialized) {
                    foreach ($item->serializedItems as $selected) {
                        $serial = SerializedItem::whereKey($selected->id)->lockForUpdate()->firstOrFail();
                        if ($serial->product_variant_id !== $variant->id || $serial->status === 'inactive') {
                            throw ValidationException::withMessages(['items' => 'El número de serie '.$serial->serial_number.' no pertenece a la variante seleccionada o está inactivo.']);
                        }
                        $balance = (float) InventoryMovement::where('serialized_item_id', $serial->id)->sum('quantity');
                        if ($note->type === 'entry' && $balance !== 0.0) {
                            throw ValidationException::withMessages(['items' => 'El número de serie '.$serial->serial_number.' ya se encuentra en almacén.']);
                        }
                        if ($note->type === 'exit' && $balance !== 1.0) {
                            throw ValidationException::withMessages(['items' => 'El número de serie '.$serial->serial_number.' no está disponible en almacén.']);
                        }
                    }
                } elseif ($note->type === 'exit') {
                    $stock = (float) InventoryMovement::where('product_variant_id', $variant->id)->sum('quantity');
                    if ($stock < $quantity) {
                        throw ValidationException::withMessages(['items' => 'Stock insuficiente para '.$variant->sku.'. Disponible: '.$stock.'.']);
                    }
                }
            }

            $note->number ??= $this->nextNumber($note->type, $note->document_date);
            $sign = $note->type === 'entry' ? 1 : -1;
            foreach ($items as $item) {
                if ($item->serializedItems->isNotEmpty()) {
                    foreach ($item->serializedItems as $serial) {
                        InventoryMovement::create([
                            'product_variant_id' => $item->product_variant_id, 'serialized_item_id' => $serial->id,
                            'dispatch_note_id' => $note->id, 'movement_type' => 'dispatch_'.$note->type,
                            'quantity' => $sign, 'occurred_at' => now(), 'created_by' => $userId,
                        ]);
                        $serial->update(['status' => $sign > 0 ? 'available' : 'out_of_stock']);
                    }
                } else {
                    InventoryMovement::create([
                        'product_variant_id' => $item->product_variant_id, 'dispatch_note_id' => $note->id,
                        'movement_type' => 'dispatch_'.$note->type, 'quantity' => $sign * (float) $item->quantity,
                        'occurred_at' => now(), 'created_by' => $userId,
                    ]);
                }
            }
            $note->fill(['status' => 'confirmed', 'confirmed_by' => $userId, 'confirmed_at' => now()])->save();

            return $note->fresh(['items.variant.product', 'items.serializedItems']);
        });
    }

    public function annul(DispatchNote $note, int $userId, string $reason): DispatchNote
    {
        return DB::transaction(function () use ($note, $userId, $reason) {
            $note = DispatchNote::whereKey($note->id)->lockForUpdate()->firstOrFail();
            if ($note->status !== 'confirmed') {
                throw ValidationException::withMessages(['annulReason' => 'Solo se puede anular un remito confirmado.']);
            }
            if ($note->corrected_from_id) {
                return $this->annulCorrectedDispatch($note, $userId, $reason);
            }

            $movements = $note->movements()->lockForUpdate()->get();
            foreach ($movements as $movement) {
                if ($movement->quantity > 0) {
                    $current = (float) InventoryMovement::where('product_variant_id', $movement->product_variant_id)->sum('quantity');
                    if ($movement->serialized_item_id) {
                        $current = (float) InventoryMovement::where('serialized_item_id', $movement->serialized_item_id)->sum('quantity');
                    }
                    if ($current < (float) $movement->quantity) {
                        throw ValidationException::withMessages(['annulReason' => 'No se puede anular porque parte del stock ingresado ya fue utilizado.']);
                    }
                }
            }
            foreach ($movements as $movement) {
                InventoryMovement::create([
                    'product_variant_id' => $movement->product_variant_id, 'serialized_item_id' => $movement->serialized_item_id,
                    'dispatch_note_id' => $note->id, 'reversal_of_id' => $movement->id,
                    'movement_type' => 'annulment', 'quantity' => -(float) $movement->quantity,
                    'occurred_at' => now(), 'created_by' => $userId,
                ]);
                if ($movement->serialized_item_id) {
                    $balance = (float) InventoryMovement::where('serialized_item_id', $movement->serialized_item_id)->sum('quantity');
                    SerializedItem::whereKey($movement->serialized_item_id)->update(['status' => $balance > 0 ? 'available' : 'out_of_stock']);
                }
            }
            $note->fill(['status' => 'annulled', 'annulled_by' => $userId, 'annulled_at' => now(), 'annul_reason' => trim($reason)])->save();

            return $note->fresh(['items.variant.product', 'items.serializedItems']);
        });
    }

    public function confirmDelivery(Delivery $delivery, int $userId): Delivery
    {
        return DB::transaction(function () use ($delivery, $userId) {
            $delivery = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if ($delivery->status !== 'draft' || ! $delivery->worker()->where('status', true)->exists()) {
                throw ValidationException::withMessages(['delivery' => 'La entrega no está en borrador o el trabajador está inactivo.']);
            }
            if ($delivery->corrected_from_id) {
                return $this->confirmDeliveryCorrection($delivery, $userId);
            }

            $items = $delivery->items()->with(['variant.product', 'serializedItems'])->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agregue al menos un producto.']);
            }
            foreach ($items as $item) {
                $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->firstOrFail();
                $this->assertActiveVariant($variant);
                $quantity = (float) $item->quantity;
                $serialized = $variant->product->tracking_type === 'serialized';
                if ($quantity <= 0 || ($serialized && (float) $item->serializedItems->count() !== $quantity)) {
                    throw ValidationException::withMessages(['items' => 'Cantidad o series inválidas para '.$variant->sku.'.']);
                }
                if (! $serialized && $item->serializedItems->isNotEmpty()) {
                    throw ValidationException::withMessages(['items' => 'El producto '.$variant->sku.' se controla por cantidad y no admite números de serie.']);
                }
                if ($serialized) {
                    foreach ($item->serializedItems as $selected) {
                        $serial = SerializedItem::whereKey($selected->id)->lockForUpdate()->firstOrFail();
                        $balance = (float) InventoryMovement::where('serialized_item_id', $serial->id)->sum('quantity');
                        if ($serial->product_variant_id !== $variant->id || $serial->status !== 'available' || $balance !== 1.0) {
                            throw ValidationException::withMessages(['items' => 'La serie '.$serial->serial_number.' no está disponible.']);
                        }
                    }
                } else {
                    $stock = (float) InventoryMovement::where('product_variant_id', $variant->id)->sum('quantity');
                    if ($stock < $quantity) {
                        throw ValidationException::withMessages(['items' => 'Stock insuficiente para '.$variant->sku.'. Disponible: '.$stock.'.']);
                    }
                }
            }
            if (! $delivery->number) {
                $delivery->number = $this->dailyNumber(
                    $this->codes->sequenceKey('delivery', $delivery->delivery_date),
                    'ENT',
                    $delivery->delivery_date,
                    fn (string $code) => Delivery::where('number', $code)->exists()
                );
            }
            foreach ($items as $item) {
                if ($item->serializedItems->isNotEmpty()) {
                    foreach ($item->serializedItems as $serial) {
                        InventoryMovement::create(['product_variant_id' => $item->product_variant_id, 'serialized_item_id' => $serial->id, 'delivery_id' => $delivery->id, 'movement_type' => 'delivery', 'quantity' => -1, 'occurred_at' => now(), 'created_by' => $userId]);
                        $serial->update(['status' => 'assigned']);
                    }
                } else {
                    InventoryMovement::create(['product_variant_id' => $item->product_variant_id, 'delivery_id' => $delivery->id, 'movement_type' => 'delivery', 'quantity' => -(float) $item->quantity, 'occurred_at' => now(), 'created_by' => $userId]);
                }
            }
            $delivery->fill(['status' => 'confirmed', 'confirmed_by' => $userId, 'confirmed_at' => now()])->save();

            return $delivery->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
        });
    }

    public function annulDelivery(Delivery $delivery, int $userId, string $reason): Delivery
    {
        return DB::transaction(function () use ($delivery, $userId, $reason) {
            $delivery = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if ($delivery->status !== 'confirmed') {
                throw ValidationException::withMessages(['annulReason' => 'Solo se puede anular una entrega confirmada.']);
            }
            if ($delivery->corrected_from_id) {
                return $this->annulCorrectedDelivery($delivery, $userId, $reason);
            }
            foreach ($delivery->movements()->lockForUpdate()->get() as $movement) {
                InventoryMovement::create(['product_variant_id' => $movement->product_variant_id, 'serialized_item_id' => $movement->serialized_item_id, 'delivery_id' => $delivery->id, 'reversal_of_id' => $movement->id, 'movement_type' => 'delivery_annulment', 'quantity' => -(float) $movement->quantity, 'occurred_at' => now(), 'created_by' => $userId]);
                if ($movement->serialized_item_id) {
                    SerializedItem::whereKey($movement->serialized_item_id)->update(['status' => 'available']);
                }
            }
            $delivery->fill(['status' => 'annulled', 'annulled_by' => $userId, 'annulled_at' => now(), 'annul_reason' => trim($reason)])->save();

            return $delivery->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
        });
    }

    private function confirmDispatchCorrection(DispatchNote $note, int $userId): DispatchNote
    {
        $original = DispatchNote::whereKey($note->corrected_from_id)->lockForUpdate()->firstOrFail();
        if ($original->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'document' => 'El remito original ya no está confirmado y esta corrección no puede aplicarse.',
            ]);
        }

        $items = $note->items()->with(['variant.product', 'serializedItems'])->get();
        $originalItems = $original->items()->with(['variant.product', 'serializedItems'])->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Agregue al menos un producto al remito.']);
        }

        [$variants, $serials] = $this->lockCorrectionResources($originalItems, $items);
        $originalSign = $original->type === 'entry' ? 1.0 : -1.0;
        $correctedSign = $note->type === 'entry' ? 1.0 : -1.0;
        $before = $this->buildContribution($originalItems, $variants, $serials, $originalSign, false);
        $after = $this->buildContribution($items, $variants, $serials, $correctedSign, true);

        $this->applyCorrectionDelta(
            $before,
            $after,
            $variants,
            $serials,
            $userId,
            dispatchNoteId: $note->id,
            movementType: 'dispatch_correction',
        );

        $note->number ??= $this->nextNumber($note->type, $note->document_date);
        $original->fill([
            'status' => 'annulled',
            'annulled_by' => $userId,
            'annulled_at' => now(),
            'annul_reason' => 'Sustituido por la corrección '.$note->number.'.',
        ])->save();
        $note->fill([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ])->save();

        return $note->fresh(['items.variant.product', 'items.serializedItems', 'correctedFrom']);
    }

    private function confirmDeliveryCorrection(Delivery $delivery, int $userId): Delivery
    {
        $original = Delivery::whereKey($delivery->corrected_from_id)->lockForUpdate()->firstOrFail();
        if ($original->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'delivery' => 'La entrega original ya no está confirmada y esta corrección no puede aplicarse.',
            ]);
        }

        $items = $delivery->items()->with(['variant.product', 'serializedItems'])->get();
        $originalItems = $original->items()->with(['variant.product', 'serializedItems'])->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Agregue al menos un producto.']);
        }

        [$variants, $serials] = $this->lockCorrectionResources($originalItems, $items);
        $before = $this->buildContribution($originalItems, $variants, $serials, -1.0, false);
        $after = $this->buildContribution($items, $variants, $serials, -1.0, true);

        $this->applyCorrectionDelta(
            $before,
            $after,
            $variants,
            $serials,
            $userId,
            deliveryId: $delivery->id,
            movementType: 'delivery_correction',
            delivery: true,
        );

        if (! $delivery->number) {
            $delivery->number = $this->dailyNumber(
                $this->codes->sequenceKey('delivery', $delivery->delivery_date),
                'ENT',
                $delivery->delivery_date,
                fn (string $code) => Delivery::where('number', $code)->exists()
            );
        }

        $original->fill([
            'status' => 'annulled',
            'annulled_by' => $userId,
            'annulled_at' => now(),
            'annul_reason' => 'Sustituida por la corrección '.$delivery->number.'.',
        ])->save();
        $delivery->fill([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ])->save();

        return $delivery->fresh(['worker', 'items.variant.product', 'items.serializedItems', 'correctedFrom']);
    }

    private function annulCorrectedDispatch(DispatchNote $note, int $userId, string $reason): DispatchNote
    {
        $items = $note->items()->with(['variant.product', 'serializedItems'])->get();
        [$variants, $serials] = $this->lockCorrectionResources(collect(), $items);
        $sign = $note->type === 'entry' ? 1.0 : -1.0;
        $current = $this->buildContribution($items, $variants, $serials, $sign, false);

        $this->applyCorrectionDelta(
            $current,
            ['bulk' => [], 'serial' => []],
            $variants,
            $serials,
            $userId,
            dispatchNoteId: $note->id,
            movementType: 'annulment',
        );

        $note->fill([
            'status' => 'annulled',
            'annulled_by' => $userId,
            'annulled_at' => now(),
            'annul_reason' => trim($reason),
        ])->save();

        return $note->fresh(['items.variant.product', 'items.serializedItems']);
    }

    private function annulCorrectedDelivery(Delivery $delivery, int $userId, string $reason): Delivery
    {
        $items = $delivery->items()->with(['variant.product', 'serializedItems'])->get();
        [$variants, $serials] = $this->lockCorrectionResources(collect(), $items);
        $current = $this->buildContribution($items, $variants, $serials, -1.0, false);

        $this->applyCorrectionDelta(
            $current,
            ['bulk' => [], 'serial' => []],
            $variants,
            $serials,
            $userId,
            deliveryId: $delivery->id,
            movementType: 'delivery_annulment',
            delivery: true,
        );

        $delivery->fill([
            'status' => 'annulled',
            'annulled_by' => $userId,
            'annulled_at' => now(),
            'annul_reason' => trim($reason),
        ])->save();

        return $delivery->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
    }

    private function lockCorrectionResources($originalItems, $correctedItems): array
    {
        $allItems = $originalItems->concat($correctedItems);
        $variantIds = $allItems->pluck('product_variant_id')->unique()->sort()->values();
        $variants = ProductVariant::with('product')
            ->whereIn('id', $variantIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($variants->count() !== $variantIds->count()) {
            throw ValidationException::withMessages(['items' => 'Uno de los productos seleccionados ya no existe.']);
        }

        $serialIds = $allItems
            ->flatMap(fn ($item) => $item->serializedItems->pluck('id'))
            ->unique()
            ->sort()
            ->values();
        $serials = $serialIds->isEmpty()
            ? collect()
            : SerializedItem::whereIn('id', $serialIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

        if ($serials->count() !== $serialIds->count()) {
            throw ValidationException::withMessages(['items' => 'Uno de los números de serie seleccionados ya no existe.']);
        }

        return [$variants, $serials];
    }

    private function buildContribution($items, $variants, $serials, float $sign, bool $validateSelection): array
    {
        $contribution = ['bulk' => [], 'serial' => []];

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $quantity = (float) $item->quantity;
            if (! $variant || $quantity <= 0) {
                throw ValidationException::withMessages(['items' => 'Todas las cantidades deben ser mayores que cero.']);
            }
            if ($validateSelection) {
                $this->assertActiveVariant($variant);
            }

            $selectedSerials = $item->serializedItems;
            $serialized = $variant->product->tracking_type === 'serialized';
            if ($serialized && (float) $selectedSerials->count() !== $quantity) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad de '.$variant->sku.' debe coincidir con sus números de serie.',
                ]);
            }
            if (! $serialized && $selectedSerials->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Un producto por cantidad no puede incluir números de serie.',
                ]);
            }

            if (! $serialized) {
                $contribution['bulk'][$variant->id] = round(
                    ($contribution['bulk'][$variant->id] ?? 0.0) + ($sign * $quantity),
                    3
                );

                continue;
            }

            foreach ($selectedSerials as $selected) {
                $serial = $serials->get($selected->id);
                if (! $serial || $serial->product_variant_id !== $variant->id) {
                    throw ValidationException::withMessages([
                        'items' => 'El número de serie seleccionado no pertenece a '.$variant->sku.'.',
                    ]);
                }
                if ($validateSelection && $serial->status === 'inactive') {
                    throw ValidationException::withMessages([
                        'items' => 'El número de serie '.$serial->serial_number.' está inactivo.',
                    ]);
                }
                if (isset($contribution['serial'][$serial->id])) {
                    throw ValidationException::withMessages([
                        'items' => 'El número de serie '.$serial->serial_number.' está repetido.',
                    ]);
                }

                $contribution['serial'][$serial->id] = [
                    'variant_id' => $variant->id,
                    'quantity' => $sign,
                ];
            }
        }

        return $contribution;
    }

    private function applyCorrectionDelta(
        array $before,
        array $after,
        $variants,
        $serials,
        int $userId,
        ?int $dispatchNoteId = null,
        ?int $deliveryId = null,
        string $movementType = 'correction',
        bool $delivery = false,
    ): void {
        $bulkAdjustments = [];
        $bulkIds = collect(array_keys($before['bulk']))
            ->merge(array_keys($after['bulk']))
            ->unique()
            ->sort();

        foreach ($bulkIds as $variantId) {
            $delta = round(($after['bulk'][$variantId] ?? 0.0) - ($before['bulk'][$variantId] ?? 0.0), 3);
            if (abs($delta) < 0.0005) {
                continue;
            }

            $current = (float) InventoryMovement::where('product_variant_id', $variantId)->sum('quantity');
            $final = round($current + $delta, 3);
            if ($final < -0.0005) {
                $sku = $variants->get($variantId)?->sku ?? '#'.$variantId;
                throw ValidationException::withMessages([
                    'items' => 'La corrección dejaría stock negativo para '.$sku.'. Disponible: '.$current.'.',
                ]);
            }
            $bulkAdjustments[] = ['variant_id' => (int) $variantId, 'delta' => $delta];
        }

        $serialAdjustments = [];
        $serialIds = collect(array_keys($before['serial']))
            ->merge(array_keys($after['serial']))
            ->unique()
            ->sort();

        foreach ($serialIds as $serialId) {
            $beforeRow = $before['serial'][$serialId] ?? null;
            $afterRow = $after['serial'][$serialId] ?? null;
            if ($beforeRow && $afterRow && $beforeRow['variant_id'] !== $afterRow['variant_id']) {
                throw ValidationException::withMessages(['items' => 'Una serie no puede cambiar de producto.']);
            }

            $delta = ($afterRow['quantity'] ?? 0.0) - ($beforeRow['quantity'] ?? 0.0);
            if (abs($delta) < 0.0005) {
                continue;
            }

            $serial = $serials->get($serialId);
            $current = (float) InventoryMovement::where('serialized_item_id', $serialId)->sum('quantity');
            $final = round($current + $delta, 3);
            if ($final < -0.0005 || $final > 1.0005) {
                throw ValidationException::withMessages([
                    'items' => 'La corrección no es posible porque la serie '.$serial->serial_number.' ya fue utilizada en otro movimiento.',
                ]);
            }

            $serialAdjustments[] = [
                'variant_id' => (int) ($afterRow['variant_id'] ?? $beforeRow['variant_id']),
                'serial_id' => (int) $serialId,
                'delta' => $delta,
                'final' => $final,
                'after_quantity' => (float) ($afterRow['quantity'] ?? 0.0),
            ];
        }

        foreach ($bulkAdjustments as $adjustment) {
            InventoryMovement::create([
                'product_variant_id' => $adjustment['variant_id'],
                'dispatch_note_id' => $dispatchNoteId,
                'delivery_id' => $deliveryId,
                'movement_type' => $movementType,
                'quantity' => $adjustment['delta'],
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);
        }

        foreach ($serialAdjustments as $adjustment) {
            InventoryMovement::create([
                'product_variant_id' => $adjustment['variant_id'],
                'serialized_item_id' => $adjustment['serial_id'],
                'dispatch_note_id' => $dispatchNoteId,
                'delivery_id' => $deliveryId,
                'movement_type' => $movementType,
                'quantity' => $adjustment['delta'],
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            $status = $adjustment['final'] > 0.0005
                ? 'available'
                : ($delivery && $adjustment['after_quantity'] < 0 ? 'assigned' : 'out_of_stock');
            SerializedItem::whereKey($adjustment['serial_id'])->update(['status' => $status]);
        }
    }

    private function assertActiveVariant(ProductVariant $variant): void
    {
        $variant->loadMissing('product');
        if (! $variant->status || ! $variant->product?->status) {
            throw ValidationException::withMessages([
                'items' => 'El producto '.$variant->sku.' está inactivo y no puede afectar el inventario.',
            ]);
        }
    }

    private function nextNumber(string $type, \DateTimeInterface $date): string
    {
        return $this->dailyNumber(
            $this->codes->sequenceKey('dispatch', $date),
            'REM',
            $date,
            fn (string $code) => DispatchNote::where('number', $code)->exists()
        );
    }

    private function dailyNumber(string $key, string $prefix, \DateTimeInterface $date, callable $exists): string
    {
        DB::table('document_sequences')->insertOrIgnore([
            'key' => $key, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sequence = DB::table('document_sequences')->where('key', $key)->lockForUpdate()->first();
        $number = (int) $sequence->next_number;
        do {
            if ($number > 999) {
                throw new \RuntimeException('Se alcanzó el límite diario de 999 documentos para '.$prefix.'.');
            }
            $code = $this->codes->documentCode($prefix, $number++, $date);
        } while ($exists($code));
        DB::table('document_sequences')->where('key', $key)->update(['next_number' => $number, 'updated_at' => now()]);

        return $code;
    }
}
