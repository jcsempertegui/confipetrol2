<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Traits\AuditLog;

class DocumentPrintController extends Controller
{
    use AuditLog;

    public function dispatchNote(DispatchNote $dispatchNote)
    {
        $dispatchNote->load(['items.variant.product', 'items.serializedItems', 'items.lotAllocations.lot', 'creator', 'confirmer', 'annuller', 'correctedFrom', 'correction']);
        $this->logActivity('REMITOS', 'IMPRIMIR', 'Impresión del remito '.($dispatchNote->number ?: '#'.$dispatchNote->id), $dispatchNote->id);

        return view('documents.dispatch-note', compact('dispatchNote'));
    }

    public function delivery(Delivery $delivery)
    {
        $delivery->load(['worker', 'items.variant.product', 'items.serializedItems', 'items.lotAllocations.lot', 'creator', 'confirmer', 'annuller', 'correctedFrom', 'correction']);
        $this->logActivity('ENTREGAS', 'IMPRIMIR', 'Impresión de la entrega '.($delivery->number ?: '#'.$delivery->id), $delivery->id);

        return view('documents.delivery', compact('delivery'));
    }
}
